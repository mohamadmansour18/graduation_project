#!/usr/bin/env bash

# ==============================================================================
# Graduation Project - Production Artisan Wrapper
# ==============================================================================
#
# Runs Laravel Artisan commands inside the production application container.
#
# Examples:
#
#   ./scripts/production/artisan.sh route:list
#   ./scripts/production/artisan.sh migrate --force
#   ./scripts/production/artisan.sh config:show app
#   ./scripts/production/artisan.sh queue:restart
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
  artisan.sh <artisan-command> [arguments...]

Examples:
  artisan.sh route:list
  artisan.sh migrate --force
  artisan.sh optimize
  artisan.sh queue:restart
  artisan.sh scout:import "App\Models\Test"
EOF
}


# ------------------------------------------------------------------------------
# Argument validation
# ------------------------------------------------------------------------------

if [[ $# -eq 0 ]]; then
    show_usage
    die "An Artisan command is required."
fi

case "${1}" in
    -h|--help)
        show_usage
        exit 0
        ;;
esac


# ------------------------------------------------------------------------------
# Environment validation
# ------------------------------------------------------------------------------

validate_production_environment
require_service app
require_running_service app


# ------------------------------------------------------------------------------
# Execute Artisan
# ------------------------------------------------------------------------------

log_info "Running Artisan command inside the production app container:"
printf '  php artisan'
printf ' %q' "$@"
printf '\n'

production_compose exec \
    --no-TTY \
    app \
    php artisan "$@"

log_success "Artisan command completed successfully."
