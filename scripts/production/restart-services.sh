#!/usr/bin/env bash

# ==============================================================================
# Graduation Project - Production Services Restart
# ==============================================================================
#
# Restarts or recreates selected production services without rebuilding Docker
# images, pulling source-code changes, or running database migrations.
#
# Default behavior:
#   Recreate containers using:
#
#       docker compose up -d --force-recreate --no-deps
#
# This default behavior reloads environment variables from .env.production.
#
# Restart-only behavior:
#   Restart the existing containers using:
#
#       docker compose restart
#
# Restart-only mode does not reload changed environment variables or Compose
# configuration.
#
# Usage:
#
#   ./scripts/production/restart-services.sh [options] [service...]
#
# Examples:
#
#   ./scripts/production/restart-services.sh
#   ./scripts/production/restart-services.sh app
#   ./scripts/production/restart-services.sh horizon reverb
#   ./scripts/production/restart-services.sh --restart-only horizon
#   ./scripts/production/restart-services.sh --all
#
# ==============================================================================

set -Eeuo pipefail

SCRIPT_DIR="$(
    cd -- "$(dirname -- "${BASH_SOURCE[0]}")" >/dev/null 2>&1
    pwd -P
)"

# shellcheck source=./common.sh
source "${SCRIPT_DIR}/common.sh"


# ------------------------------------------------------------------------------
# Configuration
# ------------------------------------------------------------------------------

readonly DEFAULT_WAIT_TIMEOUT_SECONDS=60
readonly DEFAULT_RESTART_TIMEOUT_SECONDS=30

readonly -a DEFAULT_SERVICES=(
    "app"
    "reverb"
    "horizon"
    "scheduler"
    "nginx"
)

declare -a SELECTED_SERVICES=()

OPERATION_MODE="recreate"
WAIT_TIMEOUT_SECONDS="${DEFAULT_WAIT_TIMEOUT_SECONDS}"
RESTART_TIMEOUT_SECONDS="${DEFAULT_RESTART_TIMEOUT_SECONDS}"
USE_ALL_SERVICES=false


# ------------------------------------------------------------------------------
# Usage
# ------------------------------------------------------------------------------

show_usage() {
    cat <<'EOF'
Usage:
  restart-services.sh [options] [service...]

Description:
  Recreates or restarts selected production Docker Compose services without
  rebuilding images or running database migrations.

  When no services are specified, these application services are processed:

    app reverb horizon scheduler nginx

Options:
      --restart-only       Restart existing containers without recreating them.
                           This does not reload changed environment variables.

      --recreate           Recreate containers without rebuilding their images.
                           This is the default behavior.

      --all                Process all services defined in Docker Compose.

      --wait-timeout N     Maximum number of seconds to wait for each service
                           to reach the running state. Default: 60.

      --stop-timeout N     Container shutdown timeout used in restart-only mode.
                           Default: 30.

  -h, --help               Display this help message.

Examples:
  restart-services.sh
  restart-services.sh app
  restart-services.sh horizon reverb
  restart-services.sh --restart-only horizon
  restart-services.sh --recreate app nginx
  restart-services.sh --all
EOF
}


# ------------------------------------------------------------------------------
# Argument validation
# ------------------------------------------------------------------------------

require_positive_integer() {
    local value="${1:?Value is required}"
    local option_name="${2:?Option name is required}"

    if [[ ! "${value}" =~ ^[1-9][0-9]*$ ]]; then
        die "${option_name} must be a positive integer."
    fi
}


# ------------------------------------------------------------------------------
# Argument parsing
# ------------------------------------------------------------------------------

parse_arguments() {
    while (($# > 0)); do
        case "$1" in
            -h|--help)
                show_usage
                exit 0
                ;;

            --restart-only)
                OPERATION_MODE="restart"
                shift
                ;;

            --recreate)
                OPERATION_MODE="recreate"
                shift
                ;;

            --all)
                USE_ALL_SERVICES=true
                shift
                ;;

            --wait-timeout)
                if (($# < 2)); then
                    die "Option '--wait-timeout' requires a value."
                fi

                WAIT_TIMEOUT_SECONDS="$2"

                require_positive_integer \
                    "${WAIT_TIMEOUT_SECONDS}" \
                    "--wait-timeout"

                shift 2
                ;;

            --wait-timeout=*)
                WAIT_TIMEOUT_SECONDS="${1#*=}"

                require_positive_integer \
                    "${WAIT_TIMEOUT_SECONDS}" \
                    "--wait-timeout"

                shift
                ;;

            --stop-timeout)
                if (($# < 2)); then
                    die "Option '--stop-timeout' requires a value."
                fi

                RESTART_TIMEOUT_SECONDS="$2"

                require_positive_integer \
                    "${RESTART_TIMEOUT_SECONDS}" \
                    "--stop-timeout"

                shift 2
                ;;

            --stop-timeout=*)
                RESTART_TIMEOUT_SECONDS="${1#*=}"

                require_positive_integer \
                    "${RESTART_TIMEOUT_SECONDS}" \
                    "--stop-timeout"

                shift
                ;;

            --)
                shift

                while (($# > 0)); do
                    SELECTED_SERVICES+=("$1")
                    shift
                done
                ;;

            -*)
                die "Unknown option: $1"
                ;;

            *)
                SELECTED_SERVICES+=("$1")
                shift
                ;;
        esac
    done
}


# ------------------------------------------------------------------------------
# Service selection
# ------------------------------------------------------------------------------

load_all_services() {
    mapfile -t SELECTED_SERVICES < <(
        production_compose config --services
    )

    if ((${#SELECTED_SERVICES[@]} == 0)); then
        die "Docker Compose does not define any services."
    fi
}

load_default_services() {
    SELECTED_SERVICES=("${DEFAULT_SERVICES[@]}")
}

resolve_selected_services() {
    if [[ "${USE_ALL_SERVICES}" == true ]] &&
        ((${#SELECTED_SERVICES[@]} > 0)); then
        die "Option '--all' cannot be combined with explicit service names."
    fi

    if [[ "${USE_ALL_SERVICES}" == true ]]; then
        load_all_services
        return
    fi

    if ((${#SELECTED_SERVICES[@]} == 0)); then
        load_default_services
    fi
}


# ------------------------------------------------------------------------------
# Service validation
# ------------------------------------------------------------------------------

validate_selected_services() {
    local service_name

    for service_name in "${SELECTED_SERVICES[@]}"; do
        require_service "${service_name}"
    done
}


# ------------------------------------------------------------------------------
# Service state verification
# ------------------------------------------------------------------------------

wait_for_service_running() {
    local service_name="${1:?Service name is required}"
    local elapsed_seconds=0

    while ((elapsed_seconds < WAIT_TIMEOUT_SECONDS)); do
        if service_is_running "${service_name}"; then
            log_success \
                "Service '${service_name}' is running."

            return 0
        fi

        sleep 2
        elapsed_seconds=$((elapsed_seconds + 2))
    done

    log_error \
        "Service '${service_name}' did not reach the running state within ${WAIT_TIMEOUT_SECONDS} seconds."

    return 1
}

verify_services_running() {
    local service_name
    local verification_failed=false

    for service_name in "${SELECTED_SERVICES[@]}"; do
        if ! wait_for_service_running "${service_name}"; then
            verification_failed=true
        fi
    done

    if [[ "${verification_failed}" == true ]]; then
        die "One or more services failed to reach the running state."
    fi
}


# ------------------------------------------------------------------------------
# Operations
# ------------------------------------------------------------------------------

recreate_services() {
    log_info \
        "Recreating production service(s) without rebuilding images: ${SELECTED_SERVICES[*]}"

    production_compose up \
        --detach \
        --force-recreate \
        --no-deps \
        "${SELECTED_SERVICES[@]}"

    log_success "Selected service containers were recreated."
}

restart_services_only() {
    local service_name

    for service_name in "${SELECTED_SERVICES[@]}"; do
        if ! service_is_running "${service_name}"; then
            die \
                "Service '${service_name}' is not running. Restart-only mode requires existing running containers. Use the default recreate mode instead."
        fi
    done

    log_info \
        "Restarting existing production container(s): ${SELECTED_SERVICES[*]}"

    production_compose restart \
        --timeout "${RESTART_TIMEOUT_SECONDS}" \
        "${SELECTED_SERVICES[@]}"

    log_success "Selected service containers were restarted."
}

perform_operation() {
    case "${OPERATION_MODE}" in
        recreate)
            recreate_services
            ;;

        restart)
            restart_services_only
            ;;

        *)
            die "Unsupported operation mode: ${OPERATION_MODE}"
            ;;
    esac
}


# ------------------------------------------------------------------------------
# Main
# ------------------------------------------------------------------------------

main() {
    parse_arguments "$@"

    validate_production_environment

    acquire_script_lock "service-restart"

    resolve_selected_services
    validate_selected_services

    log_info "Operation mode: ${OPERATION_MODE}"
    log_info "Selected services: ${SELECTED_SERVICES[*]}"

    perform_operation
    verify_services_running

    log_success "Production service operation completed successfully."

    if [[ "${OPERATION_MODE}" == "restart" ]]; then
        log_warning \
            "Restart-only mode does not apply changes made to environment variables or Docker Compose configuration."
    fi
}

main "$@"
