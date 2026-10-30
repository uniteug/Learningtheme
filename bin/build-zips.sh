#!/usr/bin/env bash
# Pacchetti installabili : tema, plugin compagno, configurazione noigui e
# installatore del tema (per i siti dove Aspetto › Temi › Carica non è disponibile).
set -euo pipefail
R=$(cd "$(dirname "$0")/.." && pwd)
OUT=${1:-$R/dist}
T=$(mktemp -d)
trap 'rm -rf "$T"' EXIT
mkdir -p "$OUT"
rm -f "$OUT"/*.zip

pack() { # cartella sorgente, nome della cartella nel pacchetto, zip
	rm -rf "${T:?}/$2" && cp -r "$1" "$T/$2"
	find "$T/$2" \( -name '.DS_Store' -o -name '*.log' -o -name 'node_modules' \) -prune -exec rm -rf {} +
	(cd "$T" && zip -qr -X "$3" "$2")
}

pack "$R/learn-drive" learn-drive "$OUT/learn-drive.zip"
pack "$R/learn-drive-core" learn-drive-core "$OUT/learn-drive-core.zip"
pack "$R/sites/noigui-setup" noigui-setup "$OUT/noigui-setup.zip"
rm -rf "$T/learn-drive-theme-installer"
cp -r "$R/sites/learn-drive-theme-installer" "$T/"
cp "$OUT/learn-drive.zip" "$T/learn-drive-theme-installer/"
(cd "$T" && zip -qr -X "$OUT/learn-drive-theme-installer.zip" learn-drive-theme-installer)
ls -la "$OUT"
