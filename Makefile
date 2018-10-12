# Utils

vendor: composer.json
	make -C $$PWD composer-install

.PHONY: composer-update
composer-update:
	docker-compose run --rm php -d memory_limit=-1 /usr/local/bin/composer update $(options)

.PHONY: composer-install
composer-install:
	docker-compose run --rm php composer install $(options)

# PHPUnit commands

.PHONY: phpunit
phpunit: vendor ./vendor/bin/phpunit ./phpunit.xml.dist
	docker-compose run --rm php ./vendor/bin/phpunit --coverage-text $(options)

.PHONY: phpunit-functional
phpunit-functional: vendor ./vendor/bin/phpunit ./phpunit_functional.xml.dist
	docker-compose run --rm php ./vendor/bin/phpunit -c phpunit_functional.xml.dist --coverage-text $(options)
