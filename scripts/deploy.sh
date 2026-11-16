#!/usr/bin/env bash
# Uploads the app to the school server (KitLab) over SFTP, with data stored in JSON files.
#
# First open one SSH connection and type the password (it stays open for 2 hours):
#   ssh -fNM -o ControlPath=~/.ssh/kitlab.sock -o ControlPersist=2h -p 56789 LOGIN@ete89e.pef.czu.cz
#
# Usage:
#   scripts/deploy.sh LOGIN              code only, live data stays untouched
#   scripts/deploy.sh LOGIN --with-data  also uploads fresh demo data (overwrites users and items!)
set -euo pipefail

LOGIN=${1:?Usage: scripts/deploy.sh LOGIN [--with-data]}
WITH_DATA=${2:-}
HOST=ete89e.pef.czu.cz
PORT=56789
REMOTE=/var/www/html/zs2627/07
APP_URL=https://$HOST/zs2627/07
SOCKET=~/.ssh/kitlab.sock

ROOT=$(cd "$(dirname "$0")/.." && pwd)
STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"' EXIT

sftp_batch() {
    sftp -q -o ControlPath="$SOCKET" -P "$PORT" -b - "$LOGIN@$HOST"
}

echo "Building assets"
npm --prefix "$ROOT" run build >/dev/null

echo "Preparing files"
git -C "$ROOT" archive HEAD | tar -x -C "$STAGE"
cp -R "$ROOT/public/build" "$STAGE/public/"
rm -rf "$STAGE/tests" "$STAGE/docs" "$STAGE/scripts" "$STAGE/assets" \
    "$STAGE"/{package.json,package-lock.json,vite.config.js,composer.json,composer.lock,phpunit.xml,CLAUDE.md,README.md,.gitignore,.editorconfig,.env.example,.env.testing.example}

cat > "$STAGE/.env" <<EOF
APP_ENV=prod
APP_DEBUG=0
APP_URL=$APP_URL
APP_TIMEZONE=Europe/Prague
BASE_PATH=/zs2627/07
DATA_DRIVER=json
MAIL_DRIVER=mail
MAIL_FROM=noreply@$HOST
EOF

if [[ "$WITH_DATA" == "--with-data" ]]; then
    echo "Seeding demo data"
    DATA_DRIVER=json php "$ROOT/database/seed.php" >/dev/null
    cp "$ROOT"/storage/data/*.json "$STAGE/storage/data/"
fi

echo "Uploading to $REMOTE"
{
    echo "cd $REMOTE"
    echo "lcd $STAGE"
    for entry in .htaccess .env src templates database public storage; do
        [[ -e "$STAGE/$entry" ]] || continue
        [[ -d "$STAGE/$entry" ]] && echo "-mkdir $entry"
        echo "put -r $entry"
    done
    # Apache writes JSON data, logs and photos
    for dir in storage storage/data storage/logs public/uploads public/uploads/items; do
        echo "-mkdir $dir"
        echo "chmod 777 $dir"
    done
    if [[ "$WITH_DATA" == "--with-data" ]]; then
        for file in "$STAGE"/storage/data/*.json; do
            echo "chmod 666 storage/data/$(basename "$file")"
        done
    fi
} | sftp_batch

echo "Done: $APP_URL"
