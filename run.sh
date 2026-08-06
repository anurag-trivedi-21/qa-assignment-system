#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is required to run this project." >&2
  exit 1
fi

if [[ ! -f .env ]]; then
  echo "Creating .env from .env.example..."
  cp .env.example .env
fi

if [[ ! -d vendor ]]; then
  echo "Installing PHP dependencies..."
  composer install --no-interaction --prefer-dist
fi

if [[ ! -x vendor/bin/sail ]]; then
  echo "Laravel Sail was not generated. Please run composer install again." >&2
  exit 1
fi

APP_PORT="$(grep '^APP_PORT=' .env 2>/dev/null | cut -d= -f2- || true)"
if [[ -z "$APP_PORT" ]]; then
  APP_PORT="8080"
fi

echo "Starting the application containers..."
./vendor/bin/sail up -d

echo "Generating the application key..."
./vendor/bin/sail artisan key:generate --force

echo "Preparing the database and seed data..."
./vendor/bin/sail artisan migrate:fresh --seed

echo
echo "The project is ready!"
echo "Open http://localhost:${APP_PORT}/admin"
echo "Sign in with: manager@example.test / password"
