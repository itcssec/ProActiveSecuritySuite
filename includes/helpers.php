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

/**
 * Migrate old wtc_* tables to new pssx_* tables WITHOUT carrying over the old `id` values.
 * Instead, each new row gets a fresh auto-increment ID in the new table.
 *
 * Steps:
 *   A. Truncate the new tables (pssx_*).
 *   B. Insert data from wtc_* tables (excluding `id` in the column list).
 *   C. Drop the old tables.
 */
function pssx_migrate_old_tables() {
    global $wpdb;

    // OLD TABLES
    $old_blocked_ips_table  = $wpdb->prefix . 'wtc_blocked_ips';
    $old_traffic_data_table = $wpdb->prefix . 'wtc_traffic_data';
    $old_rules_table        = $wpdb->prefix . 'wtc_rules';

    // NEW TABLES
    $new_blocked_ips_table  = $wpdb->prefix . 'pssx_blocked_ips';
    $new_traffic_data_table = $wpdb->prefix . 'pssx_traffic_data';
    $new_rules_table        = $wpdb->prefix . 'pssx_rules';

    // 1) Truncate new tables to ensure they are empty and avoid duplicates
    //    (only do this if you are sure you don't need any existing data in them).
    $wpdb->query( "TRUNCATE TABLE `{$new_blocked_ips_table}`" );
    $wpdb->query( "TRUNCATE TABLE `{$new_traffic_data_table}`" );
    $wpdb->query( "TRUNCATE TABLE `{$new_rules_table}`" ); // if you truly have pssx_rules

    // 2) Migrate data from old wtc_blocked_ips -> pssx_blocked_ips (WITHOUT the `id` column).
    $old_blocked_ips_exists = ( $wpdb->get_var(
        $wpdb->prepare( "SHOW TABLES LIKE %s", $old_blocked_ips_table )
    ) === $old_blocked_ips_table );
    $new_blocked_ips_table_esc = esc_sql( $new_blocked_ips_table );
    $old_blocked_ips_table_esc = esc_sql( $old_blocked_ips_table );

    if ( $old_blocked_ips_exists ) {
        $sql_blocked_ips = "
            INSERT INTO `{$new_blocked_ips_table_esc}` (
                blockedTime,
                blockedHits,
                ip,
                cfResponse,
                isSent,
                countryCode,
                usageType,
                isp,
                confidenceScore,
                block_mode,
                rule_id,
                rule_details
            )
            SELECT
                blockedTime,
                blockedHits,
                ip,
                cfResponse,
                isSent,
                countryCode,
                usageType,
                isp,
                confidenceScore,
                block_mode,
                rule_id,
                rule_details
            FROM `{$old_blocked_ips_table_esc}`;
        ";

        $insert_blocked_ips = $wpdb->query( $sql_blocked_ips );
        if ( $insert_blocked_ips !== false ) {
            // Drop the old table if successful
            $wpdb->query( "DROP TABLE IF EXISTS `{$old_blocked_ips_table_esc}`" );
        }
    }

    // 3) Migrate data from old wtc_traffic_data -> pssx_traffic_data (WITHOUT the `id` column).
    $old_traffic_data_exists = ( $wpdb->get_var(
        $wpdb->prepare( "SHOW TABLES LIKE %s", $old_traffic_data_table )
    ) === $old_traffic_data_table );
    
    $new_traffic_data_table_esc = esc_sql( $new_traffic_data_table );
    $old_traffic_data_table_esc = esc_sql( $old_traffic_data_table );

    if ( $old_traffic_data_exists ) {
        $sql_traffic_data = "
            INSERT INTO `{$new_traffic_data_table_esc}` (
                timestamp,
                request_method,
                request_uri,
                user_agent,
                ip_address,
                is_abusive,
                software,
                operating_system,
                api_response,
                sent_to_cf,
                countryCode,
                usageType,
                isp,
                confidenceScore,
                isWhitelisted,
                ipdata_response,
                ipdata_is_tor,
                ipdata_is_icloud_relay,
                ipdata_is_proxy,
                ipdata_is_datacenter,
                ipdata_is_anonymous,
                ipdata_is_known_attacker,
                ipdata_is_known_abuser,
                ipdata_is_threat,
                ipdata_is_bogon
            )
            SELECT
                timestamp,
                request_method,
                request_uri,
                user_agent,
                ip_address,
                is_abusive,
                software,
                operating_system,
                api_response,
                sent_to_cf,
                countryCode,
                usageType,
                isp,
                confidenceScore,
                isWhitelisted,
                ipdata_response,
                ipdata_is_tor,
                ipdata_is_icloud_relay,
                ipdata_is_proxy,
                ipdata_is_datacenter,
                ipdata_is_anonymous,
                ipdata_is_known_attacker,
                ipdata_is_known_abuser,
                ipdata_is_threat,
                ipdata_is_bogon
            FROM `{$old_traffic_data_table_esc}`;
        ";

        $insert_traffic_data = $wpdb->query( $sql_traffic_data );
        if ( $insert_traffic_data !== false ) {
            $wpdb->query( "DROP TABLE IF EXISTS `{$old_traffic_data_table_esc}`" );
        }
    }

    // 4) Migrate wtc_rules -> pssx_rules (WITHOUT the `id` column)
    //    if you also want fresh IDs for rules or if wtc_rules had an auto-increment ID.
    $old_rules_exists = ( $wpdb->get_var(
        $wpdb->prepare( "SHOW TABLES LIKE %s", $old_rules_table )
    ) === $old_rules_table );

    if ( $old_rules_exists ) {
        $sql_rules = "
            INSERT INTO `{$new_rules_table}` (
                criteria,
                action,
                priority
            )
            SELECT
                criteria,
                action,
                priority
            FROM `{$old_rules_table}`;
        ";

        $insert_rules = $wpdb->query( $sql_rules );
        if ( $insert_rules !== false ) {
            $wpdb->query( "DROP TABLE IF EXISTS `{$old_rules_table}`" );
        }
    }
}

/**
 * Check plugin version and run migrations if needed.
 */
function pssx_check_update_and_migrate() {
    $current_version = '1.5.8'; // e.g. your new plugin version
    $stored_version  = get_option( 'pssx_plugin_version', '0.0.0' );

    // If stored version < current_version, let's run the migration once.
    if ( version_compare( $stored_version, $current_version, '<' ) ) {
        // 1. Ensure new tables exist (if your plugin hasn't created them yet).
        pssx_check_custom_tables();

        // 2. Run the migration from old wtc_ tables to new pssx_ tables
        pssx_migrate_old_tables();

        // 3. Update stored version to avoid repeating
        update_option( 'pssx_plugin_version', $current_version );
    }
}
add_action( 'admin_init', 'pssx_check_update_and_migrate' );

