#!/bin/bash
EXTENSIONS=$1
EXTENSION_CACHE_DIR=${TRAVIS_BUILD_DIR}/travis/extension-cache/`php-config --vernum`
INI_DIR=${TRAVIS_BUILD_DIR}/travis/ini/
PHP_TARGET_DIR=`php-config --extension-dir`

mkdir -p ${EXTENSION_CACHE_DIR}

if [ -d ${EXTENSION_CACHE_DIR} ]
then
  cp ${EXTENSION_CACHE_DIR}/* ${PHP_TARGET_DIR}
fi

mkdir -p ${INI_DIR}
mkdir -p ${EXTENSION_CACHE_DIR}

for extension in $EXTENSIONS
do
  FILENAME=`echo $extension|cut -d : -f 1`
  PACKAGE=`echo $extension|cut -d : -f 2`
  if [ ! -f ${PHP_TARGET_DIR}/${FILENAME} ]
  then
    echo "$FILENAME not found in extension dir, compiling"
    printf "yes\n" | pecl install ${PACKAGE} || true
  else
    echo "Adding $FILENAME to php config"
    echo "extension = $FILENAME" > ${INI_DIR}/${FILENAME}.ini
    phpenv config-add ${INI_DIR}/${FILENAME}.ini
  fi
  if [ -f ${PHP_TARGET_DIR}/${FILENAME} ]
  then
    echo "Copying $FILENAME to php config"
    cp ${PHP_TARGET_DIR}/${FILENAME} ${EXTENSION_CACHE_DIR}
  fi
done
