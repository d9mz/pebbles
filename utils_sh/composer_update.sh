#!/bin/bash
# Follows logs if your local instance is running.

# nginx: http://localhost:8000
# phpma: http://localhost:8000

docker run --rm -v $(pwd)/web/app:/app composer update