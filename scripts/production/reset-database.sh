#!/usr/bin/env bash

# ==============================================================================
# Graduation Project - Production Database Reset Script
# ==============================================================================
#
# DANGER:
# This script permanently deletes all existing production database tables,
# recreates them, runs the production seeders, synchronizes Meilisearch index
# settings, and dispatches Scout import jobs for the configured searchable
# models.
#
# Scout imports are queued. This script verifies that Horizon is running before
# dispatching them, but it does not claim that all queued indexing jobs have
# finished processing.
#
# Usage:
#
#   ./scripts/production/reset-database.sh
#   CONFIRMATION_TEXT='RESET-PRODUCTION-DATABASE'
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
readonly HORIZON_SERVICE="horizon"

readonly TEST_MODEL='App\Models\Test'
readonly LIBRARY_MATERIAL_MODEL='App\Models\LibraryMaterial'

readonly CONFIRMATION_TEXT='RESET-PRODUCTION-DATABASE'

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
            "Laravel maintenance mode could not be disabled because the app service is not running."

        return 0
    fi

    log_warning "Attempting to disable Laravel maintenance mode..."

    if "${ARTISAN_SCRIPT}" up; then
        MAINTENANCE_MODE_ENABLED_BY_SCRIPT=false
        log_success "Laravel maintenance mode was disabled."
    else
        log_error \
            "Laravel maintenance mode could not be disabled automatically. Run the following command manually:"
        log_error \
            "./scripts/production/artisan.sh up"
    fi
}

handle_error() {
    local exit_code=$?
    local failed_line="${1:-unknown}"

    trap - ERR

    log_error \
        "Database reset failed at line ${failed_line} with exit code ${exit_code}."

    restore_application_availability

    exit "${exit_code}"
}

trap 'handle_error "${LINENO}"' ERR


# ------------------------------------------------------------------------------
# Validation
# ------------------------------------------------------------------------------

validate_reset_requirements() {
    validate_production_environment

    require_command flock
    require_file "${ARTISAN_SCRIPT}" "Production Artisan script"

    if [[ ! -x "${ARTISAN_SCRIPT}" ]]; then
        die "Production Artisan script is not executable: ${ARTISAN_SCRIPT}"
    fi

    require_running_service "${APP_SERVICE}"
    require_running_service "${HORIZON_SERVICE}"
}


# ------------------------------------------------------------------------------
# User confirmation
# ------------------------------------------------------------------------------

request_destructive_confirmation() {
    printf '\n'
    log_warning "DESTRUCTIVE PRODUCTION OPERATION"
    printf '\n'

    printf '%s\n' \
        "This operation will permanently:" \
        "  - Delete every table and all data in the production database." \
        "  - Recreate the database schema from migrations." \
        "  - Run all configured seeders." \
        "  - Synchronize Meilisearch index settings." \
        "  - Flush existing index records and dispatch fresh queued Scout imports for:" \
        "      ${TEST_MODEL}" \
        "      ${LIBRARY_MATERIAL_MODEL}"

    printf '\n'

    log_warning "This operation cannot be undone without a valid database backup."

    printf '\n'

    confirm_exactly \
        "${CONFIRMATION_TEXT}" \
        "Type '${CONFIRMATION_TEXT}' exactly to continue:"
}


# ------------------------------------------------------------------------------
# Maintenance mode
# ------------------------------------------------------------------------------

enable_maintenance_mode() {
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
# Database reset
# ------------------------------------------------------------------------------

reset_database() {
    log_warning "Dropping and recreating the production database schema..."

    "${ARTISAN_SCRIPT}" migrate:fresh \
        --seed \
        --force \
        --no-interaction

    log_success "Database migrations and seeders completed successfully."
}


# ------------------------------------------------------------------------------
# Meilisearch synchronization
# ------------------------------------------------------------------------------

synchronize_meilisearch_settings() {
    log_info "Synchronizing Meilisearch index settings..."

    "${ARTISAN_SCRIPT}" scout:sync-index-settings \
        --no-interaction

    log_success "Meilisearch index settings were synchronized successfully."
}


# ------------------------------------------------------------------------------
# Scout imports
# ------------------------------------------------------------------------------

dispatch_scout_imports() {
    log_info \
        "Flushing the existing index records and dispatching Scout import jobs for '${LIBRARY_MATERIAL_MODEL}'..."

    "${ARTISAN_SCRIPT}" scout:import \
        "${LIBRARY_MATERIAL_MODEL}" \
        --fresh \
        --no-interaction

    log_success \
        "The existing index records were flushed and Scout import jobs for '${LIBRARY_MATERIAL_MODEL}' were dispatched."

    log_info \
        "Flushing the existing index records and dispatching Scout import jobs for '${TEST_MODEL}'..."

    "${ARTISAN_SCRIPT}" scout:import \
        "${TEST_MODEL}" \
        --fresh \
        --no-interaction

    log_success \
        "The existing index records were flushed and Scout import jobs for '${TEST_MODEL}' were dispatched."
}


# ------------------------------------------------------------------------------
# Horizon status
# ------------------------------------------------------------------------------

verify_horizon_after_dispatch() {
    if ! service_is_running "${HORIZON_SERVICE}"; then
        die \
            "Horizon stopped after Scout jobs were dispatched. Queued indexing jobs may remain unprocessed."
    fi

    log_success "Horizon is running and can process the queued Scout imports."
}


# ------------------------------------------------------------------------------
# Main
# ------------------------------------------------------------------------------

main() {
    log_warning "Preparing to reset the production database..."

    validate_reset_requirements

    acquire_script_lock "database-reset"

    log_success "Exclusive database reset lock acquired."

    request_destructive_confirmation

    enable_maintenance_mode

    reset_database

    synchronize_meilisearch_settings

    dispatch_scout_imports

    verify_horizon_after_dispatch

    disable_maintenance_mode

    log_success "Production database reset completed successfully."

    log_warning \
        "Scout import jobs were queued and may still be processing in Horizon."

    log_info \
        "Monitor Horizon and failed jobs before considering Meilisearch fully rebuilt."
}

main "$@"
