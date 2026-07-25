#!/usr/bin/env bash

# ==============================================================================
# Graduation Project - Production Deployment Script
# ==============================================================================
#
# Deploys the already-pulled application source code to the production
# containers.
#
# This script does NOT run git pull. Source-control operations remain an
# explicit responsibility of the operator.
#
# Deployment flow:
#
#   1. Validate the production environment.
#   2. Acquire an exclusive deployment lock.
#   3. Build the application image.
#   4. Put Laravel into maintenance mode when the old app is running.
#   5. Recreate the application container.
#   6. Run production database migrations.
#   7. Rebuild Laravel caches.
#   8. Recreate long-running application services.
#   9. Recreate Nginx to refresh its upstream container resolution.
#  10. Verify that all required services are running.
#  11. Disable maintenance mode.
#
# Usage:
#
#   ./scripts/production/deploy.sh
#
# ==============================================================================

set -Eeuo pipefail

SCRIPT_DIR="$(
    cd -- "$(dirname -- "${BASH_SOURCE[0]}")" >/dev/null 2>&1
    pwd -P
)"

# shellcheck source=./common.sh
source "${SCRIPT_DIR}/common.sh"

readonly ARTISAN_SCRIPT="${SCRIPT_DIR}/artisan.sh"

readonly APP_SERVICE="app"
readonly NGINX_SERVICE="nginx"
readonly PHPMYADMIN_SERVICE="phpmyadmin"

readonly -a LONG_RUNNING_SERVICES=(
    "reverb"
    "horizon"
    "scheduler"
)

readonly -a REQUIRED_RUNNING_SERVICES=(
    "app"
    "reverb"
    "horizon"
    "scheduler"
    "nginx"
    "phpmyadmin"
)

MAINTENANCE_MODE_ENABLED_BY_SCRIPT=false


# ------------------------------------------------------------------------------
# Cleanup and failure handling
# ------------------------------------------------------------------------------

restore_application_availability() {
    if [[ "${MAINTENANCE_MODE_ENABLED_BY_SCRIPT}" != true ]]; then
        return 0
    fi

    if ! service_is_running "${APP_SERVICE}"; then
        log_warning \
            "The application remains in maintenance mode because the app service is not running."

        return 0
    fi

    log_warning "Attempting to disable Laravel maintenance mode..."

    if "${ARTISAN_SCRIPT}" up; then
        MAINTENANCE_MODE_ENABLED_BY_SCRIPT=false
        log_success "Laravel maintenance mode was disabled."
    else
        log_error \
            "Could not disable Laravel maintenance mode automatically. Run 'php artisan up' manually."
    fi
}

handle_error() {
    local exit_code=$?
    local failed_line="${1:-unknown}"

    trap - ERR

    log_error \
        "Deployment failed at line ${failed_line} with exit code ${exit_code}."

    restore_application_availability

    exit "${exit_code}"
}

trap 'handle_error "${LINENO}"' ERR


# ------------------------------------------------------------------------------
# Validation
# ------------------------------------------------------------------------------

validate_deployment_requirements() {
    validate_production_environment

    require_command flock
    require_file "${ARTISAN_SCRIPT}" "Production Artisan script"

    if [[ ! -x "${ARTISAN_SCRIPT}" ]]; then
        die "Production Artisan script is not executable: ${ARTISAN_SCRIPT}"
    fi

    require_service "${APP_SERVICE}"
    require_service "${PHPMYADMIN_SERVICE}"
    require_service "${NGINX_SERVICE}"

    local service_name

    for service_name in "${LONG_RUNNING_SERVICES[@]}"; do
        require_service "${service_name}"
    done
}


# ------------------------------------------------------------------------------
# Service readiness
# ------------------------------------------------------------------------------

wait_for_service() {
    local service_name="${1:?Service name is required}"
    local maximum_attempts="${2:-30}"
    local delay_seconds="${3:-2}"

    local attempt

    log_info "Waiting for service '${service_name}' to become running..."

    for ((attempt = 1; attempt <= maximum_attempts; attempt++)); do
        if service_is_running "${service_name}"; then
            log_success "Service '${service_name}' is running."
            return 0
        fi

        sleep "${delay_seconds}"
    done

    production_compose ps "${service_name}" >&2 || true

    die "Service '${service_name}' did not become running within the expected time."
}


# ------------------------------------------------------------------------------
# Maintenance mode
# ------------------------------------------------------------------------------

enable_maintenance_mode_if_possible() {
    if ! service_is_running "${APP_SERVICE}"; then
        log_warning \
            "The app service is not currently running; maintenance mode cannot be enabled before deployment."

        return 0
    fi

    log_info "Enabling Laravel maintenance mode..."

    "${ARTISAN_SCRIPT}" down \
        --retry=60 \
        --refresh=15

    MAINTENANCE_MODE_ENABLED_BY_SCRIPT=true

    log_success "Laravel maintenance mode is enabled."
}

disable_maintenance_mode() {
    if [[ "${MAINTENANCE_MODE_ENABLED_BY_SCRIPT}" != true ]]; then
        return 0
    fi

    log_info "Disabling Laravel maintenance mode..."

    "${ARTISAN_SCRIPT}" up

    MAINTENANCE_MODE_ENABLED_BY_SCRIPT=false

    log_success "Laravel maintenance mode is disabled."
}


# ------------------------------------------------------------------------------
# Deployment steps
# ------------------------------------------------------------------------------

build_application_image() {
    log_info "Building the production application image..."

    production_compose build "${APP_SERVICE}"

    log_success "Production application image was built successfully."
}

recreate_application_service() {
    log_info "Recreating the application service..."

    production_compose up \
        --detach \
        --force-recreate \
        --no-deps \
        "${APP_SERVICE}"

    wait_for_service "${APP_SERVICE}"

    log_success "Application service was recreated successfully."
}

run_database_migrations() {
    log_info "Running production database migrations..."

    "${ARTISAN_SCRIPT}" migrate --force

    log_success "Database migrations completed successfully."
}

rebuild_laravel_caches() {
    log_info "Clearing stale Laravel caches..."

    "${ARTISAN_SCRIPT}" optimize:clear

    log_info "Building Laravel production caches..."

    "${ARTISAN_SCRIPT}" optimize

    log_success "Laravel production caches were rebuilt successfully."
}

recreate_long_running_services() {
    log_info "Recreating long-running application services..."

    production_compose up \
        --detach \
        --force-recreate \
        --no-deps \
        "${LONG_RUNNING_SERVICES[@]}"

    local service_name

    for service_name in "${LONG_RUNNING_SERVICES[@]}"; do
        wait_for_service "${service_name}"
    done

    log_success "Long-running application services were recreated successfully."
}

ensure_phpmyadmin_service_is_running() {
    log_info "Ensuring phpMyAdmin service is created and running..."

    production_compose up \
        --detach \
        "${PHPMYADMIN_SERVICE}"

    wait_for_service "${PHPMYADMIN_SERVICE}"

    log_success "phpMyAdmin service is running."
}

recreate_nginx_service() {
    log_info "Recreating Nginx to refresh application upstream resolution..."

    production_compose up \
        --detach \
        --force-recreate \
        --no-deps \
        "${NGINX_SERVICE}"

    wait_for_service "${NGINX_SERVICE}"

    log_success "Nginx was recreated successfully."
}

verify_required_services() {
    local service_name
    local verification_failed=false

    log_info "Verifying required production services..."

    for service_name in "${REQUIRED_RUNNING_SERVICES[@]}"; do
        if service_is_running "${service_name}"; then
            log_success "Service '${service_name}' is running."
        else
            log_error "Service '${service_name}' is not running."
            verification_failed=true
        fi
    done

    if [[ "${verification_failed}" == true ]]; then
        production_compose ps >&2 || true
        die "One or more required production services are not running."
    fi
}


# ------------------------------------------------------------------------------
# Main
# ------------------------------------------------------------------------------

main() {
    log_info "Starting production deployment..."

    validate_deployment_requirements

    acquire_script_lock "deployment"

    log_success "Exclusive deployment lock acquired."

    build_application_image

    enable_maintenance_mode_if_possible

    recreate_application_service

    run_database_migrations

    rebuild_laravel_caches

    recreate_long_running_services

    ensure_phpmyadmin_service_is_running

    recreate_nginx_service

    verify_required_services

    disable_maintenance_mode

    log_success "Production deployment completed successfully."
}

main "$@"
