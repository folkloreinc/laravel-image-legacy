#!/bin/bash
sh -c "mkdir -p ${TRAVIS_BUILD_DIR}/travis/module-cache/`php-config --vernum`"
pear config-set preferred_state beta
pecl channel-update pecl.php.net
yes | pecl install imagick || true
yes | pecl install gmagick || true
composer global require hirak/prestissimo --update-no-dev
composer require "illuminate/support:${ILLUMINATE_VERSION}" --no-update --prefer-dist
composer require "orchestra/testbench:${TESTBENCH_VERSION}" --no-update --prefer-dist
composer require "phpunit/phpunit:${PHPUNIT_VERSION}" --no-update --prefer-dist
