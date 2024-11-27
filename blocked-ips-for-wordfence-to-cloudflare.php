<?php

/*
Plugin Name: Blocked IPs for Wordfence to Cloudflare
Description: This plugin takes blocked IPs from Wordfence and adds them to the Cloudflare firewall blocked list.
Version: 1.5.2
Author: ITCS
Author URI: https://itcs.services/
License: GPLv2 or later
Text Domain: blocked-ips-for-wordfence-to-cloudflare
*/
// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Include Freemius SDK.
if ( !function_exists( 'wor_fs' ) ) {
    function wor_fs() {
        global $wor_fs;
        if ( !isset( $wor_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $wor_fs = fs_dynamic_init( array(
                'id'             => '13207',
                'slug'           => 'wordfence2cloudflare',
                'type'           => 'plugin',
                'public_key'     => 'pk_ed1eec939e12cfd4b144c98c2adae',
                'is_premium'     => false,
                'premium_suffix' => 'PremiumPlus',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                    'slug'    => 'wtc-settings',
                    'support' => false,
                    'parent'  => array(
                        'slug' => 'options-general.php',
                    ),
                ),
                'is_live'        => true,
            ) );
        }
        return $wor_fs;
    }

    wor_fs();
    do_action( 'wor_fs_loaded' );
}
// Define plugin constants.
define( 'WTC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WTC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
// Include necessary files.
require_once WTC_PLUGIN_DIR . 'includes/helpers.php';
require_once WTC_PLUGIN_DIR . 'includes/admin-settings.php';
require_once WTC_PLUGIN_DIR . 'includes/blocked-ips.php';
require_once WTC_PLUGIN_DIR . 'includes/ajax-handlers.php';
require_once WTC_PLUGIN_DIR . 'includes/scripts.php';
// Activation hook to schedule cron job.
register_activation_hook( __FILE__, 'wtc_activate_plugin' );
function wtc_activate_plugin() {
    wtc_schedule_cron_job();
}

// Deactivation hook to clear scheduled cron job.
register_deactivation_hook( __FILE__, 'wtc_deactivate_plugin' );
function wtc_deactivate_plugin() {
    wp_clear_scheduled_hook( 'wtc_check_new_blocked_ips' );
}

// Schedule the cron job if not already scheduled.
function wtc_schedule_cron_job() {
    if ( !wp_next_scheduled( 'wtc_check_new_blocked_ips' ) ) {
        $cron_interval = get_option( 'cron_interval', 'hourly' );
        if ( $cron_interval !== 'none' ) {
            wp_schedule_event( time(), $cron_interval, 'wtc_check_new_blocked_ips' );
        }
    }
}

// Plugin initialization.
function wtc_init_plugin() {
    // Check and create custom tables.
    wtc_check_custom_tables();
    // Enqueue scripts and styles.
    add_action( 'admin_enqueue_scripts', 'wtc_enqueue_scripts' );
}

add_action( 'init', 'wtc_init_plugin' );
// Uninstall hook for cleanup.
if ( function_exists( 'register_uninstall_hook' ) ) {
    register_uninstall_hook( __FILE__, 'wtc_uninstall_plugin' );
}