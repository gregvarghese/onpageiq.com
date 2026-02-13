# OnPageIQ

OnPageIQ is a Laravel 12 SaaS application for comprehensive URL content analysis. It extracts content from web pages and performs spelling, grammar, SEO, and readability checks using OpenAI models, providing detailed reports with visual issue highlighting.

## Features

- **Content Analysis**: Spelling, grammar, SEO, and readability checks
- **AI-Powered**: GPT-4o-mini (quick) and GPT-4o (deep analysis)
- **Team Collaboration**: Organizations, departments, and role-based access
- **Credit System**: Subscription tiers with credit-based billing
- **Real-time Updates**: WebSocket notifications via Laravel Reverb
- **REST API**: Full API parity with UI functionality
- **Webhooks**: Event notifications with retry logic
- **PDF Reports**: Branded export via Browsershot

## Tech Stack

- **Framework**: Laravel 12
- **Frontend**: Livewire 4, Alpine.js, Tailwind CSS 4
- **Admin Panel**: Filament 5
- **Database**: SQLite (dev), PostgreSQL (prod)
- **Queue**: Laravel Horizon
- **Real-time**: Laravel Reverb (WebSockets)
- **AI**: OpenAI via Prism (Laravel AI SDK)
- **Payments**: Stripe via Laravel Cashier
- **Permissions**: Spatie Laravel Permission
- **Monitoring**: Sentry + Laravel Pulse
- **Testing**: Pest 4

## Requirements

- PHP >= 8.4
- Composer >= 2.0
- Node.js >= 18.0
- NPM >= 9.0
- SQLite (default) or PostgreSQL

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd onpageiq
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Set up database**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start development server**
   ```bash
   composer run dev
   # Or use Laravel Herd
   ```

## Configuration

### Required Environment Variables

```env
# OpenAI API
OPENAI_API_KEY=your-api-key

# Stripe Billing
STRIPE_KEY=your-publishable-key
STRIPE_SECRET=your-secret-key
STRIPE_WEBHOOK_SECRET=your-webhook-secret

# OAuth (optional)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
```

### Browser Automation

By default, OnPageIQ uses local Playwright for page rendering. For production, configure Browserless:

```env
BROWSER_DRIVER=browserless
BROWSERLESS_URL=https://your-browserless-instance
BROWSERLESS_TOKEN=your-token
```

### Monitoring

```env
SENTRY_LARAVEL_DSN=your-sentry-dsn
PULSE_ENABLED=true
```

## Running Tests

```bash
php artisan test
```

## Queue Workers

For production, use Laravel Horizon:

```bash
php artisan horizon
```

## API Documentation

See [docs/API.md](docs/API.md) for full API reference.

## Admin Panel

Access the admin panel at `/admin` (requires Super Admin role).

## Monitoring

- **Pulse Dashboard**: `/pulse` (Super Admin only)
- **Horizon Dashboard**: `/horizon` (Super Admin only)

## Subscription Tiers

| Tier | Projects | Team Size | Features |
|------|----------|-----------|----------|
| Free | 1 | 1 | Spelling only |
| Pro | Unlimited | 1 | All checks, PDF export, API |
| Team | Unlimited | 10 | + Departments, priority queue |
| Enterprise | Unlimited | Unlimited | + SSO, dedicated support |

## License

Proprietary - All rights reserved.
