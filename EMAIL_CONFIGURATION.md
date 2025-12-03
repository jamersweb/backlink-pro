# Email Configuration Guide for Docker

## Current Status

**❌ Emails are NOT being sent** - They are being logged to `storage/logs/laravel.log`

## How to Enable Real Email Sending

### Option 1: Using Gmail SMTP (Recommended for Development)

1. **Update `.env` file** in your project root:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Backlink Pro"
```

**Note:** For Gmail, you need to:
- Enable 2-Factor Authentication
- Generate an "App Password" (not your regular password)
- Use the App Password in `MAIL_PASSWORD`

### Option 2: Using Mailtrap (Recommended for Testing)

Mailtrap is perfect for testing - emails are caught and displayed in a web interface.

1. **Sign up at:** https://mailtrap.io (free account available)

2. **Update `.env` file:**

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@backlinkpro.com
MAIL_FROM_NAME="Backlink Pro"
```

### Option 3: Using SendGrid (Production Ready)

1. **Sign up at:** https://sendgrid.com

2. **Update `.env` file:**

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Backlink Pro"
```

### Option 4: Using Mailgun (Production Ready)

1. **Sign up at:** https://mailgun.com

2. **Update `.env` file:**

```env
MAIL_MAILER=mailgun
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Backlink Pro"
```

3. **Add to `config/services.php`:**

```php
'mailgun' => [
    'domain' => env('MAILGUN_DOMAIN'),
    'secret' => env('MAILGUN_SECRET'),
    'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    'scheme' => 'https',
],
```

4. **Add to `.env`:**

```env
MAILGUN_DOMAIN=your-domain.com
MAILGUN_SECRET=your-mailgun-secret-key
```

## After Configuration

1. **Clear config cache:**
```bash
docker-compose exec app php artisan config:clear
```

2. **Test email sending:**
```bash
docker-compose exec app php artisan tinker
# Then run:
Mail::raw('Test email', function ($message) {
    $message->to('your-email@example.com')->subject('Test');
});
```

3. **Check logs if email fails:**
```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

## View Logged Emails (Current Setup)

Since emails are currently logged, you can view them:

```bash
# View recent log entries
docker-compose exec app tail -n 100 storage/logs/laravel.log

# Or check inside container
docker-compose exec app bash
cat storage/logs/laravel.log | grep -A 20 "Verification"
```

## Quick Setup for Testing (Mailtrap)

1. Go to https://mailtrap.io and create free account
2. Copy SMTP credentials from inbox
3. Update `.env` with Mailtrap credentials
4. Clear config: `docker-compose exec app php artisan config:clear`
5. Register a new user - email will appear in Mailtrap inbox!

## Production Setup

For production, use:
- **SendGrid** (easy setup, good deliverability)
- **Mailgun** (powerful, good for high volume)
- **Amazon SES** (cost-effective for high volume)
- **Postmark** (excellent deliverability, great for transactional emails)

