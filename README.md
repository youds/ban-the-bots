# BanTheBots PHP Kit

Take control back on the web by silently trapping non‑compliant crawlers and abusive bots. This PHP package integrates with the BanTheBots cloud API to:

- Drop a hidden "blackhole" link in your pages that only misbehaving bots will follow
- Record the offending IP/user‑agent with the central API
- Deny further access to flagged clients with a 403 and a friendly error page
- Keep good crawlers (that obey `robots.txt`) unaffected

This README explains installation, configuration, and usage.


## Requirements

- PHP 7.1+ recommended (the library uses class constant visibility available since PHP 7.1)
- cURL extension enabled
- Web server user must have write permission to your public web root (or the directory where `robots.txt` lives)

Note: The `composer.json` currently declares `php ">=5.4"`, but you should run PHP 7.1 or newer for compatibility with this code.


## Installation

### Composer (recommended)

```bash
composer require youds/ban-the-bots
```

Composer will autoload `src/BanTheBots.class.php` for you.

### Manual (no Composer)

- Copy `src/BanTheBots.class.php` into your project
- Require it in your bootstrap:

```php
require_once __DIR__ . '/src/BanTheBots.class.php';
```


## Quick start

1. Instantiate the library as early as possible in your request lifecycle (before any output). Optionally pass configuration (see below).
2. Call `apply()` to enforce a decision for the current request.
3. In your layout/footer, call `outputBlackHole()` once to render the hidden link that naughty bots will follow.

Example minimal integration:

```php
<?php
use \BanTheBots; // if you are using a namespace/import system; otherwise reference the class directly

$btb = new BanTheBots([
    // See Configuration section for details
    'checkInterval' => 4,
    'errorMessage'  => 'BanTheBots API error: ',
    'robotsPath'    => $_SERVER['DOCUMENT_ROOT'] . '/',
]);

$btb->apply();
?>
<!doctype html>
<html>
  <head>...</head>
  <body>
    ... your page content ...
    <?php $btb->outputBlackHole(); ?>
  </body>
</html>
```


## Configuration

Create the instance with an associative array. All options are optional; sensible defaults are used when omitted.

- `checkInterval` (int, seconds, default `4`)
  - Minimum interval between API checks per session. The library stores a timestamp in `$_SESSION['visited']` and only hits the API if the interval has elapsed or the client is currently flagged.

- `errorMessage` (string, default `"BanTheBots API error: "`)
  - Prefix for errors logged to the PHP error log if the API call fails.

- `robotsPath` (string, default is intended to be the directory that contains `robots.txt`)
  - Path to the directory where `robots.txt` lives and where the library can drop helper files. This should be a filesystem path and usually equals your public web root.
  - Examples:
    - `$_SERVER['DOCUMENT_ROOT'] . '/'`
    - `/var/www/html/`
  - Ensure this directory is writable by your web server user.


## What the library does

On construction (`new BanTheBots($config)`), the library will:

1. Start a session if not already started.
2. Generate or reuse a random per‑site "blackhole" path (e.g., `a1b2c3.php`) and persist it in a small PHP file next to `robots.txt`.
3. Create the blackhole endpoint file if it doesn’t exist. When a client requests this URL, the library:
   - Reports the IP and base64‑encoded user agent to the cloud API `POST /v1/bad-bots/create` (performed here via a `file_get_contents()` GET call), then
   - Responds with `HTTP/1.1 403 Forbidden`,
   - Redirects to a human‑readable page at `https://api.banthebots.cloud/blackhole?returnTo=...`, and
   - Outputs a friendly error page.
4. Ensure `robots.txt` contains a `Disallow: /<blackhole>` rule so compliant crawlers will avoid it.

On each request when you call `apply()`:

- If the session was recently checked, it won’t call the API again until `checkInterval` seconds elapse.
- Otherwise it queries the API: `GET https://api.banthebots.cloud/v1/bad-bots/read?ip=<ip>&userAgent=<base64 ua>`.
- If the API flags the client as a bad bot, the request is terminated with a 403 and redirect as above.

When you call `outputBlackHole()` in your footer/layout:

- A hidden snippet is printed that includes the unique blackhole URL. Human visitors won’t see it, but naive crawlers that click every link will follow it and self‑identify as non‑compliant.


## Security and permissions

- Give your web server user write access to the directory specified by `robotsPath`. The library needs to create:
  - A small PHP file holding the generated blackhole filename
  - The blackhole endpoint script itself
  - `robots.txt` (if it doesn’t exist)
- Do not expose directory listings in your web root.
- Review server‑side caching or reverse proxy behavior to ensure the dynamic 403 behavior is respected.


## Troubleshooting

- I get permission errors creating files
  - Ensure `robotsPath` points to a writable directory (usually `$_SERVER['DOCUMENT_ROOT'] . '/'`).
  - Check file ownership (`www-data`, `apache`, `nginx`, etc.).

- The package says PHP 5.4+ but it fails on my older PHP
  - Use PHP 7.1 or newer. The class uses features introduced in 7.1.

- Nothing happens when I call `apply()`
  - Make sure sessions are enabled and not started too late. The library starts a session if needed, but your framework might interfere if sessions are disabled.
  - Confirm the server can reach `https://api.banthebots.cloud` from your environment (no firewall blocks, valid SSL CA bundle for cURL).

- Search engines are hitting my blackhole
  - Verify `robots.txt` is present at `https://your-domain/robots.txt` and contains the `Disallow` rule for the generated path.


## Example framework integration

- WordPress: instantiate in `wp-config.php` or a small mu‑plugin before output; echo `outputBlackHole()` in your theme’s `footer.php`.
- Laravel/Symfony: register a small middleware executed early that creates the instance and calls `apply()`; in your base layout (Blade/Twig) call `outputBlackHole()`.
- Plain PHP: see the Quick start example above.


## API endpoints used

- Read status: `GET https://api.banthebots.cloud/v1/bad-bots/read?ip=<ip>&userAgent=<base64 ua>`
- Create on blackhole hit: `GET https://api.banthebots.cloud/v1/bad-bots/create?ip=<ip>&userAgent=<base64 ua>`

These are invoked by the library; you don’t need to call them manually.


## Roadmap / contributions

- Multi‑language ports are welcome — re‑implement based on this PHP kit
- Improvements to configuration validation and PHP version constraints
- Tests and CI integration

Contributions are welcome via pull requests.


## License

This project is licensed under the terms of the MIT License. See `LICENSE` for details.

