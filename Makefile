.PHONY: lint lint-fix lint-php lint-php-fix lint-twig lint-twig-fix lint-js lint-js-fix

lint: lint-php lint-twig lint-js

lint-fix: lint-php-fix lint-twig-fix lint-js-fix

lint-php:
	composer cs-php

lint-php-fix:
	composer cs-php-fix

lint-twig:
	composer cs-twig

lint-twig-fix:
	composer cs-twig-fix

lint-js:
	npm run lint:js

lint-js-fix:
	npm run lint:js:fix
