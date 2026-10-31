---
description: "Use when creating or updating GitHub Actions CI workflows for this Laravel application."
applyTo: ".github/workflows/**/*.yml"
---

# CI Workflow Instructions

- Keep CI isolated from production: use in-memory SQLite and never add production credentials or deploy/migrate production from test jobs.
- Set `APP_URL` to `http://localhost` in CI so generated route URLs do not inherit the subdirectory configured in `.env.example`.
- Test the minimum supported PHP version (8.2) and the PHP runtime used by the production container (8.4).
- Install dependencies from `composer.lock`, then run `php artisan test`.
- Grant workflows only the GitHub token permissions they need.
- Keep pull requests and pushes covered; do not make test failures non-blocking.
