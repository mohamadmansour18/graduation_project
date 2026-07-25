#!/usr/bin/env bash

# ==============================================================================
# Graduation Project - Production Health Check
# ==============================================================================
#
# Performs read-only health checks for the production environment.
#
# Checks:
#   1. Docker and Docker Compose configuration.
#   2. Required container states and Docker health checks.
#   3. Laravel application bootstrap.
#   4. Database connectivity.
#   5. Redis connectivity.
#   6. Meilisearch connectivity.
#   7. Horizon runtime status.
#   8. Failed queue jobs.
#   9. Public HTTPS application endpoint.
#  10. Public phpMyAdmin HTTPS and Basic Auth endpoint.
#
# Exit codes:
#   0 - All critical checks passed.
#   1 - One or more critical checks failed.
#
# Usage:
#   ./scripts/production/health.sh
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

readonly APP_SERVICE="app"
readonly PHPMYADMIN_SERVICE="phpmyadmin"

readonly -a REQUIRED_SERVICES=(
    "app"
    "nginx"
    "mysql"
    "redis"
    "meilisearch"
    "reverb"
    "horizon"
    "scheduler"
    "phpmyadmin"
)

# Optional services are checked only when they exist in the Compose file.
# Their failure produces a warning rather than making the complete health check
# fail.
readonly -a OPTIONAL_SERVICES=(
    "ollama"
)

readonly HTTP_CONNECT_TIMEOUT_SECONDS=5
readonly HTTP_MAX_TIME_SECONDS=15
readonly PHPMYADMIN_PUBLIC_URL="${PHPMYADMIN_PUBLIC_URL:-https://db.nerdsoftwares.tech}"

CRITICAL_FAILURES=0
WARNINGS=0


# ------------------------------------------------------------------------------
# Report helpers
# ------------------------------------------------------------------------------

print_separator() {
    printf '%s\n' \
        '==============================================================================='
}

report_pass() {
    local check_name="${1:?Check name is required}"
    local details="${2:-OK}"

    printf '%s[PASS]%s %-32s %s\n' \
        "${COLOR_GREEN}" \
        "${COLOR_RESET}" \
        "${check_name}" \
        "${details}"
}

report_warning() {
    local check_name="${1:?Check name is required}"
    local details="${2:-Warning}"

    WARNINGS=$((WARNINGS + 1))

    printf '%s[WARN]%s %-32s %s\n' \
        "${COLOR_YELLOW}" \
        "${COLOR_RESET}" \
        "${check_name}" \
        "${details}" >&2
}

report_fail() {
    local check_name="${1:?Check name is required}"
    local details="${2:-Failed}"

    CRITICAL_FAILURES=$((CRITICAL_FAILURES + 1))

    printf '%s[FAIL]%s %-32s %s\n' \
        "${COLOR_RED}" \
        "${COLOR_RESET}" \
        "${check_name}" \
        "${details}" >&2
}


# ------------------------------------------------------------------------------
# Docker helpers
# ------------------------------------------------------------------------------

get_service_container_id() {
    local service_name="${1:?Service name is required}"

    production_compose ps \
        --quiet \
        "${service_name}" 2>/dev/null |
        head -n 1
}

get_container_status() {
    local container_id="${1:?Container ID is required}"

    docker inspect \
        --format '{{.State.Status}}' \
        "${container_id}" 2>/dev/null
}

get_container_health_status() {
    local container_id="${1:?Container ID is required}"

    docker inspect \
        --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' \
        "${container_id}" 2>/dev/null
}


# ------------------------------------------------------------------------------
# Laravel PHP execution
# ------------------------------------------------------------------------------

run_laravel_php() {
    local php_code="${1:?PHP code is required}"

    production_compose exec \
        --no-TTY \
        "${APP_SERVICE}" \
        php -r "
            require '/var/www/html/vendor/autoload.php';

            \$app = require '/var/www/html/bootstrap/app.php';

            \$kernel = \$app->make(
                Illuminate\Contracts\Console\Kernel::class
            );

            \$kernel->bootstrap();

            ${php_code}
        "
}


# ------------------------------------------------------------------------------
# Docker service checks
# ------------------------------------------------------------------------------

check_required_service() {
    local service_name="${1:?Service name is required}"

    if ! service_exists "${service_name}"; then
        report_fail \
            "Service: ${service_name}" \
            "Not defined in Docker Compose."

        return
    fi

    local container_id
    container_id="$(get_service_container_id "${service_name}")"

    if [[ -z "${container_id}" ]]; then
        report_fail \
            "Service: ${service_name}" \
            "Container does not exist."

        return
    fi

    local container_status
    container_status="$(get_container_status "${container_id}")"

    if [[ "${container_status}" != "running" ]]; then
        report_fail \
            "Service: ${service_name}" \
            "Container status is '${container_status}'."

        return
    fi

    local health_status
    health_status="$(get_container_health_status "${container_id}")"

    case "${health_status}" in
        healthy)
            report_pass \
                "Service: ${service_name}" \
                "Running and healthy."
            ;;

        none)
            report_pass \
                "Service: ${service_name}" \
                "Running; no Docker health check is configured."
            ;;

        starting)
            report_warning \
                "Service: ${service_name}" \
                "Running, but its health check is still starting."
            ;;

        unhealthy)
            report_fail \
                "Service: ${service_name}" \
                "Running, but Docker reports it as unhealthy."
            ;;

        *)
            report_warning \
                "Service: ${service_name}" \
                "Running with unknown health status '${health_status}'."
            ;;
    esac
}

check_optional_service() {
    local service_name="${1:?Service name is required}"

    if ! service_exists "${service_name}"; then
        return 0
    fi

    local container_id
    container_id="$(get_service_container_id "${service_name}")"

    if [[ -z "${container_id}" ]]; then
        report_warning \
            "Optional: ${service_name}" \
            "Container does not exist."

        return
    fi

    local container_status
    container_status="$(get_container_status "${container_id}")"

    if [[ "${container_status}" != "running" ]]; then
        report_warning \
            "Optional: ${service_name}" \
            "Container status is '${container_status}'."

        return
    fi

    local health_status
    health_status="$(get_container_health_status "${container_id}")"

    case "${health_status}" in
        healthy)
            report_pass \
                "Optional: ${service_name}" \
                "Running and healthy."
            ;;

        none)
            report_pass \
                "Optional: ${service_name}" \
                "Running; no Docker health check is configured."
            ;;

        *)
            report_warning \
                "Optional: ${service_name}" \
                "Running with health status '${health_status}'."
            ;;
    esac
}

check_docker_services() {
    local service_name

    for service_name in "${REQUIRED_SERVICES[@]}"; do
        check_required_service "${service_name}"
    done

    for service_name in "${OPTIONAL_SERVICES[@]}"; do
        check_optional_service "${service_name}"
    done
}


# ------------------------------------------------------------------------------
# Laravel application check
# ------------------------------------------------------------------------------

check_laravel_application() {
    if ! service_is_running "${APP_SERVICE}"; then
        report_fail \
            "Laravel application" \
            "The app service is not running."

        return
    fi

    local output

    if ! output="$(
        production_compose exec \
            --no-TTY \
            "${APP_SERVICE}" \
            php artisan about \
            --no-interaction \
            --no-ansi 2>&1
    )"; then
        report_fail \
            "Laravel application" \
            "Artisan could not bootstrap the application."

        printf '%s\n' "${output}" >&2
        return
    fi

    report_pass \
        "Laravel application" \
        "Artisan bootstrapped successfully."
}


# ------------------------------------------------------------------------------
# Database check
# ------------------------------------------------------------------------------

check_database_connection() {
    if ! service_is_running "${APP_SERVICE}"; then
        report_fail \
            "Database connection" \
            "Cannot test because the app service is not running."

        return
    fi

    local output

    if ! output="$(
        run_laravel_php '
            try {
                Illuminate\Support\Facades\DB::connection()->getPdo();
                echo "connected";
            } catch (Throwable $exception) {
                fwrite(STDERR, $exception->getMessage());
                exit(1);
            }
        ' 2>&1
    )"; then
        report_fail \
            "Database connection" \
            "Laravel could not connect to the database."

        printf '%s\n' "${output}" >&2
        return
    fi

    if [[ "${output}" != *"connected"* ]]; then
        report_fail \
            "Database connection" \
            "Unexpected database check response."

        return
    fi

    report_pass \
        "Database connection" \
        "Laravel connected successfully."
}


# ------------------------------------------------------------------------------
# Redis check
# ------------------------------------------------------------------------------

check_redis_connection() {
    if ! service_is_running "${APP_SERVICE}"; then
        report_fail \
            "Redis connection" \
            "Cannot test because the app service is not running."

        return
    fi

    local output

    if ! output="$(
        run_laravel_php '
            try {
                $response = Illuminate\Support\Facades\Redis::connection()->ping();

                if (is_object($response)) {
                    if (method_exists($response, "getPayload")) {
                        $response = $response->getPayload();
                    } elseif (method_exists($response, "__toString")) {
                        $response = (string) $response;
                    }
                }

                if ($response === true) {
                    echo "pong";
                    exit(0);
                }

                $normalizedResponse = strtoupper(
                    trim((string) $response)
                );

                if (
                    $normalizedResponse === "PONG" ||
                    $normalizedResponse === "+PONG"
                ) {
                    echo "pong";
                    exit(0);
                }

                fwrite(
                    STDERR,
                    "Unexpected Redis PING response: " .
                    var_export($response, true)
                );

                exit(1);
            } catch (Throwable $exception) {
                fwrite(STDERR, $exception->getMessage());
                exit(1);
            }
        ' 2>&1
    )"; then
        report_fail \
            "Redis connection" \
            "Laravel could not communicate with Redis."

        printf '%s\n' "${output}" >&2
        return
    fi

    if [[ "${output}" != *"pong"* ]]; then
        report_fail \
            "Redis connection" \
            "Unexpected Redis check response."

        printf '%s\n' "${output}" >&2
        return
    fi

    report_pass \
        "Redis connection" \
        "Redis responded to PING."
}


# ------------------------------------------------------------------------------
# Meilisearch check
# ------------------------------------------------------------------------------

check_meilisearch_connection() {
    if ! service_is_running "${APP_SERVICE}"; then
        report_fail \
            "Meilisearch connection" \
            "Cannot test because the app service is not running."

        return
    fi

    local output

    if ! output="$(
        run_laravel_php '
            try {
                $host = rtrim(
                    (string) config("scout.meilisearch.host"),
                    "/"
                );

                if ($host === "") {
                    fwrite(
                        STDERR,
                        "Meilisearch host is not configured."
                    );

                    exit(1);
                }

                $context = stream_context_create([
                    "http" => [
                        "method" => "GET",
                        "timeout" => 5,
                        "ignore_errors" => true,
                    ],
                ]);

                $response = @file_get_contents(
                    $host . "/health",
                    false,
                    $context
                );

                if ($response === false) {
                    fwrite(
                        STDERR,
                        "Could not reach the Meilisearch health endpoint."
                    );

                    exit(1);
                }

                $payload = json_decode($response, true);

                if (
                    !is_array($payload) ||
                    ($payload["status"] ?? null) !== "available"
                ) {
                    fwrite(
                        STDERR,
                        "Unexpected Meilisearch health response: " .
                        $response
                    );

                    exit(1);
                }

                echo "available";
            } catch (Throwable $exception) {
                fwrite(STDERR, $exception->getMessage());
                exit(1);
            }
        ' 2>&1
    )"; then
        report_fail \
            "Meilisearch connection" \
            "Laravel could not reach Meilisearch."

        printf '%s\n' "${output}" >&2
        return
    fi

    if [[ "${output}" != *"available"* ]]; then
        report_fail \
            "Meilisearch connection" \
            "Unexpected Meilisearch check response."

        return
    fi

    report_pass \
        "Meilisearch connection" \
        "Health endpoint reports available."
}


# ------------------------------------------------------------------------------
# Horizon check
# ------------------------------------------------------------------------------

check_horizon_status() {
    if ! service_is_running "horizon"; then
        report_fail \
            "Horizon status" \
            "The Horizon service is not running."

        return
    fi

    local output

    if ! output="$(
        production_compose exec \
            --no-TTY \
            "${APP_SERVICE}" \
            php artisan horizon:status \
            --no-interaction \
            --no-ansi 2>&1
    )"; then
        report_fail \
            "Horizon status" \
            "Could not retrieve Horizon status."

        printf '%s\n' "${output}" >&2
        return
    fi

    if grep --ignore-case --quiet 'running' <<<"${output}"; then
        report_pass \
            "Horizon status" \
            "Horizon reports running."
    else
        report_fail \
            "Horizon status" \
            "Horizon does not report a running state."

        printf '%s\n' "${output}" >&2
    fi
}


# ------------------------------------------------------------------------------
# Failed jobs check
# ------------------------------------------------------------------------------

check_failed_jobs() {
    if ! service_is_running "${APP_SERVICE}"; then
        report_warning \
            "Failed queue jobs" \
            "Cannot count because the app service is not running."

        return
    fi

    local output

    if ! output="$(
        run_laravel_php '
            try {
                if (
                    !Illuminate\Support\Facades\Schema::hasTable(
                        "failed_jobs"
                    )
                ) {
                    echo "table-missing";
                    exit(0);
                }

                echo (string) Illuminate\Support\Facades\DB::table(
                    "failed_jobs"
                )->count();
            } catch (Throwable $exception) {
                fwrite(STDERR, $exception->getMessage());
                exit(1);
            }
        ' 2>&1
    )"; then
        report_warning \
            "Failed queue jobs" \
            "Could not query the failed_jobs table."

        printf '%s\n' "${output}" >&2
        return
    fi

    if [[ "${output}" == *"table-missing"* ]]; then
        report_warning \
            "Failed queue jobs" \
            "The failed_jobs table does not exist."

        return
    fi

    local failed_jobs_count
    failed_jobs_count="$(
        printf '%s' "${output}" |
            grep --only-matching --extended-regexp '[0-9]+' |
            tail -n 1
    )"

    if [[ -z "${failed_jobs_count}" ]]; then
        report_warning \
            "Failed queue jobs" \
            "Unexpected failed-job count response."

        return
    fi

    if ((failed_jobs_count == 0)); then
        report_pass \
            "Failed queue jobs" \
            "0 failed jobs."
    else
        report_warning \
            "Failed queue jobs" \
            "${failed_jobs_count} failed job(s) require attention."
    fi
}


# ------------------------------------------------------------------------------
# Public HTTPS check
# ------------------------------------------------------------------------------

get_application_url() {
    run_laravel_php '
        echo rtrim((string) config("app.url"), "/");
    '
}

check_public_application_url() {
    require_command curl

    if ! service_is_running "${APP_SERVICE}"; then
        report_fail \
            "Public HTTPS endpoint" \
            "Cannot determine APP_URL because the app service is not running."

        return
    fi

    local application_url

    if ! application_url="$(get_application_url 2>/dev/null)"; then
        report_fail \
            "Public HTTPS endpoint" \
            "Could not determine Laravel APP_URL."

        return
    fi

    application_url="$(
        printf '%s' "${application_url}" |
            tail -n 1 |
            tr -d '\r'
    )"

    if [[ -z "${application_url}" ]]; then
        report_fail \
            "Public HTTPS endpoint" \
            "Laravel APP_URL is empty."

        return
    fi

    if [[ "${application_url}" != https://* ]]; then
        report_warning \
            "Public HTTPS endpoint" \
            "APP_URL does not use HTTPS: ${application_url}"
    fi

    local http_status

    if ! http_status="$(
        curl \
            --silent \
            --show-error \
            --output /dev/null \
            --write-out '%{http_code}' \
            --connect-timeout "${HTTP_CONNECT_TIMEOUT_SECONDS}" \
            --max-time "${HTTP_MAX_TIME_SECONDS}" \
            "${application_url}"
    )"; then
        report_fail \
            "Public HTTPS endpoint" \
            "Could not connect to ${application_url}."

        return
    fi

    case "${http_status}" in
        2??|3??|401|403|404|405)
            report_pass \
                "Public HTTPS endpoint" \
                "${application_url} returned HTTP ${http_status}."
            ;;

        *)
            report_fail \
                "Public HTTPS endpoint" \
                "${application_url} returned HTTP ${http_status}."
            ;;
    esac
}

# ------------------------------------------------------------------------------
# Public phpMyAdmin HTTPS and Basic Auth check
# ------------------------------------------------------------------------------

check_public_phpmyadmin_url() {
    require_command curl

    if ! service_is_running "${PHPMYADMIN_SERVICE}"; then
        report_fail \
            "phpMyAdmin HTTPS endpoint" \
            "The phpMyAdmin service is not running."

        return
    fi

    if ! service_is_running "nginx"; then
        report_fail \
            "phpMyAdmin HTTPS endpoint" \
            "Cannot test because the Nginx service is not running."

        return
    fi

    if [[ "${PHPMYADMIN_PUBLIC_URL}" != https://* ]]; then
        report_fail \
            "phpMyAdmin HTTPS endpoint" \
            "The configured URL does not use HTTPS: ${PHPMYADMIN_PUBLIC_URL}"

        return
    fi

    local response_headers_file
    response_headers_file="$(mktemp)"

    local http_status

    if ! http_status="$(
        curl \
            --silent \
            --show-error \
            --output /dev/null \
            --dump-header "${response_headers_file}" \
            --write-out '%{http_code}' \
            --connect-timeout "${HTTP_CONNECT_TIMEOUT_SECONDS}" \
            --max-time "${HTTP_MAX_TIME_SECONDS}" \
            "${PHPMYADMIN_PUBLIC_URL}"
    )"; then
        rm -f "${response_headers_file}"

        report_fail \
            "phpMyAdmin HTTPS endpoint" \
            "Could not connect to ${PHPMYADMIN_PUBLIC_URL}."

        return
    fi

    if [[ "${http_status}" != "401" ]]; then
        rm -f "${response_headers_file}"

        report_fail \
            "phpMyAdmin HTTPS endpoint" \
            "Expected HTTP 401 from Basic Auth, but received HTTP ${http_status}."

        return
    fi

    if ! grep \
        --ignore-case \
        --quiet \
        '^WWW-Authenticate:[[:space:]]*Basic' \
        "${response_headers_file}"; then

        rm -f "${response_headers_file}"

        report_fail \
            "phpMyAdmin Basic Auth" \
            "HTTP 401 was returned without a Basic authentication challenge."

        return
    fi

    rm -f "${response_headers_file}"

    report_pass \
        "phpMyAdmin HTTPS endpoint" \
        "${PHPMYADMIN_PUBLIC_URL} returned HTTP 401 with Basic Auth enabled."
}

# ------------------------------------------------------------------------------
# Main
# ------------------------------------------------------------------------------

main() {
    print_separator
    printf 'Production Health Report\n'
    printf 'Generated at: %s\n' "$(timestamp)"
    print_separator

    log_info "Validating production environment..."

    if ! validate_production_environment; then
        report_fail \
            "Production environment" \
            "Base production validation failed."

        print_separator
        printf 'Overall status: %sUNHEALTHY%s\n' \
            "${COLOR_RED}" \
            "${COLOR_RESET}"
        print_separator

        exit 1
    fi

    report_pass \
        "Production environment" \
        "Docker and Compose configuration are valid."

    check_docker_services

    check_laravel_application
    check_database_connection
    check_redis_connection
    check_meilisearch_connection
    check_horizon_status
    check_failed_jobs
    check_public_application_url
    check_public_phpmyadmin_url

    print_separator

    printf 'Critical failures: %d\n' "${CRITICAL_FAILURES}"
    printf 'Warnings:          %d\n' "${WARNINGS}"

    if ((CRITICAL_FAILURES > 0)); then
        printf 'Overall status:    %sUNHEALTHY%s\n' \
            "${COLOR_RED}" \
            "${COLOR_RESET}"

        print_separator
        exit 1
    fi

    if ((WARNINGS > 0)); then
        printf 'Overall status:    %sHEALTHY WITH WARNINGS%s\n' \
            "${COLOR_YELLOW}" \
            "${COLOR_RESET}"
    else
        printf 'Overall status:    %sHEALTHY%s\n' \
            "${COLOR_GREEN}" \
            "${COLOR_RESET}"
    fi

    print_separator
}

main "$@"
