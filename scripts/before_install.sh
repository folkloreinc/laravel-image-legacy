#!/bin/bash
sudo apt-get install graphicsmagick

sh -c "mkdir -p ${TRAVIS_BUILD_DIR}/travis/module-cache/`php-config --vernum`"
pear config-set preferred_state beta
pecl channel-update pecl.php.net
MODULES="imagick.so:imagick gmagick.so:gmagick" ./scripts/modulecache.sh

composer global require hirak/prestissimo --update-no-dev
composer require "illuminate/support:${ILLUMINATE_VERSION}" --no-update --prefer-dist
