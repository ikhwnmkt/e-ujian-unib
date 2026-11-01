<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Deploy to Vercel

This Laravel application can run on Vercel using the container runtime, FrankenPHP, and the root `Dockerfile`.

Before deploying:

1. Connect the repository to a Vercel project and select the Container preset.
2. Add production environment variables in Vercel: `APP_KEY`, `APP_URL`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE`, `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `GOOGLE_CLIENT_ID`, and `GOOGLE_CLIENT_SECRET`. Set `APP_ENV=production` and `APP_DEBUG=false`. Use a persistent external PostgreSQL database and set `SESSION_DRIVER=database`, `CACHE_STORE=database`, and `QUEUE_CONNECTION=sync`.
3. Set the Google OAuth callback URL to `https://<your-domain>/auth/google/callback` in the Google OAuth client. The app uses that URL from `APP_URL` by default; set `GOOGLE_REDIRECT_URI` only if you need to override it.
4. Run `php artisan migrate --force` against the production database once, from a trusted release environment. Do not run migrations at container startup or seed demo data in production. For local demo data, run `php artisan migrate --seed` against a local database; the sample exams are scheduled for the current day and the seeders can be rerun without duplicating their records.
5. Deploy with the Vercel CLI (`vercel deploy --prod`) or import the repository in Vercel.

The application currently stores uploaded avatars and question media on the local `public` disk. Vercel container storage is ephemeral, so uploads will not be durable across deployments or instances; configure persistent object storage and update the app's upload/display paths before relying on uploaded media in production. Never commit `.env` or paste secret values into chat.

### CI and deployment changes

GitHub Actions runs `php artisan test` on PHP 8.2 and 8.4 for pushes and pull requests using an in-memory SQLite database. Workflow-specific maintenance guidance is in `.github/instructions/ci-workflow.instructions.md`.

The current deployment setup adds a FrankenPHP Docker image, Caddy configuration, Vercel container routing, and ignore rules for local files and secrets. Laravel trusts the Vercel proxy headers so generated URLs use HTTPS, and Google OAuth derives its callback from `APP_URL` unless `GOOGLE_REDIRECT_URI` overrides it. The Supabase production schema and Laravel migration records have been initialized. Production deployments are publicly accessible while preview deployments remain protected.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
