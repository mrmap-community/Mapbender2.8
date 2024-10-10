#!/bin/bash

# wait for database
echo "checking database host is reachable..."
while ! nc -z $POSTGRES_HOST $POSTGRES_PORT; do
  sleep 0.3
  echo "Waiting for database host..."
done
echo "database host is up... continue"

exec "$@"