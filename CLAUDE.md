# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

Laravel 12 JSON API on PHP 8.4. Runs in Docker (`docker-compose.yml`): `app` (php-fpm, built from `docker/php/Dockerfile`, runs as host UID/GID, Xdebug toggled via `XDEBUG_MODE`), `nginx`, `mysql` 8.4, `redis` 7. Default env: MySQL DB, Redis cache, database queue.

## Commands

Run inside the `app` container (`docker compose exec app <cmd>`) when using Docker.

```bash
composer test                              # config:clear + php artisan test (Pest)
vendor/bin/pest                            # all tests
vendor/bin/pest tests/Feature/FooTest.php  # one file
vendor/bin/pest --filter="test name"       # one test
vendor/bin/pint --dirty                    # format changed files
vendor/bin/phpstan analyse --memory-limit=1G   # Larastan, config in phpstan.neon (level 5)
php artisan scribe:generate                # regenerate API docs (served at /docs)
php artisan ide-helper:models -W           # refresh model @property docblocks
php artisan app:movie-downloader-command   # interactive movie scraper/downloader
```

Tests use in-memory SQLite (`phpunit.xml`), array cache, sync queue. `RefreshDatabase` is commented out in `tests/Pest.php` — add it per test file (`uses(RefreshDatabase::class)`) or enable globally when tests touch the DB.

## CI

`.github/workflows/ci.yml` runs on every push and PR: three parallel jobs — `pint --test`, `phpstan analyse` (Larastan), `pest --ci` (PHP 8.4). Run all three locally before pushing.

## Architecture

**Request flow:** `routes/api.php` → controller in `app/Http/Controllers/Api` → `FormRequest` validates and exposes `toDto()` returning a readonly DTO (`app/DTO/...`) → service in `app/Services` holds business logic → response via a `JsonResource`. Controllers stay thin.

**Error convention:** services throw exceptions whose `code` is the HTTP status (e.g. `RuntimeException('...', 401)`); controllers catch `Throwable` and return `MessageResource::error($e->getMessage(), $e->getCode())`. `MessageResource` produces `{success, status, message}` and sets the HTTP status via `withResponse`. Resources set `public static $wrap;` (null) so responses are unwrapped. `bootstrap/app.php` forces JSON exception rendering for `api/*`.

**JWT auth (custom, no jwt-auth package):**
- `JwtService` issues/parses HS256 tokens with `firebase/php-jwt`. Claims: `iss` = `APP_URL`, `sub` = user id, `jti` uuid, `exp` = now + `JWT_TTL`. Config in `config/jwt.php`; `JWT_SECRET` must be set in `.env`.
- Logout revokes by storing `jwt:revoked:{jti}` in the cache until token expiry — revocation depends on the cache store (Redis in dev, array in tests).
- `AppServiceProvider::boot()` registers an `Auth::viaRequest('jwt', ...)` driver; the `api` guard in `config/auth.php` uses it. Protect routes with `->middleware('auth:api')`.
- Changing `APP_URL` invalidates all issued tokens (issuer check).

**API docs:** Scribe (`config/scribe.php`, type `laravel`). Document endpoints with Scribe PHP attributes on controller methods (`#[Group]`, `#[Endpoint]`, `#[Authenticated]`/`#[Unauthenticated]`, `#[Response]`), `bodyParameters()` on FormRequests, and `#[ResponseField]` on resource `toArray()`. Keep these in sync when changing endpoints.

**Movies domain:** `Genre` and `Movie` models are populated by `MovieDownloaderCommand`, which scrapes an external site using PHP 8.4's native `Dom\HTMLDocument`, then downloads files to `storage/app/movies`. Workers claim pending movies inside a DB transaction via `processing_started_at`; claims older than 6 hours are treated as stale and re-claimable. `is_downloaded`/`hash` mark completion.

## Claude Code setup

`.claude/` defines a `/feature` command that orchestrates `laravel-coder` (app code only, never touches `tests/`) and `laravel-tester` (Pest tests only, never fixes app code) in a code → test → fix loop. Project conventions they enforce: validation in FormRequests, authorization in Policies, logic in Services/Actions, reversible migrations, a factory for every new model, eager loading to avoid N+1. `.claude/settings.json` denies `migrate:fresh`, `db:wipe`, `git push`, and reading `.env`; run destructive DB commands only with `--env=testing`.
