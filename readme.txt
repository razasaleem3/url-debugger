=== URL Debugger ===
Contributors:      Raza Saleem
Tags:              debug, errors, php, development, logging
Requires at least: 5.0
Tested up to:      6.7
Requires PHP:      7.4
Stable tag:        1.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Enable PHP error output on any production URL using a private secret token — no admin login needed for your developer.

== Description ==

**URL Debugger** lets site owners share a secret debug URL with a developer so that PHP errors are displayed directly in the browser — without giving the developer a WordPress login or touching `wp-config.php`.

**How it works:**

1. Activate the plugin.
2. Go to **Settings → URL Debugger** to copy your unique secret token.
3. Append `?debug_key=YOUR_TOKEN` to any URL on your site (a page, post, or admin screen).
4. PHP errors appear inline. Errors are also written to `wp-content/debug.log`.
5. Regenerate the token any time from the settings page.

**Security model:**

* The debug token is a randomly generated 48-character hex string unique to your installation.
* Only someone who knows the token can enable debug mode — it is never exposed publicly.
* Regenerate the token instantly if it is ever leaked.
* No user login is required, making it ideal for developers who only have FTP/plugin access.

**What gets enabled when the token is present:**

* `error_reporting(E_ALL)` — all PHP errors, warnings, and notices
* `display_errors` — errors shown in the browser
* `log_errors` — errors written to `debug.log`
* WordPress `WP_DEBUG`, `WP_DEBUG_LOG`, `WP_DEBUG_DISPLAY` constants (if not already set in `wp-config.php`)
* A red **DEBUG ON** badge in the page footer so it is obvious debug mode is active

**Note:** Because this is a standard WordPress plugin (not a must-use plugin), PHP errors that occur *during plugin loading* (before `plugins_loaded`) may not be caught. For those edge cases, direct `wp-config.php` access is still needed.

== Installation ==

1. Upload the `url-debugger` folder to `/wp-content/plugins/`.
2. Activate the plugin via **Plugins → Installed Plugins**.
3. Go to **Settings → URL Debugger** to find your secret debug URL.
4. Share that URL with your developer.

== Frequently Asked Questions ==

= Do I need to edit wp-config.php? =

No. The plugin handles everything via `ini_set()` and WordPress constants at runtime.

= Can the token be changed? =

Yes. Click **Regenerate Token** in **Settings → URL Debugger** at any time.

= Is the token visible to visitors? =

No. The token is stored in the database and only shown on the settings page to administrators.

= What happens when I deactivate the plugin? =

Debug mode is immediately disabled for all URLs. The token is kept in the database so it is restored if you reactivate.

= What happens when I delete the plugin? =

The token is deleted from the database automatically.

= Will it slow down my site? =

No. The plugin does nothing on normal page loads — it only activates when the correct `debug_key` parameter is present in the URL.

== Screenshots ==

1. The Settings page — set or generate your secret debug token and copy the ready-to-share debug URL.
2. Without the debug key: WordPress shows only the generic "A critical error has occurred" message — the real error is hidden.
3. With `?debug_key=TOKEN` appended: the actual PHP parse error is revealed inline, making it easy to diagnose and fix.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
First release.
