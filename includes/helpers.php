<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Function to check and create custom tables.
function wtc_check_custom_tables() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'wtc_blocked_ips';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        blockedTime datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        blockedHits int(11) DEFAULT 0 NOT NULL,
        ip varchar(100) NOT NULL,
        cfResponse text NOT NULL,
        isSent tinyint(1) DEFAULT 0 NOT NULL,
        countryCode varchar(10) DEFAULT '' NOT NULL,
        usageType varchar(100) DEFAULT '' NOT NULL,
        isp varchar(255) DEFAULT '' NOT NULL,
        confidenceScore varchar(10) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Function to update the cron schedule.
function wtc_update_cron_schedule() {
    // Clear any existing scheduled events.
    wp_clear_scheduled_hook( 'wtc_check_new_blocked_ips' );

    $cron_interval = get_option( 'cron_interval', 'hourly' );

    if ( $cron_interval !== 'none' ) {
        wp_schedule_event( time(), $cron_interval, 'wtc_check_new_blocked_ips' );
    }
}

// Add custom cron schedules.
function wtc_custom_cron_schedules( $schedules ) {
    $schedules['5min'] = array(
        'interval' => 300, // 5 minutes in seconds.
        'display'  => __( 'Every 5 Minutes', 'blocked-ips-for-wordfence-to-cloudflare' ),
    );
    $schedules['15min'] = array(
        'interval' => 900, // 15 minutes in seconds.
        'display'  => __( 'Every 15 Minutes', 'blocked-ips-for-wordfence-to-cloudflare' ),
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'wtc_custom_cron_schedules' );
