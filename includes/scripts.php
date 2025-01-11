<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hook all our admin enqueues into WordPress.
 * Make sure these action calls exist in your plugin initialization logic, for example:
 *
 * add_action( 'admin_enqueue_scripts', 'pssx_enqueue_scripts' );
 * add_action( 'admin_enqueue_scripts', 'pssx_admin_inline_styles' );
 * add_action( 'admin_enqueue_scripts', 'pssx_blocked_ips_inline_style' );
 */

/**
 * Enqueue scripts and styles for the PSSX plugin on admin pages.
 *
 * @param string $hook The current admin page hook.
 */
function pssx_enqueue_scripts( $hook ) {
    // Only load on our plugin settings page (e.g. 'settings_page_pss-settings').
    if ( 'settings_page_pss-settings' !== $hook ) {
        return;
    }

    // Ensure a base plugin URL constant is defined.
    if ( ! defined( 'PSSX_PLUGIN_URL' ) ) {
        define( 'PSSX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    }

    /**
     * REGISTER & ENQUEUE: DataTables (local copies instead of CDN)
     */
    wp_register_script(
        'pssx-datatables-js',
        PSSX_PLUGIN_URL . 'assets/js/jquery.dataTables.min.js',
        array( 'jquery' ),
        '1.11.5',
        true,  // load in footer
        array(  // WordPress 6.3+ supports adding attributes like async/defer here
            //'async' => true,
            //'defer' => true,
        )
    );
    wp_register_style(
        'pssx-datatables-css',
        PSSX_PLUGIN_URL . 'assets/css/jquery.dataTables.min.css',
        array(),
        '1.11.5'
    );

    wp_enqueue_script( 'pssx-datatables-js' );
    wp_enqueue_style( 'pssx-datatables-css' );

    /**
     * REGISTER & ENQUEUE: Custom plugin script
     */
    wp_register_script(
        'pssx-custom-script',
        PSSX_PLUGIN_URL . 'js/custom-script.js',
        array( 'jquery', 'pssx-datatables-js' ),
        '1.0.0',
        true,
        array(  
            // Example if you want to defer or async:
            //'async' => true,
            //'defer' => true,
        )
    );
    wp_enqueue_script( 'pssx-custom-script' );

    // Generate a nonce for tab usage (if needed).
    $pssx_ips_tab_nonce = wp_create_nonce( 'pssx_ips_tab_action' );

    // Localize data to pass to your script.
    wp_localize_script(
        'pssx-custom-script',
        'pssx_ajax_object',
        array(
            'ajax_url'           => admin_url( 'admin-ajax.php' ),
            'pssx_ips_tab_nonce' => $pssx_ips_tab_nonce,
        )
    );
}

/**
 * Enqueue base admin inline styles for the PSSX plugin.
 *
 * @param string $hook The current admin page hook.
 */
function pssx_admin_inline_styles( $hook ) {
    // Only load on our plugin settings page.
    if ( 'settings_page_pss-settings' !== $hook ) {
        return;
    }

    // Register (but do not specify a file), then enqueue a handle for inline CSS.
    wp_register_style( 'pssx-admin-inline', false );
    wp_enqueue_style( 'pssx-admin-inline' );

    // Add inline CSS.
    $base_inline_css = '
        .nav-tab.disabled {
            color: #a7aaad;
            pointer-events: none;
            cursor: default;
        }
        input[disabled],
        select[disabled],
        textarea[disabled] {
            background-color: #f1f1f1;
            color: #a7aaad;
        }
    ';
    wp_add_inline_style( 'pssx-admin-inline', $base_inline_css );
}

/**
 * Enqueue inline styles specifically for the "Blocked IPs" tab.
 * Includes nonce verification & sanitized input to satisfy PHPCS warnings.
 *
 * @param string $hook The current admin page hook.
 */
function pssx_blocked_ips_inline_style( $hook ) {
    // Only load on our plugin settings page.
    if ( 'settings_page_pss-settings' !== $hook ) {
        return;
    }

    // Because we rely on the pssx-admin-inline handle, ensure it's enqueued.
    wp_register_style( 'pssx-admin-inline', false );
    wp_enqueue_style( 'pssx-admin-inline' );

    // Check current screen.
    $current_screen = get_current_screen();
    if ( ! isset( $current_screen->id ) || 'settings_page_pss-settings' !== $current_screen->id ) {
        return;
    }

    // (1) Verify the nonce from $_REQUEST (if you're using one for tab usage).
    $nonce = isset( $_REQUEST['pssx_ips_tab_nonce'] )
        ? sanitize_text_field( wp_unslash( $_REQUEST['pssx_ips_tab_nonce'] ) )
        : '';
    if ( ! wp_verify_nonce( $nonce, 'pssx_ips_tab_action' ) ) {
        // If the nonce is invalid or missing, decide how to handle it.
        return;
    }

    // (2) Retrieve and sanitize the 'tab' GET parameter.
    $active_tab = isset( $_GET['tab'] )
        ? sanitize_text_field( wp_unslash( $_GET['tab'] ) )
        : '';

    // (3) If this is the "Blocked IPs" tab, add the relevant CSS.
    if ( 'pssx-ips' === $active_tab ) {
        $ips_inline_css = '
            .pssx-checkbox-column {
                width: 50px;
                text-align: center;
            }
            .pssx-checkbox-column-header {
                display: flex;
                justify-content: center;
                align-items: center;
                width: 40px;
            }
        ';
        wp_add_inline_style( 'pssx-admin-inline', $ips_inline_css );
    }
}
