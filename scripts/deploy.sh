#!/usr/bin/env bash
# Uploads the app to the school server (KitLab) over SFTP.
#
# First open one SSH connection and type the password (it stays open for 2 hours):
#   ssh -fNM -o ControlPath=~/.ssh/kitlab.sock -o ControlPersist=2h -p 56789 LOGIN@ete89e.pef.czu.cz
#
# MariaDB (default): copy .env.server.example to .env.server and fill in the values from kitlab_db.php.
# Import database/seed.sql in Adminer once to create the tables with demo data.
#
# Usage:
#   scripts/deploy.sh LOGIN                     MariaDB, code only
#   scripts/deploy.sh LOGIN --json              JSON files instead of MariaDB, live data stays untouched
#   scripts/deploy.sh LOGIN --json --with-data  JSON files with fresh demo data (overwrites users and items!)
set -euo pipefail

LOGIN=${1:?Usage: scripts/deploy.sh LOGIN [--json [--with-data]]}
shift
DRIVER=mysql
WITH_DATA=false
for option in "$@"; do
    case "$option" in
        --json) DRIVER=json ;;
        --with-data) WITH_DATA=true ;;
        *) echo "Unknown option $option" >&2; exit 1 ;;
    esac
done

HOST=ete89e.pef.czu.cz
PORT=56789
REMOTE=/var/www/html/zs2627/07
BASE_PATH=/zs2627/07
# Origin only, the app adds BASE_PATH to every link
APP_URL=https://$HOST
SOCKET=~/.ssh/kitlab.sock

ROOT=$(cd "$(dirname "$0")/.." && pwd)
SERVER_ENV="$ROOT/.env.server"
STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"' EXIT

if [[ "$DRIVER" == mysql && ! -f "$SERVER_ENV" ]]; then
    echo "Missing .env.server with the database login, see .env.server.example" >&2
    exit 1
fi

if [[ "$DRIVER" == mysql && "$WITH_DATA" == true ]]; then
    echo "With MariaDB, import database/seed.sql in Adminer instead of --with-data" >&2
    exit 1
fi

if ! ssh -O check -o ControlPath="$SOCKET" x 2>/dev/null; then
    echo "No open SSH connection, open it first (in Warp prefix it with 'command'):" >&2
    echo "  ssh -fNM -o ControlPath=$SOCKET -o ControlPersist=2h -p $PORT $LOGIN@$HOST" >&2
    exit 1
fi

sftp_batch() {
    sftp -q -o ControlPath="$SOCKET" -P "$PORT" -b - "$LOGIN@$HOST"
}

echo "Building assets"
# Vite needs the base path for lazy-loaded chunks and fonts
BASE_PATH=$BASE_PATH npm --prefix "$ROOT" run build >/dev/null

echo "Preparing files"
git -C "$ROOT" archive HEAD | tar -x -C "$STAGE"
cp -R "$ROOT/public/build" "$STAGE/public/"
rm -rf "$STAGE/tests" "$STAGE/docs" "$STAGE/scripts" "$STAGE/assets" \
    "$STAGE"/{package.json,package-lock.json,vite.config.js,composer.json,composer.lock,phpunit.xml,CLAUDE.md,README.md,.gitignore,.editorconfig,.env.example,.env.testing.example,.env.server.example}

cat > "$STAGE/.env" <<EOF
APP_ENV=prod
APP_DEBUG=0
APP_URL=$APP_URL
APP_TIMEZONE=Europe/Prague
BASE_PATH=$BASE_PATH
DATA_DRIVER=$DRIVER
MAIL_DRIVER=mail
MAIL_FROM=noreply@$HOST
EOF

if [[ "$DRIVER" == mysql ]]; then
    grep -E '^DB_(HOST|NAME|USER|PASS)=' "$SERVER_ENV" >> "$STAGE/.env"
fi

if [[ "$WITH_DATA" == true ]]; then
    echo "Seeding demo data"
    DATA_DRIVER=json php "$ROOT/database/seed.php" >/dev/null
    cp "$ROOT"/storage/data/*.json "$STAGE/storage/data/"
fi

echo "Uploading to $REMOTE"
{
    echo "cd $REMOTE"
    echo "lcd $STAGE"
    for entry in .htaccess .env src templates database public storage; do
        [[ -d "$STAGE/$entry" ]] && echo "-mkdir $entry"
        echo "put -r $entry"
    done
    # Apache writes logs and photos (and the data with --json)
    for dir in storage storage/data storage/logs public/uploads public/uploads/items; do
        echo "-mkdir $dir"
        echo "chmod 777 $dir"
    done
    if [[ "$WITH_DATA" == true ]]; then
        for file in "$STAGE"/storage/data/*.json; do
            echo "chmod 666 storage/data/$(basename "$file")"
        done
    fi
} | sftp_batch

echo "Done: $APP_URL$BASE_PATH"
