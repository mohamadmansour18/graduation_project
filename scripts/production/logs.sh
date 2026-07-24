#!/usr/bin/env bash

# ==============================================================================
# Graduation Project - Production Logs Viewer
# ==============================================================================
#
# Displays Docker Compose logs for one, multiple, or all production services.
#
# Examples:
#
#   ./scripts/production/logs.sh app
#   ./scripts/production/logs.sh horizon --follow
#   ./scripts/production/logs.sh nginx --tail 200
#   ./scripts/production/logs.sh app reverb --since 30m
#   ./scripts/production/logs.sh --follow
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
# Usage
# ------------------------------------------------------------------------------

show_usage() {
    cat <<'EOF'
Usage:
  logs.sh [options] [service...]

Options:
  -f, --follow              Follow log output continuously.
  -t, --timestamps          Display timestamps.
      --tail <number|all>   Number of lines to show from the end.
      --since <value>       Show logs since a timestamp or relative duration.
      --until <value>       Show logs until a timestamp or relative duration.
      --no-color            Disable colored log output.
  -h, --help                Display this help message.

Examples:
  logs.sh app
  logs.sh app horizon
  logs.sh horizon --follow
  logs.sh nginx --tail 200
  logs.sh app reverb --since 30m
  logs.sh --follow --tail 100
  logs.sh --since "2026-07-24T20:00:00"
EOF
}


# ------------------------------------------------------------------------------
# Arguments
# ------------------------------------------------------------------------------

declare -a SERVICES=()
declare -a LOG_OPTIONS=()

FOLLOW_MODE=false


# ------------------------------------------------------------------------------
# Argument parsing
# ------------------------------------------------------------------------------

while (($# > 0)); do
    case "$1" in
        -h|--help)
            show_usage
            exit 0
            ;;

        -f|--follow)
            LOG_OPTIONS+=("--follow")
            FOLLOW_MODE=true
            shift
            ;;

        -t|--timestamps)
            LOG_OPTIONS+=("--timestamps")
            shift
            ;;

        --no-color)
            LOG_OPTIONS+=("--no-color")
            shift
            ;;

        --tail)
            if (($# < 2)); then
                die "Option '--tail' requires a value."
            fi

            LOG_OPTIONS+=("--tail" "$2")
            shift 2
            ;;

        --tail=*)
            LOG_OPTIONS+=("$1")
            shift
            ;;

        --since)
            if (($# < 2)); then
                die "Option '--since' requires a value."
            fi

            LOG_OPTIONS+=("--since" "$2")
            shift 2
            ;;

        --since=*)
            LOG_OPTIONS+=("$1")
            shift
            ;;

        --until)
            if (($# < 2)); then
                die "Option '--until' requires a value."
            fi

            LOG_OPTIONS+=("--until" "$2")
            shift 2
            ;;

        --until=*)
            LOG_OPTIONS+=("$1")
            shift
            ;;

        --)
            shift

            while (($# > 0)); do
                SERVICES+=("$1")
                shift
            done
            ;;

        -*)
            die "Unknown option: $1"
            ;;

        *)
            SERVICES+=("$1")
            shift
            ;;
    esac
done


# ------------------------------------------------------------------------------
# Validation
# ------------------------------------------------------------------------------

validate_logs_requirements() {
    validate_production_environment

    local service_name

    for service_name in "${SERVICES[@]}"; do
        require_service "${service_name}"
    done
}


# ------------------------------------------------------------------------------
# Log display
# ------------------------------------------------------------------------------

display_logs() {
    if ((${#SERVICES[@]} == 0)); then
        log_info "Displaying logs for all production services."
    else
        log_info "Displaying logs for service(s): ${SERVICES[*]}"
    fi

    if [[ "${FOLLOW_MODE}" == true ]]; then
        log_info "Follow mode is enabled. Press Ctrl+C to stop."
    fi

    production_compose logs \
        "${LOG_OPTIONS[@]}" \
        "${SERVICES[@]}"
}


# ------------------------------------------------------------------------------
# Main
# ------------------------------------------------------------------------------

main() {
    validate_logs_requirements
    display_logs
}

main
