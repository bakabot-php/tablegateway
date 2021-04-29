ci: csdiff database psalm tests cleanup

cleanup:
	docker-compose down -v

csdiff: vendor
	docker-compose run --rm php vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

csfix: vendor
	docker-compose run --rm php vendor/bin/php-cs-fixer fix

database:
	$(MAKE) cleanup
	docker-compose up -d mariadb mysql postgres

psalm: vendor
	docker-compose run --rm php vendor/bin/psalm --shepherd

tests: vendor database
	docker-compose run --rm php -dxdebug.mode=coverage vendor/bin/phpunit

vendor: composer.json
	docker-compose run --rm composer validate
	docker-compose run --rm composer install --quiet --no-cache
