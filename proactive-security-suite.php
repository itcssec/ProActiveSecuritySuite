<?php

/*
Plugin Name: Proactive Security Suite
Description: Enhance your WordPress website’s security with the ProActive Security Suite. This powerful plugin offers advanced security features including automatic IP blocking, an advanced rule builder, traffic analysis, and seamless integration with services like Cloudflare, AbuseIPDB, and Whatismybrowser.com. ProActive Security Suite provides proactive defense mechanisms to protect your site from malicious traffic and potential threats before they reach your server.
Version: 1.5.9.2
Author: ITCS
Author URI: https://github.com/itcssec/ProActiveSecuritySuite
License: GPLv2 or later
Text Domain: proactive-security-suite
*/
// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Include Freemius SDK.
if ( !function_exists( 'pssx_fs' ) ) {
    function pssx_fs() {
        global $pssx_fs;
        if ( !isset( $pssx_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
            $pssx_fs = fs_dynamic_init( array(
                'id'             => '13207',
                'slug'           => 'proactive-security-suite',
                'type'           => 'plugin',
                'public_key'     => 'pk_ed1eec939e12cfd4b144c98c2adae',
                'is_premium'     => false,
                'premium_suffix' => 'PremiumPlus',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                    'slug'    => 'pss-settings',
                    'support' => false,
                    'parent'  => array(
                        'slug' => 'options-general.php',
                    ),
                ),
                'is_live'        => true,
            ) );
        }
        return $pssx_fs;
    }

    pssx_fs();
    do_action( 'pssx_fs_loaded' );
}
// Define plugin constants.
define( 'PSSX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PSSX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
// Include necessary files.
require_once PSSX_PLUGIN_DIR . 'includes/helpers.php';
require_once PSSX_PLUGIN_DIR . 'includes/admin-settings.php';
require_once PSSX_PLUGIN_DIR . 'includes/blocked-ips.php';
require_once PSSX_PLUGIN_DIR . 'includes/ajax-handlers.php';
require_once PSSX_PLUGIN_DIR . 'includes/scripts.php';
// Activation hook to schedule cron job.
register_activation_hook( __FILE__, 'pssx_activate_plugin' );
function pssx_activate_plugin() {
    pssx_schedule_cron_job();
}

// Deactivation hook to clear scheduled cron job.
register_deactivation_hook( __FILE__, 'pssx_deactivate_plugin' );
function pssx_deactivate_plugin() {
    wp_clear_scheduled_hook( 'pssx_check_new_blocked_ips' );
}

// Schedule the cron job if not already scheduled.
function pssx_schedule_cron_job() {
    if ( !wp_next_scheduled( 'pssx_check_new_blocked_ips' ) ) {
        $cron_interval = get_option( 'cron_interval', 'hourly' );
        if ( $cron_interval !== 'none' ) {
            wp_schedule_event( time(), $cron_interval, 'pssx_check_new_blocked_ips' );
        }
    }
}

// Plugin initialization.
function pssx_init_plugin() {
    pssx_check_custom_tables();
    // Enqueue scripts and styles in admin.
    add_action( 'admin_enqueue_scripts', 'pssx_enqueue_scripts' );
    // CHANGED: Moved admin inline styles from echo to proper enqueuing
    add_action( 'admin_enqueue_scripts', 'pssx_admin_inline_styles' );
    add_action( 'admin_enqueue_scripts', 'pssx_blocked_ips_inline_style' );
}

add_action( 'init', 'pssx_init_plugin' );
// Uninstall hook for cleanup.
if ( function_exists( 'register_uninstall_hook' ) ) {
    register_uninstall_hook( __FILE__, 'pssx_uninstall_plugin' );
}
