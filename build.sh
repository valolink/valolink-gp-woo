#!/usr/bin/env bash
#
# Build a distributable plugin zip in dist/.
#
# The archive contains a top-level `valolink-gp-woo/` folder so WordPress installs/updates
# it under the correct slug. Only runtime files are included (no build/dev cruft).
#
# Usage: ./build.sh
# Output: dist/valolink-gp-woo-<version>.zip

set -euo pipefail

SLUG="valolink-gp-woo"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN="$ROOT/$SLUG.php"

# Read "Version:" from the plugin header.
VERSION="$(grep -iE '^[[:space:]]*\*[[:space:]]*Version:' "$MAIN" | head -1 | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
if [ -z "$VERSION" ]; then
  echo "error: could not read Version from $MAIN" >&2
  exit 1
fi

DIST="$ROOT/dist"
STAGE="$(mktemp -d)"
DEST="$STAGE/$SLUG"
trap 'rm -rf "$STAGE"' EXIT
mkdir -p "$DEST" "$DIST"

# Whitelist: only ship runtime files.
cp "$MAIN" "$DEST/"
cp "$ROOT/uninstall.php" "$DEST/"
cp -R "$ROOT/src" "$DEST/"
[ -f "$ROOT/readme.txt" ] && cp "$ROOT/readme.txt" "$DEST/"

ZIP="$DIST/$SLUG-$VERSION.zip"
rm -f "$ZIP"
( cd "$STAGE" && zip -rq "$ZIP" "$SLUG" -x '*.DS_Store' )

echo "Built $ZIP"
( cd "$STAGE" && unzip -l "$ZIP" | awk 'NR>3 && $4 {print "  " $4}' | grep -v '^  ---' || true )
