#!/bin/bash

if [-n $COVERAGE]; then travis_retry php vendor/bin/coveralls; fi
