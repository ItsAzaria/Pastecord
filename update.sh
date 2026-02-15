#!/bin/sh
set -eu

docker compose build --no-cache
docker compose up -d --force-recreate

