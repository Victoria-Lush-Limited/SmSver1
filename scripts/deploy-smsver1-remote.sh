#!/usr/bin/env bash
# Deploy SmSver1 (SMS portal) over SSH: rsync if available, else tar stream (same idea as vll_backend/scripts/deploy-backend-remote.sh).
#
# Env: VLL_DEPLOY_HOST, VLL_DEPLOY_USER, VLL_DEPLOY_KEY, optional VLL_DEPLOY_PORT, VLL_DEPLOY_SMS_PATH
# Optional file: scripts/.deploy.env (same keys as backend).

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

ENV_FILE="$(dirname "${BASH_SOURCE[0]}")/.deploy.env"
if [[ -f "$ENV_FILE" ]]; then
  set -a
  # shellcheck source=/dev/null
  source "$ENV_FILE"
  set +a
fi

: "${VLL_DEPLOY_HOST:?Set VLL_DEPLOY_HOST}"
: "${VLL_DEPLOY_USER:?Set VLL_DEPLOY_USER}"
: "${VLL_DEPLOY_KEY:?Set VLL_DEPLOY_KEY}"

PORT="${VLL_DEPLOY_PORT:-22}"
REMOTE_PATH="${VLL_DEPLOY_SMS_PATH:-/var/www/public_html/victorialush/sms/}"

if [[ ! -f "$VLL_DEPLOY_KEY" ]]; then
  echo "Key file not found: $VLL_DEPLOY_KEY" >&2
  exit 1
fi

chmod 600 "$VLL_DEPLOY_KEY" 2>/dev/null || true
SSH=(ssh -i "$VLL_DEPLOY_KEY" -p "${PORT}" -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new -o BatchMode=yes -o ConnectTimeout=30)

if command -v rsync >/dev/null 2>&1; then
  RSYNC_RSH="ssh -i \"$VLL_DEPLOY_KEY\" -p ${PORT} -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new -o BatchMode=yes -o ConnectTimeout=30"
  echo "==> [SmSver1] rsync -> ${VLL_DEPLOY_USER}@${VLL_DEPLOY_HOST}:${REMOTE_PATH}"
  rsync -az --delete \
    --exclude '.git/' \
    --exclude 'uploads/' \
    -e "$RSYNC_RSH" \
    ./ "${VLL_DEPLOY_USER}@${VLL_DEPLOY_HOST}:${REMOTE_PATH}"
else
  echo "==> [SmSver1] tar over ssh -> ${VLL_DEPLOY_USER}@${VLL_DEPLOY_HOST}:${REMOTE_PATH}"
  tar -cf - \
    --exclude='./.git' \
    --exclude='./uploads' \
    . | "${SSH[@]}" "${VLL_DEPLOY_USER}@${VLL_DEPLOY_HOST}" \
    "set -e; mkdir -p '${REMOTE_PATH}' && cd '${REMOTE_PATH}' && tar -xf -"
fi

echo "==> [SmSver1] best-effort web reload (ignore errors)"
"${SSH[@]}" "${VLL_DEPLOY_USER}@${VLL_DEPLOY_HOST}" \
  "systemctl reload apache2 2>/dev/null || systemctl reload httpd 2>/dev/null || service apache2 reload 2>/dev/null || service httpd reload 2>/dev/null || true" \
  || true

echo "==> [SmSver1] done"
