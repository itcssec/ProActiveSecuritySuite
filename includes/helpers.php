<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Function to check and create custom tables.
function wtc_check_custom_tables() {
    global $wpdb;

    $blocked_ips_table = $wpdb->prefix . 'wtc_blocked_ips';
    $rules_table       = $wpdb->prefix . 'wtc_rules';
    $charset_collate = $wpdb->get_charset_collate();

    // Corrected Create Table Statement
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
    // Create Rules Table
    $sql_rules = "CREATE TABLE IF NOT EXISTS $rules_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        criteria text NOT NULL,
        action varchar(50) NOT NULL,
        priority int(11) DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    // After creating other tables
    dbDelta( $sql_rules );

}
add_action( 'init', 'wtc_check_custom_tables' );



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
        'display'  => __( 'Every 5 Minutes', 'proactive-security-suite' ),
    );
    $schedules['1min'] = array(
        'interval' => 60, // 1 minute in seconds.
        'display'  => __( 'Every Minute', 'proactive-security-suite' ),
    );
    $schedules['15min'] = array(
        'interval' => 900, // 15 minutes in seconds.
        'display'  => __( 'Every 15 Minutes', 'proactive-security-suite' ),
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'wtc_custom_cron_schedules' );
