# University of Olodo — Digital Campus Platform

A complete university ecosystem built with **Laravel**: public website, admissions & application lifecycle, student portal, lecturer workspace, integrated LMS, academic operations (registration → results), resource hub, administration, and payments architecture.

> University of Olodo is a fictional institution. All seeded people, programmes and records are sample data that demonstrate real product relationships — not factual claims.

## Requirements

- PHP 8.3+ (extensions: `sqlite3`, `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, `gd`, `intl`)
- Composer
- Node.js 20+ / npm

## Quick start

```bash
composer install
npm install && npm run build

cp .env.example .env          # SQLite is preconfigured as the dev database
php artisan key:generate

php artisan migrate:fresh --seed
php artisan serve             # http://127.0.0.1:8000
```

`touch database/database.sqlite` first if your setup does not create it automatically.

## Demo accounts

All demo users use the password **`password`** (development seed only — never in production).

| Role | Email |
| --- | --- |
| Super Administrator | `admin@olodo.edu.ng` |
| Registrar | `registrar@olodo.edu.ng` |
| Admissions Officer | `admissions@olodo.edu.ng` |
| Finance Officer | `finance@olodo.edu.ng` |
| Lecturer | `c.obi@olodo.edu.ng` |
| Student (primary demo) | `z.adeyemi@student.olodo.edu.ng` |
| Applicant (under review) | `emeka.nwosu@example.com` |

The full persona map lives in [docs/SEED.md](docs/SEED.md).

## Documentation

- [Product definition](docs/PRODUCT.md) — institution, users, flows, scope
- [Architecture](docs/ARCHITECTURE.md) — stack rationale, data model, authorization matrix
- [Seeded world](docs/SEED.md) — demo data specification

## Tests

```bash
php artisan test
```

## Production notes

SQLite powers local development only. The schema avoids SQLite-specific SQL and migrates to MySQL/PostgreSQL unchanged; payment providers are abstracted behind a verification-based interface and ship with a manual/dev provider.
