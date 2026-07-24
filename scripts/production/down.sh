#!/usr/bin/env bash

# ==============================================================================
# Graduation Project - Production Environment Shutdown
# ==============================================================================
#
# Stops or removes the production Docker Compose environment.
#
# Default mode:
#
#   docker compose down
#
# This stops and removes project containers and Compose networks. Named volumes
# are intentionally preserved.
#
# Stop-only mode:
#
#   docker compose stop
#
# This stops containers without removing them, allowing them to be started later
# with:
#
#   ./scripts/production/up.sh --start-only
#
# Safety:
#   This script deliberately does not support Docker Compose's --volumes option.
#   Production data volumes must never be deleted through this lifecycle script.
#
# Usage:
#
#   ./scripts/production/down.sh [options] [service...]
#
# Examples:
#
#   ./scripts/production/down.sh
#   ./scripts/production/down.sh --stop-only
#   ./scripts/production/down.sh --stop-only horizon reverb
#   ./scripts/production/down.sh --remove-orphans
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

readonly DEFAULT_STOP_TIMEOUT_SECONDS=30
readonly DOWN_CONFIRMATION_TEXT='DOWN-PRODUCTION'

declare -a SELECTED_SERVICES=()

SHUTDOWN_MODE="down"
STOP_TIMEOUT_SECONDS="${DEFAULT_STOP_TIMEOUT_SECONDS}"
REMOVE_ORPHANS=false
SKIP_CONFIRMATION=false


# ------------------------------------------------------------------------------
# Usage
# ------------------------------------------------------------------------------

show_usage() {
    cat <<'EOF'
Usage:
  down.sh [options] [service...]

Description:
  Stops or removes the production Docker Compose environment.

  Default mode:

    docker compose down

  This removes containers and project networks while preserving named volumes.

  Stop-only mode:

    docker compose stop

  This stops existing containers without removing them.

Options:
      --stop-only         Stop containers without removing them.
      --down              Remove containers and project networks.
                          This is the default.
      --remove-orphans    Remove obsolete Compose project containers.
                          Available only in down mode.
      --timeout N         Shutdown timeout in seconds. Default: 30.
      --yes               Skip confirmation in down mode.
  -h, --help              Display this help message.

Examples:
  down.sh
  down.sh --yes
  down.sh --stop-only
  down.sh --stop-only horizon reverb
  down.sh --remove-orphans

Safety:
  This script never deletes named production volumes.
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

            --stop-only)
                SHUTDOWN_MODE="stop"
                shift
                ;;

            --down)
                SHUTDOWN_MODE="down"
                shift
                ;;

            --remove-orphans)
                REMOVE_ORPHANS=true
                shift
                ;;

            --yes)
                SKIP_CONFIRMATION=true
                shift
                ;;

            --timeout)
                if (($# < 2)); then
                    die "Option '--timeout' requires a value."
                fi

                STOP_TIMEOUT_SECONDS="$2"

                require_positive_integer \
                    "${STOP_TIMEOUT_SECONDS}" \
                    "--timeout"

                shift 2
                ;;

            --timeout=*)
                STOP_TIMEOUT_SECONDS="${1#*=}"

                require_positive_integer \
                    "${STOP_TIMEOUT_SECONDS}" \
                    "--timeout"

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
# Validation
# ------------------------------------------------------------------------------

validate_shutdown_arguments() {
    local service_name

    for service_name in "${SELECTED_SERVICES[@]}"; do
        require_service "${service_name}"
    done

    if [[ "${SHUTDOWN_MODE}" == "down" ]] &&
        ((${#SELECTED_SERVICES[@]} > 0)); then
        die \
            "Docker Compose down operates on the complete project and cannot be limited to selected services. Use '--stop-only' for individual services."
    fi

    if [[ "${SHUTDOWN_MODE}" == "stop" ]] &&
        [[ "${REMOVE_ORPHANS}" == true ]]; then
        die \
            "Option '--remove-orphans' cannot be used with '--stop-only'."
    fi
}


# ------------------------------------------------------------------------------
# Confirmation
# ------------------------------------------------------------------------------

confirm_down_operation() {
    if [[ "${SHUTDOWN_MODE}" != "down" ]]; then
        return 0
    fi

    if [[ "${SKIP_CONFIRMATION}" == true ]]; then
        log_warning \
            "Docker Compose down confirmation was skipped using '--yes'."

        return 0
    fi

    printf '\n'
    log_warning "PRODUCTION ENVIRONMENT SHUTDOWN"
    printf '\n'

    printf '%s\n' \
        "This operation will:" \
        "  - Stop all production containers." \
        "  - Remove the production project containers." \
        "  - Remove the Docker Compose project network." \
        "  - Preserve named production volumes and their data."

    printf '\n'

    confirm_exactly \
        "${DOWN_CONFIRMATION_TEXT}" \
        "Type '${DOWN_CONFIRMATION_TEXT}' exactly to continue:"
}


# ------------------------------------------------------------------------------
# Shutdown operations
# ------------------------------------------------------------------------------

stop_services_only() {
    if ((${#SELECTED_SERVICES[@]} == 0)); then
        log_info "Stopping all production containers without removing them."
    else
        log_info \
            "Stopping production service(s) without removing them: ${SELECTED_SERVICES[*]}"
    fi

    production_compose stop \
        --timeout "${STOP_TIMEOUT_SECONDS}" \
        "${SELECTED_SERVICES[@]}"

    log_success "Selected production containers were stopped."
}

remove_environment() {
    local -a command_options=(
        "--timeout"
        "${STOP_TIMEOUT_SECONDS}"
    )

    if [[ "${REMOVE_ORPHANS}" == true ]]; then
        command_options+=("--remove-orphans")
    fi

    log_warning \
        "Stopping and removing production containers and project networks."

    production_compose down \
        "${command_options[@]}"

    log_success \
        "Production containers and project networks were removed."

    log_success \
        "Named production volumes were preserved."
}

perform_shutdown_operation() {
    case "${SHUTDOWN_MODE}" in
        stop)
            stop_services_only
            ;;

        down)
            remove_environment
            ;;

        *)
            die "Unsupported shutdown mode: ${SHUTDOWN_MODE}"
            ;;
    esac
}


# ------------------------------------------------------------------------------
# Main
# ------------------------------------------------------------------------------

main() {
    parse_arguments "$@"

    validate_production_environment

    acquire_script_lock "production-lifecycle"

    validate_shutdown_arguments
    confirm_down_operation

    log_info "Shutdown mode: ${SHUTDOWN_MODE}"

    perform_shutdown_operation

    if [[ "${SHUTDOWN_MODE}" == "stop" ]]; then
        log_info \
            "Restart the existing containers with: ./scripts/production/up.sh --start-only"
    else
        log_info \
            "Recreate and start the environment with: ./scripts/production/up.sh"
    fi
}

main "$@"
