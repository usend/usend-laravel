# usend for Laravel

The official Laravel package for [usend](https://usend.et): a `usend` mail transport (your existing `Mail::` and Mailable code keeps
working) and a ready-made `Usend\Client` for everything else the API does. Laravel 10, 11 or 12, PHP 8.1 or newer.

## Install

```bash
composer require usend/laravel
```

The service provider registers itself. Add your [API key](https://usend.et/docs/api-keys) and choose the mailer:

```ini
USEND_API_KEY=us_123456789
MAIL_MAILER=usend
MAIL_FROM_ADDRESS=hello@acme.com
MAIL_FROM_NAME=Acme
```

`config/mail.php`:

```php
'mailers' => [
    'usend' => ['transport' => 'usend'],
],
```

The From address must belong to a [verified domain](https://usend.et/docs/domains/verify).

## Send a Mailable

```php
Mail::to($user)->send(new WelcomeMail($user));
```

Cc, Bcc, Reply-To, custom headers and attachments are carried over. The message id usend returns is available as the
`X-Usend-Email-Id` header of the sent message.

## Use the full API

```php
use Usend\Client;

app(Client::class)->emails->send([
    'from' => 'Acme <hello@acme.com>',
    'to' => ['ann@example.com'],
    'subject' => 'Welcome',
    'html' => '<p>Thanks for signing up.</p>',
], "welcome-{$user->id}"); // idempotency key: a retried queue job never sends twice
```

`Usend\Client` is the [usend PHP SDK](https://github.com/usend/usend-php): templates, batches, scheduling, broadcasts, webhooks verification and the
rest of the API.

## Configuration

`php artisan vendor:publish --tag=usend-config` copies `config/usend.php` (`USEND_API_KEY`, and `USEND_BASE_URL` for a self-hosted usend).

## License

MIT
