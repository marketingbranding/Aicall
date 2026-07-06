# Hostinger Business Deployment Specification

## Target

Initial production environment:

**Hostinger Business Web Hosting**

Repository:

GitHub is the source of truth.

Deployment options:

1. SSH + `git pull`
2. Hostinger hPanel Git deployment

SSH workflow is the primary documented operational workflow because it allows explicit Laravel deployment commands.

## Hosting Constraints

Design for shared/business hosting.

Do not require:

- Docker
- Kubernetes
- Redis
- Supervisor
- permanent custom WebSocket daemon
- Laravel Horizon
- Laravel Octane

Hostinger web/shared hosting supports scheduled Cron Jobs, but VPS provides more process-control customization. Therefore the initial queue architecture uses database queues with bounded workers triggered by cron.

## PHP

Target PHP: 8.3 or newer compatible version.

Laravel 13 requires PHP 8.3 minimum.

Verification note 2026-07-06: Hostinger's current PHP version documentation shows PHP 8.3 as the default for new websites and allows PHP 8.2 through PHP 8.5 on hPanel. This supports the Laravel 13 PHP 8.3 baseline for the initial target, but the exact production website PHP and SSH/CLI PHP binaries still must be checked in the specific Hostinger account during deployment.

Ensure both:

- website PHP version
- SSH/CLI PHP used for Composer and Artisan

are compatible.

Hostinger may use a different default PHP binary for SSH/Composer than the PHP version selected for a website. Deployment documentation must verify `php -v` and, when necessary, use the explicit Hostinger PHP binary path for Composer/Artisan.

## MySQL

Create a MySQL database and database user through hPanel.

Production `.env` contains:

- DB host
- database name
- username
- password

Never commit production `.env`.

## Suggested Server Layout

Prefer a structure where the Laravel application is not fully exposed as the public web root.

Conceptual:

```text
/home/USER/domains/app.example.com/
    application/        # Laravel repository
    public_html/        # public web root
```

Preferred solution is to configure the domain/subdomain document root to Laravel's `public` directory when Hostinger configuration permits.

If the hosting layout requires Hostinger's shared-hosting Laravel file movement pattern, document the exact mapping carefully and keep application source outside the public web root where practical.

Do not expose:

- `.env`
- `vendor` internals unnecessarily
- storage private files
- application source files as downloadable static content

## Initial SSH Deployment

Conceptual commands; adapt paths and PHP binary to the Hostinger account:

```bash
cd /home/USER/domains/app.example.com/application

git clone REPOSITORY_URL .

composer install --no-dev --optimize-autoloader

cp .env.example .env
php artisan key:generate

php artisan migrate --force
php artisan storage:link

php artisan optimize
```

Before `php artisan optimize`, ensure production `.env` is configured.

Do not run `key:generate` on every deployment after the application is already live.

### Create First Super Admin

After the initial deployment and migration, create the first Super Admin using the Artisan command:

```bash
php artisan app:create-super-admin --name="Nama Admin" --email="admin@example.com" --password="secure-password-min-8-chars"
```

Command behavior:

- `--name`, `--email`, `--password` are all required.
- Validates email format and minimum 8-character password.
- Prevents creating a user with an email that already exists.
- Prevents creating a second Super Admin unless `--force` is used (for emergency recovery or secondary admin).
- Created user is `SUPER_ADMIN`, `ACTIVE`, `branch_id` null, with `email_verified_at` set.
- After creation, the Super Admin can immediately log in and access HQ.

To create additional Super Admins if needed:

```bash
php artisan app:create-super-admin --name="Nama Admin Lain" --email="admin2@example.com" --password="another-secure-password" --force
```

Never commit the command or its arguments to shell scripts stored in the repository.

## Subsequent SSH Deployment

Conceptual flow:

```bash
cd /home/USER/domains/app.example.com/application

git pull origin main

composer install --no-dev --optimize-autoloader

php artisan migrate --force
php artisan optimize
```

If production assets are part of the deployment artifact, verify `public/build/manifest.json` exists.

If assets are built on server, explicitly verify Node/npm support and resource behavior before depending on it.

## Vite Asset Strategy

Preferred initial strategy: CI-built production assets.

Recommended GitHub Actions concept:

1. checkout
2. install PHP dependencies for test
3. run tests
4. install Node dependencies
5. run production Vite build
6. package/deploy artifact or make built assets available to deployment

Alternative: build locally and deploy the built assets through a controlled workflow.

Do not require a permanent Node runtime.

## Environment Variables

`.env.example` must document keys without secret values.

Suggested categories:

### Application

- `APP_NAME`
- `APP_ENV`
- `APP_KEY`
- `APP_DEBUG`
- `APP_URL`

### Database

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

### Session / Queue / Cache

- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=database`
- appropriate cache driver

### Mail

- `MAIL_MAILER`
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_ENCRYPTION`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

### Gemini Live

- `GEMINI_API_KEY`
- `GEMINI_LIVE_MODEL`

### Evaluators

Provider secrets, for example:

- `OPENROUTER_API_KEY`
- `GROQ_API_KEY`
- optional evaluator Gemini key/config

Database provider config should reference environment secret names rather than store plaintext secrets.

## Cron and Scheduler

Configure Hostinger hPanel Cron Jobs.

Laravel scheduler concept:

```bash
php /path/to/application/artisan schedule:run
```

Cron scheduling in Hostinger hPanel uses UTC. Account for this explicitly when configuring schedules.

Do not assume server local timezone equals Asia/Jakarta.

Application timezone may be `Asia/Jakarta`, but cron trigger configuration must respect Hostinger's cron timezone behavior.

## Queue Processing

Initial queue: database.

Because a permanent queue daemon is not an initial hosting assumption, use a bounded worker command from scheduler/cron.

Conceptual command:

```bash
php artisan queue:work database --stop-when-empty --tries=2 --timeout=120
```

Exact timeout and cadence must be tested with evaluator latency and Hostinger resource limits.

Evaluation job requirements:

- idempotent
- one evaluation per session
- provider timeouts
- safe retries
- provider fallback
- no duplicate on page refresh

Potential limitation:

Cron-driven queue execution may introduce evaluation delay.

This is acceptable for v1 if the UI clearly shows that the session is saved and analysis is processing.

When user volume or evaluation latency makes this unacceptable, migrate queue processing to VPS/managed workers.

## Password Reset Mail

Before production launch:

- configure SMTP
- test password reset from production domain
- verify reset link points to correct `APP_URL`
- verify token expiration

## File Permissions

Ensure Laravel-required writable directories are writable according to Hostinger's environment:

- `storage`
- `bootstrap/cache`

Do not use unsafe broad permissions as the default solution.

## Production Configuration

Required:

```env
APP_ENV=production
APP_DEBUG=false
```

Use HTTPS.

Run optimized Laravel deployment commands.

Never expose debug stack traces publicly.

## Git Safety

`.gitignore` must include:

- `.env`
- runtime logs
- private storage artifacts
- local development files

Never commit API keys.

Rotate any secret accidentally committed to Git history.

## hPanel Git Deployment Alternative

Hostinger hPanel can connect a GitHub repository through its Git deployment integration.

Document this as an alternative, but do not assume Git deployment alone executes all Laravel post-deploy commands required by the application.

The team must explicitly handle:

- Composer install
- migrations
- Laravel optimization
- storage link when needed
- production assets

## Deployment Checklist

1. PHP/CLI version verified
2. database created
3. domain/subdomain document root verified
4. repository deployed
5. production `.env` configured
6. Composer dependencies installed
7. app key exists
8. migrations applied
9. first Super Admin created (`php artisan app:create-super-admin`)
10. assets built/deployed
11. storage permissions verified
12. `APP_DEBUG=false`
13. HTTPS verified
14. cron configured
15. queue execution tested
16. password reset email tested
17. Gemini ephemeral-token endpoint tested
18. 15-minute Live session/resumption manually tested
19. evaluator fallback tested
20. logs checked for secret leakage
21. backup/recovery procedure documented

## VPS Migration Triggers

Consider moving workers or the entire application to VPS when:

- cron queue delay harms training UX
- evaluation concurrency grows significantly
- Hostinger CPU/RAM process limits are repeatedly reached
- dedicated Redis/Horizon monitoring is needed
- long-running process control is required
- application-level realtime services beyond direct Gemini Live are added

Domain contracts should remain unchanged during this migration.
