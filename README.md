# TheSocialNetworkApp

PHP scaffold prepared for Railway deployment with PostgreSQL.

## Endpoints

- `/health` - app health check
- `/db-check` - validates PostgreSQL connection

## Railway

**Web service** (default):

- Config file: `railway.json`
- Root directory: leave empty (project root — must include `composer.json`, `includes/`, etc.)
- Start: `php -S 0.0.0.0:$PORT router.php`

**Score worker** (cron):

- Config file: `railway.worker.json` (set full path in service settings if needed)
- Root directory: leave empty — do **not** set `/workers` (that folder has no `composer.json` and cannot reach `includes/`)
- Start: `php workers/compute-post-scores.php` (every 5 minutes via `cronSchedule` in config)

Shared: `router.php`, PostgreSQL via `DATABASE_URL` or `PG*` vars

## Environment Variables

Railway PostgreSQL typically provides `DATABASE_URL`.

Fallback variables supported:

- `PGHOST`
- `PGPORT`
- `PGDATABASE`
- `PGUSER`
- `PGPASSWORD`
