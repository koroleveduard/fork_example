up: docker-up
down: docker-down
init:
	docker-compose run --rm bothelp_php_cli composer install

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down -v

produce:
	docker-compose run --rm bothelp_php_cli php bin/console queue:produce

consume:
	docker-compose run --rm bothelp_php_cli php bin/console queue:consume