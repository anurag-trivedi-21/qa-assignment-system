# Testing Queue Take-home

A self-contained Laravel/Filament take-home for designing a tester clock-in and automatic test-assignment system.

The product requirements are in [TAKEHOME.md](TAKEHOME.md). This README only covers getting the project running.

## Included services

- Laravel 13 and Filament 5
- SQLite (a local file at `database/database.sqlite`)
- Redis for queues and cache
- Laravel Sail, with its shared configuration committed as `compose.yaml`

No production credentials or external services are required.

No npm install or asset build is required for this exercise: Filament's admin assets are already provided by the PHP dependencies.

## Prerequisites

- Docker Desktop (or another Docker Engine with Docker Compose)
- PHP 8.5+ and Composer 2, used once to install the PHP dependencies before Sail is available

## Setup

```sh
git clone <repository-url> php-takehome
cd php-takehome
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
```

Open [http://localhost:8091/admin](http://localhost:8091/admin) and sign in with:

- Email: `manager@example.test`
- Password: `password`

The seed resets the local SQLite database and creates a queue manager, enabled and disabled tester accounts, 55 generic test subjects, pending tests, and historical results.

## Everyday commands

```sh
# Run the Pest suite
./vendor/bin/sail pest

# Reset the local data set
./vendor/bin/sail artisan migrate:fresh --seed

# Run the queue worker (once the assignment work exists)
./vendor/bin/sail artisan queue:work

# Stop containers
./vendor/bin/sail down
```

`compose.yaml` publishes the PHP application on port `8091` and starts Redis. The matching defaults are committed in `.env.example`; do not commit a personal `.env` file or the SQLite database.

## Candidate task

See [TAKEHOME.md](TAKEHOME.md) for the clock-in/out, scheduling, automatic-assignment, reassignment, cooldown, and quality requirements. All Pest tests should pass before submission; applicants should add coverage for every new behavior.
