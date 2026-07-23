#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.production.yml"
ENV_FILE="${PROJECT_ROOT}/.env.production"

CERTBOT_IMAGE="certbot/certbot:latest"

CERTBOT_WEBROOT="${PROJECT_ROOT}/docker/production/certbot/www"
CERTBOT_CONFIG="${PROJECT_ROOT}/docker/production/certbot/conf"

# ينشئه Certbot فقط عندما يتم تجديد شهادة فعلًا.
RENEWED_MARKER="${CERTBOT_WEBROOT}/.certificate-renewed"

log() {
    printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

fail() {
    log "ERROR: $*"
    exit 1
}

command -v docker >/dev/null 2>&1 ||
    fail "Docker is not installed or is not available in PATH."

[[ -f "${COMPOSE_FILE}" ]] ||
    fail "Production Compose file not found: ${COMPOSE_FILE}"

[[ -f "${ENV_FILE}" ]] ||
    fail "Production environment file not found: ${ENV_FILE}"

[[ -d "${CERTBOT_WEBROOT}" ]] ||
    fail "Certbot webroot directory not found: ${CERTBOT_WEBROOT}"

[[ -d "${CERTBOT_CONFIG}" ]] ||
    fail "Certbot configuration directory not found: ${CERTBOT_CONFIG}"

cd "${PROJECT_ROOT}"

# نحذف علامة أي عملية تجديد سابقة.
rm -f "${RENEWED_MARKER}"

log "Checking Let's Encrypt certificates for renewal..."

docker run --rm \
    -v "${CERTBOT_WEBROOT}:/var/www/certbot" \
    -v "${CERTBOT_CONFIG}:/etc/letsencrypt" \
    "${CERTBOT_IMAGE}" renew \
    --webroot \
    --webroot-path /var/www/certbot \
    --quiet \
    --deploy-hook "touch /var/www/certbot/.certificate-renewed"

if [[ -f "${RENEWED_MARKER}" ]]; then
    log "Certificate renewed successfully. Reloading Nginx..."

    docker compose \
        --env-file "${ENV_FILE}" \
        -f "${COMPOSE_FILE}" \
        exec -T nginx nginx -t

    docker compose \
        --env-file "${ENV_FILE}" \
        -f "${COMPOSE_FILE}" \
        exec -T nginx nginx -s reload

    rm -f "${RENEWED_MARKER}"

    log "Nginx reloaded successfully without downtime."
else
    log "Certificate renewal was not required."
fi
