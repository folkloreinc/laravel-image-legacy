#!/bin/bash
MODULE_CACHE_DIR=${TRAVIS_BUILD_DIR}/travis/module-cache/`php-config --vernum`
INI_DIR=${TRAVIS_BUILD_DIR}/travis/ini/
PHP_TARGET_DIR=`php-config --extension-dir`

if [ -d ${MODULE_CACHE_DIR} ]
then
  cp ${MODULE_CACHE_DIR}/* ${PHP_TARGET_DIR}
fi

mkdir -p ${INI_DIR}
mkdir -p ${MODULE_CACHE_DIR}

for module in $MODULES
do
  FILENAME=`echo $module|cut -d : -f 1`
  PACKAGE=`echo $module|cut -d : -f 2`
  if [ ! -f ${PHP_TARGET_DIR}/${FILENAME} ]
  then
    echo "$FILENAME not found in extension dir, compiling"
    printf "yes\n" | pecl install ${PACKAGE}
  else
    echo "Adding $FILENAME to php config"
    echo "extension = $FILENAME" > ${INI_DIR}/${FILENAME}.ini
    phpenv config-add ${INI_DIR}/${FILENAME}.ini
  fi
  cp ${PHP_TARGET_DIR}/${FILENAME} ${MODULE_CACHE_DIR}
done
