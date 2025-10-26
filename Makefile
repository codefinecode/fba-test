PHP=php
COMPOSER=composer
PHPUNIT=vendor/bin/phpunit
PHPSTAN=vendor/bin/phpstan
PHPCS=vendor/bin/php-cs-fixer

.PHONY: all install test stan cs-fix cs-check qa

all: qa

install:
	$(COMPOSER) install --no-interaction --prefer-dist

test:
	$(PHPUNIT) --colors=always

stan:
	$(PHPSTAN) analyse --no-progress

cs-fix:
	$(PHPCS) fix

cs-check:
	$(PHPCS) fix --dry-run --diff

qa: cs-check stan test
