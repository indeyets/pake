#!/bin/sh
#
# Shell wrapper for pake (based on Phing shell wrapper)
# $Id: pake.sh 67 2005-10-08 11:50:16Z fabien $
#
# This script will do the following:
# - check for PHP_COMMAND env, if found, use it.
#   - if not found assume php is on the path
# - check for PAKE_HOME env, if found use it
#   - if not look for it
# - check for PHP_CLASSPATH, if found use it
#   - if not found set it using PAKE_HOME/lib

if [ -z "$PAKE_HOME" ] ; then
  PAKE_HOME="@PEAR-DIR@"
fi

if (test -z "$PHP_COMMAND") ; then
  # echo "WARNING: PHP_COMMAND environment not set. (Assuming php on PATH)"
  export PHP_COMMAND=php
fi

if (test -z "$PHP_CLASSPATH") ; then
  PHP_CLASSPATH=$PAKE_HOME/lib
  export PHP_CLASSPATH
fi

$PHP_COMMAND -d html_errors=off -qC $PAKE_HOME/pake.php "$@"
