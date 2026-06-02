# TheSocialNetworkApp

PHP scaffold prepared for Railway deployment with PostgreSQL.

## Endpoints

- `/health` - app health check
- `/db-check` - validates PostgreSQL connection

## Railway

This project includes:

- `railway.json` with start command
- `router.php` for PHP built-in server routing
- PostgreSQL connection via `DATABASE_URL` or `PG*` vars

## Environment Variables

Railway PostgreSQL typically provides `DATABASE_URL`.

Fallback variables supported:

- `PGHOST`
- `PGPORT`
- `PGDATABASE`
- `PGUSER`
- `PGPASSWORD`
