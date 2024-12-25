<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pssx_enqueue_scripts( $hook ) {
    // Only enqueue on your plugin's admin page.
    error_log( '[PSSX] hook => ' . $hook );

    if ( 'settings_page_pss-settings' !== $hook ) {
        return;
    }

    // Define plugin URL if not already.
    if ( ! defined( 'PSSX_PLUGIN_URL' ) ) {
        define( 'PSSX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    }

    // CHANGED: Now referencing local copies instead of remote CDNs:
    wp_register_script(
        'pssx-datatables-js',
        PSSX_PLUGIN_URL . 'assets/js/jquery.dataTables.min.js',  // local version
        array( 'jquery' ),
        '1.11.5',
        true
    );
    wp_register_style(
        'pssx-datatables-css',
        PSSX_PLUGIN_URL . 'assets/css/jquery.dataTables.min.css',  // local version
        array(),
        '1.11.5'
    );

    wp_enqueue_script( 'pssx-datatables-js' );
    wp_enqueue_style( 'pssx-datatables-css' );

    // Enqueue custom script.
    wp_enqueue_script(
        'pssx-custom-script',
        PSSX_PLUGIN_URL . 'js/custom-script.js',
        array( 'jquery', 'pssx-datatables-js' ),
        true
    );

    // Generate the nonce.
    $pssx_ips_tab_nonce = wp_create_nonce( 'pssx_ips_tab_action' );

    // Pass the nonce and AJAX URL to JavaScript.
    wp_localize_script( 'pssx-custom-script', 'pssx_ajax_object', array(
        'ajax_url'          => admin_url( 'admin-ajax.php' ),
        'pssx_ips_tab_nonce' => $pssx_ips_tab_nonce,
    ) );
}

// CHANGED: Admin inline styles approach
function pssx_admin_inline_styles() {
    // We create an inline style handle
    wp_register_style( 'pssx-admin-inline', false );
    wp_enqueue_style( 'pssx-admin-inline' );

    // Moved the old echo "<style> ... " from admin-settings to an inline style
    $inline_css = '
    .nav-tab.disabled {
        color: #a7aaad;
        pointer-events: none;
        cursor: default;
    }
    input[disabled], select[disabled], textarea[disabled] {
        background-color: #f1f1f1;
        color: #a7aaad;
    }
    ';
    wp_add_inline_style( 'pssx-admin-inline', $inline_css );
}

/**
 * Example inline style for the Blocked IPs tab (instead of inline <style>).
 */
function pssx_blocked_ips_inline_style() {
    // Only add these styles when we are on the "Blocked IPs" tab
    // Checking GET['tab'] might be enough
    $current_screen = get_current_screen();
    if ( isset( $current_screen->id ) && 'settings_page_pss-settings' === $current_screen->id ) {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
        if ( $active_tab === 'pssx-ips' ) {
            $ips_css = '
                .pssx-checkbox-column {
                    width: 50px;
                    text-align: center;
                }
                .pssx-checkbox-column-header {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    width:40px;
                }
            ';
            wp_add_inline_style( 'pssx-admin-inline', $ips_css );
        }
    }
}
