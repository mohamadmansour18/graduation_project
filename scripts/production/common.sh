#!/usr/bin/env bash

# ==============================================================================
# Graduation Project - Production Scripts Shared Library
# ==============================================================================
#
# This file contains shared configuration and helper functions used by all
# production management scripts.
#
# It is intended to be sourced, not executed directly:
#
#   source "/path/to/scripts/production/common.sh"
#
# ==============================================================================

set -Eeuo pipefail


# ------------------------------------------------------------------------------
# Project paths
# ------------------------------------------------------------------------------

# Absolute directory containing this file:
# /project/scripts/production
COMMON_SCRIPT_DIR="$(
    cd -- "$(dirname -- "${BASH_SOURCE[0]}")" >/dev/null 2>&1
    pwd -P
)"

# common.sh is located at:
# scripts/production/common.sh
#
# Therefore, the project root is two directories above this file.
PROJECT_ROOT="$(
    cd -- "${COMMON_SCRIPT_DIR}/../.." >/dev/null 2>&1
    pwd -P
)"

readonly COMMON_SCRIPT_DIR
readonly PROJECT_ROOT

readonly PRODUCTION_ENV_FILE="${PROJECT_ROOT}/.env.production"
readonly PRODUCTION_COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.production.yml"


# ------------------------------------------------------------------------------
# Terminal colors
# ------------------------------------------------------------------------------

# Colors are enabled only when output is connected to an interactive terminal.
# This prevents control characters from appearing in logs or CI output.
if [[ -t 1 ]]; then
    readonly COLOR_RED=$'\033[0;31m'
    readonly COLOR_GREEN=$'\033[0;32m'
    readonly COLOR_YELLOW=$'\033[0;33m'
    readonly COLOR_BLUE=$'\033[0;34m'
    readonly COLOR_RESET=$'\033[0m'
else
    readonly COLOR_RED=''
    readonly COLOR_GREEN=''
    readonly COLOR_YELLOW=''
    readonly COLOR_BLUE=''
    readonly COLOR_RESET=''
fi


# ------------------------------------------------------------------------------
# Logging helpers
# ------------------------------------------------------------------------------

timestamp() {
    date '+%Y-%m-%d %H:%M:%S'
}

log_info() {
    printf '%s[%s] INFO: %s%s\n' \
        "${COLOR_BLUE}" \
        "$(timestamp)" \
        "$*" \
        "${COLOR_RESET}"
}

log_success() {
    printf '%s[%s] SUCCESS: %s%s\n' \
        "${COLOR_GREEN}" \
        "$(timestamp)" \
        "$*" \
        "${COLOR_RESET}"
}

log_warning() {
    printf '%s[%s] WARNING: %s%s\n' \
        "${COLOR_YELLOW}" \
        "$(timestamp)" \
        "$*" \
        "${COLOR_RESET}" >&2
}

log_error() {
    printf '%s[%s] ERROR: %s%s\n' \
        "${COLOR_RED}" \
        "$(timestamp)" \
        "$*" \
        "${COLOR_RESET}" >&2
}

die() {
    log_error "$*"
    exit 1
}


# ------------------------------------------------------------------------------
# Validation helpers
# ------------------------------------------------------------------------------

require_command() {
    local command_name="${1:?Command name is required}"

    if ! command -v "${command_name}" >/dev/null 2>&1; then
        die "Required command '${command_name}' is not installed or is not available in PATH."
    fi
}

require_file() {
    local file_path="${1:?File path is required}"
    local description="${2:-Required file}"

    if [[ ! -f "${file_path}" ]]; then
        die "${description} was not found: ${file_path}"
    fi
}

require_readable_file() {
    local file_path="${1:?File path is required}"
    local description="${2:-Required file}"

    require_file "${file_path}" "${description}"

    if [[ ! -r "${file_path}" ]]; then
        die "${description} is not readable: ${file_path}"
    fi
}

require_directory() {
    local directory_path="${1:?Directory path is required}"
    local description="${2:-Required directory}"

    if [[ ! -d "${directory_path}" ]]; then
        die "${description} was not found: ${directory_path}"
    fi
}


# ------------------------------------------------------------------------------
# Production environment validation
# ------------------------------------------------------------------------------

validate_production_environment() {
    require_command docker

    require_directory \
        "${PROJECT_ROOT}" \
        "Project root directory"

    require_readable_file \
        "${PRODUCTION_ENV_FILE}" \
        "Production environment file"

    require_readable_file \
        "${PRODUCTION_COMPOSE_FILE}" \
        "Production Docker Compose file"

    if ! docker compose version >/dev/null 2>&1; then
        die "Docker Compose plugin is not available. Expected the command: docker compose"
    fi

    if ! docker info >/dev/null 2>&1; then
        die "Docker daemon is unavailable or the current user cannot access it."
    fi

    if ! production_compose config --quiet; then
        die "Production Docker Compose configuration is invalid."
    fi
}


# ------------------------------------------------------------------------------
# Docker Compose wrapper
# ------------------------------------------------------------------------------

production_compose() {
    docker compose \
        --project-directory "${PROJECT_ROOT}" \
        --env-file "${PRODUCTION_ENV_FILE}" \
        --file "${PRODUCTION_COMPOSE_FILE}" \
        "$@"
}


# ------------------------------------------------------------------------------
# Docker Compose service helpers
# ------------------------------------------------------------------------------

service_exists() {
    local service_name="${1:?Service name is required}"

    production_compose config --services |
        grep --fixed-strings --line-regexp --quiet -- "${service_name}"
}

require_service() {
    local service_name="${1:?Service name is required}"

    if ! service_exists "${service_name}"; then
        die "Docker Compose service '${service_name}' is not defined."
    fi
}

service_is_running() {
    local service_name="${1:?Service name is required}"

    production_compose ps \
        --services \
        --status running |
        grep --fixed-strings --line-regexp --quiet -- "${service_name}"
}

require_running_service() {
    local service_name="${1:?Service name is required}"

    require_service "${service_name}"

    if ! service_is_running "${service_name}"; then
        die "Docker Compose service '${service_name}' is not running."
    fi
}


# ------------------------------------------------------------------------------
# Confirmation helper
# ------------------------------------------------------------------------------

confirm_exactly() {
    local expected_value="${1:?Expected confirmation value is required}"
    local prompt_message="${2:-Type '${expected_value}' to continue:}"

    local entered_value=''

    printf '%s ' "${prompt_message}"
    read -r entered_value

    if [[ "${entered_value}" != "${expected_value}" ]]; then
        die "Confirmation did not match. Operation cancelled."
    fi
}


# ------------------------------------------------------------------------------
# Locking helpers
# ------------------------------------------------------------------------------

acquire_script_lock() {
    local lock_name="${1:?Lock name is required}"
    local lock_directory="${PROJECT_ROOT}/storage/framework"
    local lock_file="${lock_directory}/${lock_name}.lock"

    require_command flock

    mkdir -p -- "${lock_directory}"

    # File descriptor 200 remains open until the calling script exits.
    exec 200>"${lock_file}"

    if ! flock --nonblock 200; then
        die "Another '${lock_name}' operation is already running."
    fi
}


# ------------------------------------------------------------------------------
# Direct execution guard
# ------------------------------------------------------------------------------

if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    log_info "Validating the production scripting environment..."

    validate_production_environment

    log_success "Production scripting environment is valid."
    log_info "Project root: ${PROJECT_ROOT}"
    log_info "Environment file: ${PRODUCTION_ENV_FILE}"
    log_info "Compose file: ${PRODUCTION_COMPOSE_FILE}"
fi
