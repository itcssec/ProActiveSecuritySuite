<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Function to check and create custom tables.
function pssx_check_custom_tables() {
    global $wpdb;

    $blocked_ips_table = $wpdb->prefix . 'pssx_blocked_ips';
    $rules_table       = $wpdb->prefix . 'pssx_rules';
    $charset_collate   = $wpdb->get_charset_collate();

    $sql_blocked_ips = "CREATE TABLE {$blocked_ips_table} (
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
        block_mode varchar(50) DEFAULT 'block' NOT NULL,
        rule_id mediumint(9) DEFAULT NULL,
        rule_details text DEFAULT NULL,
        PRIMARY KEY  (id)
    ) {$charset_collate};";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_blocked_ips );

    $sql_rules = "CREATE TABLE IF NOT EXISTS $rules_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        criteria text NOT NULL,
        action varchar(50) NOT NULL,
        priority int(11) DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta( $sql_rules );
}

// Function to update the cron schedule.
function pssx_update_cron_schedule() {
    // Clear any existing scheduled events.
    wp_clear_scheduled_hook( 'pssx_check_new_blocked_ips' );

    $cron_interval = get_option( 'cron_interval', 'hourly' );

    if ( $cron_interval !== 'none' ) {
        wp_schedule_event( time(), $cron_interval, 'pssx_check_new_blocked_ips' );
    }
}

// Add custom cron schedules.
function pssx_custom_cron_schedules( $schedules ) {
    $schedules['5min'] = array(
        'interval' => 300,
        'display'  => __( 'Every 5 Minutes', 'proactive-security-suite' ),
    );
    $schedules['1min'] = array(
        'interval' => 60,
        'display'  => __( 'Every Minute', 'proactive-security-suite' ),
    );
    $schedules['15min'] = array(
        'interval' => 900,
        'display'  => __( 'Every 15 Minutes', 'proactive-security-suite' ),
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'pssx_custom_cron_schedules' );

