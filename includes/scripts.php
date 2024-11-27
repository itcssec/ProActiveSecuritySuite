<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue scripts and styles.
function wtc_enqueue_scripts( $hook ) {
    // Only enqueue on your plugin's admin page.
    if ( 'settings_page_wtc-settings' !== $hook ) {
        return;
    }

    // Enqueue DataTables library.
    wp_enqueue_script(
        'wtc-datatables',
        'https://cdn.datatables.net/v/bs5/dt-1.11.3/datatables.min.js',
        array( 'jquery' ),
        '1.11.3',
        true
    );
    wp_enqueue_style(
        'wtc-datatables-css',
        'https://cdn.datatables.net/v/bs5/dt-1.11.3/datatables.min.css',
        array(),
        '1.11.3'
    );

    // Enqueue custom script.
    wp_enqueue_script(
        'wtc-custom-script',
        WTC_PLUGIN_URL . 'js/custom-script.js',
        array( 'jquery', 'wtc-datatables' ),
        '1.0.0',
        true
    );

    // Generate the nonce.
    $wtc_ips_tab_nonce = wp_create_nonce( 'wtc_ips_tab_action' );

    // Pass the nonce and AJAX URL to JavaScript.
    wp_localize_script( 'wtc-custom-script', 'wtc_ajax_object', array(
        'ajax_url'          => admin_url( 'admin-ajax.php' ),
        'wtc_ips_tab_nonce' => $wtc_ips_tab_nonce,
    ) );
}
add_action( 'admin_enqueue_scripts', 'wtc_enqueue_scripts' );
