<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue scripts and styles.
function pssx_enqueue_scripts( $hook ) {
    // Only enqueue on your plugin's admin page.
    if ( 'settings_page_pss-settings' !== $hook ) {
        return;
    }

    // Define plugin URL if not already.
    if ( ! defined( 'PSSX_PLUGIN_URL' ) ) {
        define( 'PSSX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    }

    // Enqueue DataTables library from local files.
    wp_enqueue_script(
        'pssx-datatables',
        PSSX_PLUGIN_URL . 'assets/js/datatables.min.js',
        array( 'jquery' ),
        '1.11.3',
        true
    );
    wp_enqueue_style(
        'pssx-datatables-css',
        PSSX_PLUGIN_URL . 'assets/css/datatables.min.css',
        array(),
        '1.11.3'
    );

    // Enqueue custom script.
    wp_enqueue_script(
        'pssx-custom-script',
        PSSX_PLUGIN_URL . 'js/custom-script.js',
        array( 'jquery', 'pssx-datatables' ),
        '1.0.0',
        true
    );

    // Generate the nonce.
    $pssx_ips_tab_nonce = wp_create_nonce( 'wtc_ips_tab_action' );

    // Pass the nonce and AJAX URL to JavaScript.
    wp_localize_script( 'pssx-custom-script', 'pssx_ajax_object', array(
        'ajax_url'          => admin_url( 'admin-ajax.php' ),
        'pssx_ips_tab_nonce' => $pssx_ips_tab_nonce,
    ) );
}
add_action( 'admin_enqueue_scripts', 'pssx_enqueue_scripts' );
