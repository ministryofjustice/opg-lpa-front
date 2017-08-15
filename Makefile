.PHONY: watch
watch:
	docker run -it --rm -v $$(pwd):/app evolution7/nodejs-bower-grunt grunt watch --env=prod --gruntfile /app/Gruntfile.js

.PHONY: cs
cs:
	docker run -i --rm -v $$(pwd):/app registry.service.opg.digital/opguk/phpcs --standard=PSR2 module/Application/src/

.PHONY: test
test:
	docker run -i --rm -v $$(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests --bootstrap module/Application/tests/Bootstrap.php

.PHONY: testcoverage
testcoverage:
	docker run -i --rm -v $$(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests --bootstrap module/Application/tests/Bootstrap.php --coverage-html module/Application/tests/coverage/
