analyze:
	vendor/bin/psalm --show-info=true

test:
	vendor/bin/phpunit

coverage:
	php -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover=build/logs/clover.xml