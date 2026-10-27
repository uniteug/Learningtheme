#!/usr/bin/env sh
# Minifie les modules JavaScript du plugin (esbuild).
# Usage : ESBUILD=/chemin/vers/esbuild sh bin/build-js.sh
set -e
ESBUILD="${ESBUILD:-npx --yes esbuild@0.25}"
DIR="$(dirname "$0")/../learn-drive-core/assets/js"
for name in quiz account video; do
	$ESBUILD "$DIR/$name.js" --minify --format=esm --target=es2020 --outfile="$DIR/$name.min.js" --log-level=warning
done
$ESBUILD "$DIR/../../../learn-drive/assets/js/theme-toggle.js" --minify --target=es2017 --outfile="$DIR/../../../learn-drive/assets/js/theme-toggle.min.js" --log-level=warning
ls -l "$DIR"/*.min.js
