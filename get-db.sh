#!/usr/bin/env bash
# Dump the remote database with drush into dump/database.sql.gz.
# Usage: ./get-db.sh
# Override with COVE_SSH (ssh target) and COVE_ROOT (remote project root).
set -euo pipefail

SSH_TARGET="${COVE_SSH:-jmlavarenne@165.227.189.239}"
REMOTE_ROOT="${COVE_ROOT:-/var/www/cove-editions}"

cd "$(dirname "$0")"
mkdir -p dump
out="dump/database.sql.gz"
tmp="$out.part"
trap 'rm -f "$tmp"' EXIT

ssh "$SSH_TARGET" "set -o pipefail; cd $(printf %q "$REMOTE_ROOT") && vendor/bin/drush sql:dump --structure-tables-key=common --extra-dump=--no-tablespaces | gzip -c" > "$tmp"
gzip -t "$tmp"
mv "$tmp" "$out"
echo "Saved $out ($(du -h "$out" | cut -f1))"
