#!/bin/sh
set -eu

docker compose stop app nginx scheduler
docker volume rm pastecord_app_data
docker compose up -d --build
