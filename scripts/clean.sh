#!/bin/bash

# Build tools now overshadow this script
# Set working directory to the base dir
cd "${0%/*}"
cd ../

rm -rf ./build
rm -rf ./tools
rm -rf ./vendor