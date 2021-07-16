#!/usr/bin/env bash

if [[ ! -f .env ]]; then
    echo "Please copy the file .env.dist to .env and configure for your needs!" >&2
    (return 2>/dev/null) && return 1 || exit 1
fi

if [[ ! -f app.env ]]; then
    echo "Please copy the file app.env.dist to app.env and configure for your needs! (runs out of the box)" >&2
    (return 2>/dev/null) && return 1 || exit 1
fi

if [[ ! -f config/autoload/local.php ]]; then
    cp config/autoload/local.php.dist config/autoload/local.php
fi

docker-compose up -d --no-recreate

docker-compose ps
