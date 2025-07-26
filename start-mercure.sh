#!/bin/bash

# Start Mercure Hub for local development
export MERCURE_PUBLISHER_JWT_KEY="!ChangeThisMercureHubJWTSecretKey!"
export MERCURE_SUBSCRIBER_JWT_KEY="!ChangeThisMercureHubJWTSecretKey!"

echo "Starting Mercure Hub on http://127.0.0.1:3000"
echo "Press Ctrl+C to stop"

./mercure run --config Caddyfile.dev
