#!/usr/bin/env bash

# ==============================================================================
# Graduation Project - Production Environment Startup
# ==============================================================================
#
# Starts the production Docker Compose environment.
#
# Default mode:
#
#   docker compose up -d
#
# This creates missing containers and networks when necessary, then starts the
# services. It does not rebuild application images unless --build is explicitly
# requested.
#
# Start-only mode:
#
#   docker compose start
#
# This starts existing stopped containers only. It cannot create containers that
# were removed by docker compose down.
#
# Usage:
#
#   ./scripts/production/up.sh [options] [service...]
#
# Examples:
#
#   ./scripts/production/up.sh
#   ./scripts/production/up.sh app nginx
#   ./scripts/production/up.sh --start-only
#   ./scripts/production/up.sh --build
#   ./scripts/production/up.sh --pull missing
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

readonly DEFAULT_WAIT_TIMEOUT_SECONDS=120

declare -a SELECTED_SERVICES=()

START_MODE="up"
BUILD_IMAGES=false
REMOVE_ORPHANS=false
PULL_POLICY="missing"
WAIT_TIMEOUT_SECONDS="${DEFAULT_WAIT_TIMEOUT_SECONDS}"


# ------------------------------------------------------------------------------
# Usage
# ------------------------------------------------------------------------------

show_usage() {
    cat <<'EOF'
Usage:
  up.sh [options] [service...]

Description:
  Starts the production Docker Compose environment.

  The default mode uses:

    docker compose up -d

  This creates missing containers and networks when necessary.

  Start-only mode uses:

    docker compose start

  Start-only mode works only when the containers already exist.

Options:
      --start-only        Start existing containers without creating them.
      --up                Use docker compose up. This is the default.
      --build             Build images before starting services.
      --pull <policy>     Image pull policy: always, missing, or never.
                          Default: missing.
      --remove-orphans    Remove containers for obsolete Compose services.
      --wait-timeout N    Maximum seconds to wait for services to run.
                          Default: 120.
  -h, --help              Display this help message.

Examples:
  up.sh
  up.sh app nginx
  up.sh --start-only
  up.sh --build
  up.sh --pull always
  up.sh --remove-orphans
EOF
}


# ------------------------------------------------------------------------------
# Validation helpers
# ------------------------------------------------------------------------------

require_positive_integer() {
    local value="${1:?Value is required}"
    local option_name="${2:?Option name is required}"

    if [[ ! "${value}" =~ ^[1-9][0-9]*$ ]]; then
        die "${option_name} must be a positive integer."
    fi
}

validate_pull_policy() {
    case "${PULL_POLICY}" in
        always|missing|never)
            ;;

        *)
            die \
                "Invalid pull policy '${PULL_POLICY}'. Expected: always, missing, or never."
            ;;
    esac
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

            --start-only)
                START_MODE="start"
                shift
                ;;

            --up)
                START_MODE="up"
                shift
                ;;

            --build)
                BUILD_IMAGES=true
                shift
                ;;

            --remove-orphans)
                REMOVE_ORPHANS=true
                shift
                ;;

            --pull)
                if (($# < 2)); then
                    die "Option '--pull' requires a value."
                fi

                PULL_POLICY="$2"
                validate_pull_policy
                shift 2
                ;;

            --pull=*)
                PULL_POLICY="${1#*=}"
                validate_pull_policy
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
# Service validation
# ------------------------------------------------------------------------------

validate_selected_services() {
    local service_name

    for service_name in "${SELECTED_SERVICES[@]}"; do
        require_service "${service_name}"
    done
}


# ------------------------------------------------------------------------------
# State verification
# ------------------------------------------------------------------------------

wait_for_service_running() {
    local service_name="${1:?Service name is required}"
    local elapsed_seconds=0

    while ((elapsed_seconds < WAIT_TIMEOUT_SECONDS)); do
        if service_is_running "${service_name}"; then
            log_success "Service '${service_name}' is running."
            return 0
        fi

        sleep 2
        elapsed_seconds=$((elapsed_seconds + 2))
    done

    log_error \
        "Service '${service_name}' did not reach the running state within ${WAIT_TIMEOUT_SECONDS} seconds."

    return 1
}

get_services_to_verify() {
    if ((${#SELECTED_SERVICES[@]} > 0)); then
        printf '%s\n' "${SELECTED_SERVICES[@]}"
        return
    fi

    production_compose config --services
}

verify_started_services() {
    local service_name
    local verification_failed=false

    while IFS= read -r service_name; do
        [[ -z "${service_name}" ]] && continue

        if ! wait_for_service_running "${service_name}"; then
            verification_failed=true
        fi
    done < <(get_services_to_verify)

    if [[ "${verification_failed}" == true ]]; then
        die "One or more services failed to reach the running state."
    fi
}


# ------------------------------------------------------------------------------
# Start operations
# ------------------------------------------------------------------------------

start_with_up() {
    local -a command_options=(
        "--detach"
        "--pull"
        "${PULL_POLICY}"
    )

    if [[ "${BUILD_IMAGES}" == true ]]; then
        command_options+=("--build")
    fi

    if [[ "${REMOVE_ORPHANS}" == true ]]; then
        command_options+=("--remove-orphans")
    fi

    log_info "Starting the production environment with Docker Compose up."

    production_compose up \
        "${command_options[@]}" \
        "${SELECTED_SERVICES[@]}"

    log_success "Docker Compose up completed."
}

start_existing_containers() {
    if [[ "${BUILD_IMAGES}" == true ]]; then
        die "Option '--build' cannot be used with '--start-only'."
    fi

    if [[ "${REMOVE_ORPHANS}" == true ]]; then
        die "Option '--remove-orphans' cannot be used with '--start-only'."
    fi

    if [[ "${PULL_POLICY}" != "missing" ]]; then
        die "Option '--pull' cannot be used with '--start-only'."
    fi

    log_info "Starting existing production containers only."

    production_compose start \
        "${SELECTED_SERVICES[@]}"

    log_success "Existing containers were started."
}

perform_start_operation() {
    case "${START_MODE}" in
        up)
            start_with_up
            ;;

        start)
            start_existing_containers
            ;;

        *)
            die "Unsupported start mode: ${START_MODE}"
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

    validate_selected_services

    log_info "Start mode: ${START_MODE}"

    if ((${#SELECTED_SERVICES[@]} == 0)); then
        log_info "Selected services: all production services"
    else
        log_info "Selected services: ${SELECTED_SERVICES[*]}"
    fi

    perform_start_operation
    verify_started_services

    log_success "Production startup completed successfully."

    if [[ "${START_MODE}" == "start" ]]; then
        log_warning \
            "Start-only mode does not create containers removed by docker compose down."
    fi
}

main "$@"
