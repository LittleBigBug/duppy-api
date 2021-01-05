#!/bin/bash

# Build tools now overshadow this script
# Set working directory to the script
cd "${0%/*}"

skipcomposer=0
skipgit=0
skipphar=0

while [[ "$#" -gt 0 ]]; do
    case $1 in
        -sc|--skip-composer) skipcomposer=1 ;;
        -sg|--skip-git) skipgit=1 ;;
        -sp|--skip-phar) skipphar=1 ;;
        -s|--skip-all) skipcomposer=1 skipgit=1 skipphar=1 ;;
        *) echo "Unknown parameter passed: $1"; exit 1 ;;
    esac
    shift
done

if [ "$skipgit" -eq "0" ] ; then
  git pull
fi

if [ "$skipcomposer" -eq "0" ] ; then
  composer update
fi

if [ "$skipphar" -eq "0" ] ; then
  /usr/local/bin/phive update
fi

vendor/bin/doctrine orm:schema-tool:update --force --dump-sql
vendor/bin/doctrine orm:generate-proxies