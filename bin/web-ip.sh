#!/usr/bin/env bash

set -e

PROXY_ID=$(docker-compose ps --quiet service-template-web)

PROXY_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $PROXY_ID)

echo "    web:      http://"$(docker-compose port service-template-web 80)
echo "              http://$PROXY_IP"
