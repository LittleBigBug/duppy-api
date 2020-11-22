#!/bin/bash

skipcomposer=0

while [[ "$#" -gt 0 ]]; do
    case $1 in
        -s|--skip-composer) skipcomposer=1 ;;
        *) echo "Unknown parameter passed: $1"; exit 1 ;;
    esac
    shift
done

if [ "$skipcomposer" -eq "0" ] ; then
  composer update
fi

vendor/bin/doctrine orm:schema-tool:update --force --dump-sql
