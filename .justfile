export UID := `id -u`
export GID := `id -g`
base_container_name := 'solaccess_backoffice'
php_container_name := base_container_name + '_php'
nginx_container_name := base_container_name + '_nginx'
mysql_container_name := base_container_name + '_mysql'

default:
    @just --list

[doc('Install dependencies inside a running PHP container')]
[group('docker')]
install-deps:
    docker exec -it {{ php_container_name }} sh -c "composer install"

[group('docker')]
build-dev:
    docker compose --env-file .env --env-file .env.local -f compose.dev.yaml build

[group('docker')]
run-dev:
    docker compose --env-file .env --env-file .env.local -f compose.dev.yaml up -d

[group('docker')]
down-dev:
    docker compose -f compose.dev.yaml down

[group('docker')]
exec service:
    docker exec -it {{ base_container_name + '_' + service }} sh

[group('docker')]
migrate:
    docker exec -it {{ php_container_name }} sh -c "php bin/console doctrine:schema:update --force && exit"

[group('docker')]
fixtures-load:
    docker exec -it {{ php_container_name }} sh -c "php bin/console doctrine:fixtures:load && exit"

[group('docker')]
mysql-export dest:
    docker exec {{ mysql_container_name }} sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > {{ dest }}

[group('docker')]
mysql-import src:
    docker exec -i {{ mysql_container_name }} sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < {{ src }}

[group('docker')]
run-scheduler:
    docker exec -i {{ php_container_name }} sh -c 'php bin/console messenger:consume scheduler_default'
