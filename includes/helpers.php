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
        cf_list_item_id text DEFAULT '' NOT NULL,
        cf_item_id varchar(36) DEFAULT NULL,
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

    $cron_interval = get_option( 'pssx_cron_interval', 'hourly' );

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
 * Store admin notices to display on next admin page load.
 *
 * @param string $message Notice content.
 * @param string $type    Notice type (success, error, warning, info).
 */
function pssx_add_admin_notice( $message, $type = 'error' ) {
    $notices   = get_option( 'pssx_admin_notices', array() );
    $notices[] = array(
        'message' => sanitize_text_field( $message ),
        'type'    => sanitize_key( $type ),
    );
    update_option( 'pssx_admin_notices', $notices );
}

/**
 * Log an error and display an admin notice, throttled by transient.
 *
 * @param string $key     Unique key for the notice.
 * @param string $message Message to log and maybe display.
 * @param string $type    Notice type.
 */
function pssx_notify_cf_error( $key, $message, $type = 'error' ) {
    $transient_key = 'pssx_cf_notice_' . md5( $key );
    error_log( $message );
    if ( false === get_transient( $transient_key ) ) {
        pssx_add_admin_notice( $message, $type );
        set_transient( $transient_key, 1, HOUR_IN_SECONDS );
    }
}

/**
 * Output stored admin notices and clear them.
 */
function pssx_display_admin_notices() {
    $notices = get_option( 'pssx_admin_notices', array() );
    if ( empty( $notices ) ) {
        return;
    }
    foreach ( $notices as $notice ) {
        printf(
            '<div class="notice notice-%1$s"><p>%2$s</p></div>',
            esc_attr( $notice['type'] ),
            esc_html( $notice['message'] )
        );
    }
    delete_option( 'pssx_admin_notices' );
}
add_action( 'admin_notices', 'pssx_display_admin_notices' );

/**
 * Create Cloudflare WAF list and rule if they don't exist.
 */
function pssx_create_cf_waf_rule() {
    $pssx_waf_rule_name = get_option( 'pssx_waf_rule_name', '' );
    $existing_list_id   = get_option( 'pssx_cf_list_id', '' );
    $existing_rule_id   = get_option( 'pssx_cf_rule_id', '' );

    $cf_email      = get_option( 'pssx_cloudflare_email', '' );
    $cf_key        = get_option( 'pssx_cloudflare_key', '' );
    $cf_zone_id    = get_option( 'pssx_cloudflare_zone_id', '' );
    $cf_account_id = get_option( 'pssx_cloudflare_account_id', '' );
    $block_mode    = sanitize_text_field( get_option( 'pssx_block_mode', 'block' ) );

    if ( empty( $cf_email ) || empty( $cf_key ) || empty( $cf_zone_id ) || empty( $cf_account_id ) || empty( $pssx_waf_rule_name ) ) {
        pssx_notify_cf_error( 'cf_credentials', __( 'Cloudflare credentials or WAF rule name are missing.', 'proactive-security-suite' ) );
        return;
    }

    $list_name = sanitize_key( $pssx_waf_rule_name );

    $headers = array(
        'X-Auth-Email' => $cf_email,
        'X-Auth-Key'   => $cf_key,
        'Content-Type' => 'application/json',
    );

    if ( empty( $existing_list_id ) ) {
        $list_body = array(
            'name'        => $list_name,
            'kind'        => 'ip',
            'description' => 'Created by Proactive Security Suite',
        );
        $response = wp_remote_post(
            'https://api.cloudflare.com/client/v4/accounts/' . urlencode( $cf_account_id ) . '/rules/lists',
            array(
                'headers' => $headers,
                'body'    => wp_json_encode( $list_body ),
                'timeout' => 30,
            )
        );
        if ( is_wp_error( $response ) ) {
            pssx_notify_cf_error(
                'cf_list_create',
                sprintf( __( 'Failed to create Cloudflare list: %s', 'proactive-security-suite' ), $response->get_error_message() )
            );
        } else {
            $code = wp_remote_retrieve_response_code( $response );
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( 200 === (int) $code && isset( $body['success'] ) && true === $body['success'] && isset( $body['result']['id'] ) ) {
                $existing_list_id = sanitize_text_field( $body['result']['id'] );
                update_option( 'pssx_cf_list_id', $existing_list_id );
            } else {
                $error_detail = isset( $body['errors'][0]['message'] ) ? $body['errors'][0]['message'] : 'HTTP ' . $code;
                pssx_notify_cf_error(
                    'cf_list_create',
                    sprintf( __( 'Failed to create Cloudflare list: %s', 'proactive-security-suite' ), $error_detail )
                );
            }
        }
    }

    if ( ! empty( $existing_list_id ) && empty( $existing_rule_id ) ) {
        $filter_body = array(
            array(
                'expression'  => sprintf( '(ip.src in $%s)', $list_name ),
                'paused'      => false,
                'description' => 'PSSX WAF filter',
            ),
        );
        $filter_response = wp_remote_post(
            'https://api.cloudflare.com/client/v4/zones/' . urlencode( $cf_zone_id ) . '/filters',
            array(
                'headers' => $headers,
                'body'    => wp_json_encode( $filter_body ),
                'timeout' => 30,
            )
        );
        if ( is_wp_error( $filter_response ) ) {
            pssx_notify_cf_error(
                'cf_filter_create',
                sprintf( __( 'Failed to create Cloudflare filter: %s', 'proactive-security-suite' ), $filter_response->get_error_message() )
            );
        } else {
            $filter_code      = wp_remote_retrieve_response_code( $filter_response );
            $filter_body_resp = json_decode( wp_remote_retrieve_body( $filter_response ), true );
            if ( 200 === (int) $filter_code && isset( $filter_body_resp['success'] ) && true === $filter_body_resp['success'] && ! empty( $filter_body_resp['result'][0]['id'] ) ) {
                $filter_id  = sanitize_text_field( $filter_body_resp['result'][0]['id'] );
                $block_mode = ! empty( $block_mode ) ? $block_mode : 'block';
                $rule_body  = array(
                    array(
                        'filter'      => array( 'id' => $filter_id ),
                        'action'      => $block_mode,
                        'description' => $pssx_waf_rule_name,
                    ),
                );
                $rule_response = wp_remote_post(
                    'https://api.cloudflare.com/client/v4/zones/' . urlencode( $cf_zone_id ) . '/firewall/rules',
                    array(
                        'headers' => $headers,
                        'body'    => wp_json_encode( $rule_body ),
                        'timeout' => 30,
                    )
                );
                if ( is_wp_error( $rule_response ) ) {
                    pssx_notify_cf_error(
                        'cf_rule_create',
                        sprintf( __( 'Failed to create Cloudflare WAF rule: %s', 'proactive-security-suite' ), $rule_response->get_error_message() )
                    );
                } else {
                    $rule_code      = wp_remote_retrieve_response_code( $rule_response );
                    $rule_body_resp = json_decode( wp_remote_retrieve_body( $rule_response ), true );
                    if ( 200 === (int) $rule_code && isset( $rule_body_resp['success'] ) && true === $rule_body_resp['success'] && ! empty( $rule_body_resp['result'][0]['id'] ) ) {
                        update_option( 'pssx_cf_rule_id', sanitize_text_field( $rule_body_resp['result'][0]['id'] ) );
                    } else {
                        $rule_detail = isset( $rule_body_resp['errors'][0]['message'] ) ? $rule_body_resp['errors'][0]['message'] : 'HTTP ' . $rule_code;
                        pssx_notify_cf_error(
                            'cf_rule_create',
                            sprintf( __( 'Failed to create Cloudflare WAF rule: %s', 'proactive-security-suite' ), $rule_detail )
                        );
                    }
                }
            } else {
                $filter_detail = isset( $filter_body_resp['errors'][0]['message'] ) ? $filter_body_resp['errors'][0]['message'] : 'HTTP ' . $filter_code;
                pssx_notify_cf_error(
                    'cf_filter_create',
                    sprintf( __( 'Failed to create Cloudflare filter: %s', 'proactive-security-suite' ), $filter_detail )
                );
            }
        }
    }
}

/**
 * Verify Cloudflare WAF rule and list existence, recreating if missing.
 */
function pssx_verify_cf_waf_rule() {
    if ( 'yes' !== get_option( 'pssx_use_waf_rule', 'no' ) ) {
        return;
    }

    $cf_email      = get_option( 'pssx_cloudflare_email', '' );
    $cf_key        = get_option( 'pssx_cloudflare_key', '' );
    $cf_zone_id    = get_option( 'pssx_cloudflare_zone_id', '' );
    $cf_account_id = get_option( 'pssx_cloudflare_account_id', '' );
    $rule_id       = get_option( 'pssx_cf_rule_id', '' );
    $list_id       = get_option( 'pssx_cf_list_id', '' );

    if ( empty( $cf_email ) || empty( $cf_key ) || empty( $cf_zone_id ) || empty( $cf_account_id ) ) {
        return;
    }

    $headers = array(
        'X-Auth-Email' => $cf_email,
        'X-Auth-Key'   => $cf_key,
        'Content-Type' => 'application/json',
    );

    $needs_create = false;

    if ( empty( $rule_id ) || empty( $list_id ) ) {
        $needs_create = true;
    } else {
        $rule_response = wp_remote_get(
            'https://api.cloudflare.com/client/v4/zones/' . urlencode( $cf_zone_id ) . '/firewall/rules/' . urlencode( $rule_id ),
            array(
                'headers' => $headers,
                'timeout' => 30,
            )
        );
        if ( is_wp_error( $rule_response ) ) {
            pssx_notify_cf_error(
                'cf_rule_verify',
                sprintf( __( 'Failed to verify Cloudflare WAF rule: %s', 'proactive-security-suite' ), $rule_response->get_error_message() )
            );
            $needs_create = true;
        } else {
            $rule_code = wp_remote_retrieve_response_code( $rule_response );
            $rule_body = json_decode( wp_remote_retrieve_body( $rule_response ), true );
            if ( 200 !== (int) $rule_code || empty( $rule_body['success'] ) || empty( $rule_body['result']['id'] ) ) {
                $rule_detail = isset( $rule_body['errors'][0]['message'] ) ? $rule_body['errors'][0]['message'] : 'HTTP ' . $rule_code;
                pssx_notify_cf_error(
                    'cf_rule_verify',
                    sprintf( __( 'Cloudflare WAF rule verification failed: %s', 'proactive-security-suite' ), $rule_detail )
                );
                $needs_create = true;
            }
        }

        if ( ! $needs_create ) {
            $list_response = wp_remote_get(
                'https://api.cloudflare.com/client/v4/accounts/' . urlencode( $cf_account_id ) . '/rules/lists/' . urlencode( $list_id ),
                array(
                    'headers' => $headers,
                    'timeout' => 30,
                )
            );
            if ( is_wp_error( $list_response ) ) {
                pssx_notify_cf_error(
                    'cf_list_verify',
                    sprintf( __( 'Failed to verify Cloudflare list: %s', 'proactive-security-suite' ), $list_response->get_error_message() )
                );
                $needs_create = true;
            } else {
                $list_code = wp_remote_retrieve_response_code( $list_response );
                $list_body = json_decode( wp_remote_retrieve_body( $list_response ), true );
                if ( 200 !== (int) $list_code || empty( $list_body['success'] ) || empty( $list_body['result']['id'] ) ) {
                    $list_detail = isset( $list_body['errors'][0]['message'] ) ? $list_body['errors'][0]['message'] : 'HTTP ' . $list_code;
                    pssx_notify_cf_error(
                        'cf_list_verify',
                        sprintf( __( 'Cloudflare list verification failed: %s', 'proactive-security-suite' ), $list_detail )
                    );
                    $needs_create = true;
                }
            }
        }
    }

    if ( $needs_create ) {
        pssx_create_cf_waf_rule();
    }
}

