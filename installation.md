# Docker Installation and Run Guide

Run all commands from the project root.

## 1) Build Docker images

```bash
docker compose build
```

## 2) Start containers

```bash
docker compose up -d
```

## 3) Generate app key (first run or after fresh setup)

```bash
docker compose exec -T laravel.test php artisan key:generate --force
```

## 4) Run migrations and seed data

```bash
docker compose exec -T laravel.test php artisan migrate --seed --force
```

If you want a clean database reset with seed data:

```bash
docker compose exec -T laravel.test php artisan migrate:fresh --seed --force
```

## 5) Run tests in Docker

Run full test suite:

```bash
docker compose exec -T laravel.test php artisan test
```

Run one test file:

```bash
docker compose exec -T laravel.test php artisan test tests/Feature/FloatingClockOutButtonTest.php
```

Run by filter:

```bash
docker compose exec -T laravel.test php artisan test --filter=FloatingClockOut
```

## 6) Open app

- Admin URL: http://localhost:8080/admin
- Default seeded manager login: manager@example.test / password