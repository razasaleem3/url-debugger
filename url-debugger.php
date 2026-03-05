<?php
/**
 * Plugin Name:       URL Debugger
 * Plugin URI:        https://github.com/razasaleem3/url-debugger
 * Description:       Enables PHP error display on any URL using a private secret token. Safe for production — no admin login required, just share the token with your developer.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Raza Saleem
 * Author URI:        https://github.com/razasaleem3
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       url-debugger
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'URL_DEBUGGER_VERSION',    '1.0.0' );
define( 'URL_DEBUGGER_OPTION_KEY', 'url_debugger_token' );

// ---------------------------------------------------------------------------
// Activation — default token is "1" so ?debug_key=1 works out of the box
// ---------------------------------------------------------------------------
register_activation_hook( __FILE__, function () {
    if ( ! get_option( URL_DEBUGGER_OPTION_KEY ) ) {
        update_option( URL_DEBUGGER_OPTION_KEY, '1', false );
    }
} );

function url_debugger_generate_token(): string {
    return bin2hex( random_bytes( 24 ) ); // 48-char hex string, for "Generate Random" button
}

// ---------------------------------------------------------------------------
// Core debug logic — runs as early as possible to capture more errors
// ---------------------------------------------------------------------------
add_action( 'plugins_loaded', function () {
    $token = get_option( URL_DEBUGGER_OPTION_KEY, '' );

    if ( empty( $token ) ) {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- token comparison IS the auth check; no nonce applicable here.
    $supplied = isset( $_GET['debug_key'] ) ? sanitize_text_field( wp_unslash( $_GET['debug_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    // Constant-time comparison prevents timing attacks
    if ( ! hash_equals( $token, $supplied ) ) {
        return;
    }

    // PHP error display — required for this plugin's core purpose.
    error_reporting( E_ALL ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting
    ini_set( 'display_errors',         '1' ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
    ini_set( 'display_startup_errors', '1' ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
    ini_set( 'log_errors',             '1' ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged

    // WordPress debug constants (only if not already locked in wp-config.php)
    if ( ! defined( 'WP_DEBUG' ) )         define( 'WP_DEBUG',         true );
    if ( ! defined( 'WP_DEBUG_LOG' ) )     define( 'WP_DEBUG_LOG',     true );
    if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) define( 'WP_DEBUG_DISPLAY', true );

    // Visual badge so it's obvious debug mode is on
    $badge = '<div id="url-debugger-badge" style="'
        . 'position:fixed;bottom:12px;right:12px;z-index:999999;'
        . 'background:#cc1818;color:#fff;font:bold 11px/1 monospace;'
        . 'padding:6px 10px;border-radius:4px;box-shadow:0 2px 6px rgba(0,0,0,.4);'
        . 'pointer-events:none;">DEBUG ON</div>';

    add_action( 'wp_footer',    function () use ( $badge ) { echo $badge; }, 999 ); // phpcs:ignore WordPress.Security.EscapeOutput
    add_action( 'admin_footer', function () use ( $badge ) { echo $badge; }, 999 ); // phpcs:ignore WordPress.Security.EscapeOutput
}, 1 ); // priority 1 = very early

// ---------------------------------------------------------------------------
// Admin settings page
// ---------------------------------------------------------------------------
add_action( 'admin_menu', function () {
    add_options_page(
        __( 'URL Debugger', 'url-debugger' ),
        __( 'URL Debugger', 'url-debugger' ),
        'manage_options',
        'url-debugger',
        'url_debugger_render_settings'
    );
} );

function url_debugger_render_settings(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Handle custom token save
    if (
        isset( $_POST['url_debugger_save'] ) &&
        check_admin_referer( 'url_debugger_save_action', 'url_debugger_nonce' )
    ) {
        $custom = sanitize_text_field( wp_unslash( $_POST['url_debugger_custom_token'] ?? '' ) );
        if ( $custom !== '' ) {
            update_option( URL_DEBUGGER_OPTION_KEY, $custom, false );
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html__( 'Token saved.', 'url-debugger' )
                . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>'
                . esc_html__( 'Token cannot be empty.', 'url-debugger' )
                . '</p></div>';
        }
    }

    // Handle random token generation
    if (
        isset( $_POST['url_debugger_regenerate'] ) &&
        check_admin_referer( 'url_debugger_save_action', 'url_debugger_nonce' )
    ) {
        update_option( URL_DEBUGGER_OPTION_KEY, url_debugger_generate_token(), false );
        echo '<div class="notice notice-success is-dismissible"><p>'
            . esc_html__( 'Random token generated. Update anyone you shared the old URL with.', 'url-debugger' )
            . '</p></div>';
    }

    $token     = get_option( URL_DEBUGGER_OPTION_KEY, '1' );
    $debug_url = add_query_arg( 'debug_key', $token, home_url( '/' ) );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'URL Debugger', 'url-debugger' ); ?></h1>

        <p><?php esc_html_e( 'Append the debug key to any URL on this site to enable full PHP error output — no login required.', 'url-debugger' ); ?></p>

        <form method="post">
            <?php wp_nonce_field( 'url_debugger_save_action', 'url_debugger_nonce' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="url_debugger_custom_token"><?php esc_html_e( 'Debug token', 'url-debugger' ); ?></label></th>
                    <td>
                        <input
                            type="text"
                            id="url_debugger_custom_token"
                            name="url_debugger_custom_token"
                            value="<?php echo esc_attr( $token ); ?>"
                            style="width:320px;font-family:monospace;"
                            autocomplete="off"
                        />
                        <p class="description">
                            <?php esc_html_e( 'Default is "1". Set any value you like, or click Generate Random for a strong token.', 'url-debugger' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Debug URL', 'url-debugger' ); ?></th>
                    <td>
                        <input
                            type="text"
                            readonly
                            onfocus="this.select()"
                            value="<?php echo esc_attr( $debug_url ); ?>"
                            style="width:560px;font-family:monospace;"
                        />
                        <p class="description">
                            <?php esc_html_e( 'Add ?debug_key=TOKEN to any URL on your site. Share with your developer — no WP login needed.', 'url-debugger' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <p>
                <?php submit_button( __( 'Save Token', 'url-debugger' ), 'primary', 'url_debugger_save', false ); ?>
                &nbsp;
                <?php submit_button( __( 'Generate Random Token', 'url-debugger' ), 'secondary', 'url_debugger_regenerate', false ); ?>
            </p>
        </form>

        <h2><?php esc_html_e( 'How to use', 'url-debugger' ); ?></h2>
        <ol>
            <li><?php esc_html_e( 'Set your token above (default: 1) and save.', 'url-debugger' ); ?></li>
            <li><?php esc_html_e( 'Add ?debug_key=TOKEN to any page, post, or admin URL.', 'url-debugger' ); ?></li>
            <li><?php esc_html_e( 'PHP errors appear inline. No WordPress login needed.', 'url-debugger' ); ?></li>
            <li><?php esc_html_e( 'Errors are also written to wp-content/debug.log.', 'url-debugger' ); ?></li>
        </ol>

        <p class="description" style="color:#cc1818;">
            <?php esc_html_e( '⚠ Anyone who knows the token can see PHP errors on your site. Use a strong token on public/shared sites.', 'url-debugger' ); ?>
        </p>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Plugin action link — quick access from the Plugins list page
// ---------------------------------------------------------------------------
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( array $links ): array {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=url-debugger' ) ) . '">'
        . esc_html__( 'Settings', 'url-debugger' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
} );
