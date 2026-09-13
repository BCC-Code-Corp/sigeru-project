#!/bin/sh
set -eu
umask 077
mkdir -p output/backups
destino="output/backups/sigeru-$(date +%Y%m%d-%H%M%S).sql"
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump --single-transaction --no-tablespaces -uroot "$MYSQL_DATABASE"' > "$destino"
test -s "$destino"
printf 'Respaldo creado: %s\n' "$destino"
