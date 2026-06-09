---
name: cicd-intelligence
description: "Activate when reviewing, optimizing, or troubleshooting CI/CD workflows for Dot.Agents. Covers GitHub Actions pipeline analysis, deployment workflow review, build optimization, rollback strategies, Docker setup validation, environment configuration review, and queue configuration. Use when the user mentions 'GitHub Actions', 'CI/CD', 'pipeline', 'deployment', 'Docker', 'build', 'release', 'rollback', or asks to improve the build or deployment process."
license: MIT
metadata:
  author: dotagents
---

# CI/CD Intelligence

AI-powered review and optimization of the Dot.Agents deployment pipeline. Reviews GitHub Actions workflows, Docker configuration, environment setup, and queue configuration — recommending faster builds, safer deployments, and reliable rollback strategies.

## When to Activate

- Reviewing or creating GitHub Actions workflows
- Optimizing build times or deployment reliability
- Adding a new deployment stage or environment
- Troubleshooting pipeline failures
- Validating Docker or environment configuration
- Queue configuration review

---

## 1. GitHub Actions Review

### Workflow Inventory
```bash
find .github/workflows -name "*.yml" -o -name "*.yaml"
```

### Required Workflows for Dot.Agents

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| `ci.yml` | PR, push to main | Lint, test, security scan |
| `deploy-staging.yml` | Push to `develop` | Auto-deploy to staging |
| `deploy-production.yml` | Release tag / manual | Production deployment |
| `security-scan.yml` | Daily schedule | `composer audit`, `npm audit` |
| `code-quality.yml` | PR | Pint, PHPStan static analysis |

### Recommended CI Workflow Template
```yaml
name: CI

on:
  pull_request:
    branches: [main, develop]
  push:
    branches: [main, develop]

jobs:
  lint:
    name: Code Style (Pint)
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          coverage: none
      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/pint --test

  test:
    name: PHPUnit Tests
    runs-on: ubuntu-latest
    needs: lint
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_sqlite
          coverage: pcov
      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
      - run: composer install --no-interaction --prefer-dist
      - run: cp .env.testing .env
      - run: php artisan key:generate
      - run: php artisan test --compact --coverage --min=80

  security:
    name: Dependency Security Scan
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - run: composer audit --no-interaction
      - run: npm audit --audit-level=moderate

  static-analysis:
    name: PHPStan Static Analysis
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          coverage: none
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/phpstan analyse app/ --level=5 --no-progress
```

---

## 2. Pipeline Optimization

### Build Speed Improvements

#### Composer Cache (Critical)
```yaml
# Always cache vendor/ keyed on composer.lock hash
- uses: actions/cache@v4
  with:
    path: vendor
    key: composer-${{ runner.os }}-${{ hashFiles('**/composer.lock') }}
    restore-keys: |
      composer-${{ runner.os }}-
```

#### NPM Cache
```yaml
- uses: actions/cache@v4
  with:
    path: ~/.npm
    key: npm-${{ runner.os }}-${{ hashFiles('**/package-lock.json') }}
```

#### Parallel Jobs
```yaml
# Run lint, test, security in parallel — don't chain unnecessarily
jobs:
  lint:    # independent
  test:    # independent
  security: # independent
  deploy:
    needs: [lint, test, security]  # gates on all three
```

#### Job Matrix for PHP Versions
```yaml
strategy:
  matrix:
    php: ['8.4']  # Expand to ['8.3', '8.4'] for compatibility checks
```

### Build Time Benchmarks
| Stage | Target | Alert |
|-------|--------|-------|
| Composer install (cached) | < 30s | > 90s |
| PHPUnit full suite | < 3min | > 8min |
| Asset build (Vite) | < 60s | > 3min |
| Docker image build | < 5min | > 15min |
| Full CI pipeline | < 8min | > 20min |

---

## 3. Deployment Workflow

### Safe Deployment Pattern (Zero-Downtime)
```yaml
name: Deploy to Production

on:
  push:
    tags:
      - 'v[0-9]+.[0-9]+.[0-9]+'

jobs:
  deploy:
    name: Production Deploy
    runs-on: ubuntu-latest
    environment: production
    steps:
      - uses: actions/checkout@v4

      - name: Run final test suite
        run: php artisan test --compact

      - name: Build assets
        run: npm ci && npm run build

      - name: Deploy (with maintenance mode)
        run: |
          php artisan down --retry=60 --refresh=15
          composer install --no-dev --optimize-autoloader
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          php artisan queue:restart
          php artisan up

      - name: Health check
        run: curl -f https://app.dotagents.com/health || exit 1

      - name: Notify on failure
        if: failure()
        run: php artisan down:notify  # Custom command for incident alerts
```

### Deployment Checklist
- [ ] Tests pass (zero failures)
- [ ] `composer audit` passes (zero high/critical CVEs)
- [ ] Migration is reversible (`down()` method exists)
- [ ] No breaking changes to existing API contracts
- [ ] Queue workers restarted after deploy (`queue:restart`)
- [ ] Health check endpoint returns 200 after deploy
- [ ] Maintenance mode (`php artisan down`) used during migrations

---

## 4. Rollback Strategies

### Database Rollback
```bash
# Never run in production without a database backup first
php artisan migrate:rollback --step=1

# Verify rollback migration exists
php artisan migrate:status
```

### Application Rollback (Git-Based)
```bash
# Tag every production release for easy rollback
git tag v1.2.3
git push origin v1.2.3

# Rollback: redeploy previous tag
git checkout v1.2.2
php artisan down
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan up
```

### Feature Flag Integration
For high-risk features, use a feature flag so deployment and activation are decoupled:
```php
// Feature deployed but not yet active
if (config('features.new_agent_engine')) {
    // New code path
}
```

---

## 5. Docker Validation

### Required Dockerfile Checks
- [ ] Based on official PHP image (`php:8.4-fpm-alpine` for minimal size)
- [ ] Composer installed as multi-stage build (don't ship Composer in production image)
- [ ] No secrets in Dockerfile or `.dockerignore` violations
- [ ] `.env` excluded from Docker image (passed as environment variables at runtime)
- [ ] Non-root user for the application process
- [ ] Health check instruction defined

### Recommended Dockerfile Pattern
```dockerfile
# Build stage
FROM composer:2 AS composer
WORKDIR /app
COPY composer.* ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Production image
FROM php:8.4-fpm-alpine AS production

RUN apk add --no-cache \
    libpng-dev libjpeg-turbo-dev libwebp-dev \
    && docker-php-ext-install pdo_mysql pcntl

WORKDIR /var/www/html

COPY --from=composer /app/vendor ./vendor
COPY . .

RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

HEALTHCHECK --interval=30s --timeout=10s --start-period=5s \
    CMD php artisan health:check || exit 1

EXPOSE 9000
```

---

## 6. Environment Configuration Review

### Required Environment Variables
```bash
# Application
APP_NAME="Dot.Agents"
APP_ENV=production
APP_KEY=          # Must be set — never empty in production
APP_DEBUG=false   # NEVER true in production
APP_URL=

# Database
DB_CONNECTION=mysql
DB_HOST=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=      # Never committed to .env.example with real value

# Queue
QUEUE_CONNECTION=redis  # Never 'sync' in production
REDIS_HOST=

# AI
OPENAI_API_KEY=   # From secret manager — never hardcoded
ANTHROPIC_API_KEY=

# Mail
MAIL_MAILER=smtp
```

### Validation Rules
- [ ] `APP_DEBUG=false` in production
- [ ] `APP_KEY` is 32+ characters (generated by `php artisan key:generate`)
- [ ] `QUEUE_CONNECTION` is `redis` or `database` — never `sync` in production
- [ ] All API keys sourced from secret manager (not `.env` file in container)
- [ ] `SESSION_DRIVER=redis` or `database` — never `file` in production

---

## 7. Queue Configuration Review

### Queue Health Checks
```bash
# Verify queue workers are running
php artisan queue:monitor redis:default,redis:ai-tasks,redis:notifications

# Check failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all
```

### Recommended Queue Configuration
```php
// config/queue.php — Production setup
'connections' => [
    'redis' => [
        'driver'     => 'redis',
        'connection' => 'default',
        'queue'      => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for'  => null,
        'after_commit' => true,  // Only process after DB transaction commits
    ],
],
```

### Queue Separation by Priority
```bash
# Run workers for different priority queues separately
php artisan queue:work redis --queue=critical,ai-tasks,default,notifications

# Queue assignment in Jobs
class ProcessAgentTask implements ShouldQueue
{
    public string $queue = 'ai-tasks';
    public int $timeout = 120;
    public int $tries = 3;
    public int $backoff = 10;  // seconds between retries
}
```

---

## 8. CI/CD Audit Output Format

```
## CI/CD Intelligence Report — [DATE]

### Pipeline Health: ✅ HEALTHY | ⚠️ NEEDS IMPROVEMENT | 🔴 CRITICAL ISSUES

### Workflow Coverage
| Workflow | Exists | Status | Notes |
|----------|--------|--------|-------|
| CI (lint + test) | ✅/❌ | ✅/⚠️/🔴 | |
| Security scan | ✅/❌ | ✅/⚠️/🔴 | |
| Production deploy | ✅/❌ | ✅/⚠️/🔴 | |
| Rollback strategy | ✅/❌ | ✅/⚠️/🔴 | |

### Build Performance
| Stage | Current | Target | Status |
|-------|---------|--------|--------|
| Full CI pipeline | Xmin | 8min | ✅/⚠️/🔴 |
| Test suite | Xmin | 3min | ✅/⚠️/🔴 |

### Critical Issues (fix immediately)
1. [issue] → [fix]

### Optimization Recommendations
1. [recommendation] → [expected improvement]

### Environment Validation
- APP_DEBUG: [true/false] — [✅ OK / 🔴 CRITICAL: must be false in production]
- QUEUE_CONNECTION: [value] — [✅ OK / 🔴 CRITICAL: must not be sync]
- Failed jobs in queue: [count]
```
