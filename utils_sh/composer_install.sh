#!/bin/bash
# Installs composer package to running Pebbles instance.
# composer_install.sh author/package

# nginx: http://localhost:8000
# phpma: http://localhost:8000

docker run --rm -v $(pwd)/web/app:/app composer require $1