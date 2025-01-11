<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// AJAX handler for deleting IPs from the database.
function pssx_delete_ips() {
    check_ajax_referer( 'pssx_ips_tab_action', 'pssx_ips_tab_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Access is not allowed.' );
        wp_die();
    }

    // 1) Retrieve raw POST data
    // 2) Unslash and sanitize each entry
    $ips_raw = isset( $_POST['ids'] )
        ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['ids'] ) )
        : array();

    // Convert to integers
    $ids = array_map( 'absint', $ips_raw );

    // Filter out any zero or negative
    $ids = array_filter( $ids, function( $id ) {
        return $id > 0;
    } );

    if ( empty( $ids ) ) {
        wp_send_json_error( 'No valid IDs provided.' );
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'pssx_blocked_ips';

    // Build a string of '%d' placeholders based on how many IDs we have
    $placeholders_str = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe ($wpdb->prefix).
    // We then prepare the query for the IDs themselves:
    $sql = "DELETE FROM `{$table_name}` WHERE id IN ($placeholders_str)";
    $sql = $wpdb->prepare( $sql, $ids );

    // Execute
    $result = $wpdb->query( $sql );

    if ( false === $result ) {
        wp_send_json_error( 'Database error occurred.' );
    } else {
        wp_send_json_success( 'Selected records deleted successfully from the database.' );
    }

    wp_die();
}
add_action( 'wp_ajax_pssx_delete_ips', 'pssx_delete_ips' );

// AJAX handler for deleting IPs from Cloudflare.
function pssx_delete_ips_cloudflare() {
    check_ajax_referer( 'pssx_ips_tab_action', 'pssx_ips_tab_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array(
            'type'    => 'error',
            'message' => 'Access is not allowed.',
        ) );
        wp_die();
    }

    // Unslash + sanitize raw IP array
    $ips_raw = isset( $_POST['ips'] )
        ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['ips'] ) )
        : array();

    // Validate IP addresses
    $ips_to_delete = array();
    foreach ( $ips_raw as $ip_raw ) {
        if ( filter_var( $ip_raw, FILTER_VALIDATE_IP ) ) {
            $ips_to_delete[] = $ip_raw;
        }
    }

    if ( empty( $ips_to_delete ) ) {
        wp_send_json_error( array(
            'type'    => 'error',
            'message' => 'No valid IPs provided.',
        ) );
        wp_die();
    }

    $cf_zone_id    = get_option( 'pssx_cloudflare_zone_id' );
    $cf_account_id = get_option( 'pssx_cloudflare_account_id' );
    $cf_api_key    = get_option( 'pssx_cloudflare_key' );
    $cf_email      = get_option( 'pssx_cloudflare_email' );

    if ( ! $cf_zone_id || ! $cf_api_key || ! $cf_email ) {
        wp_send_json_error( array(
            'type'    => 'error',
            'message' => 'Cloudflare credentials are not set.',
        ) );
        wp_die();
    }

    $deleted_ips = array();
    foreach ( $ips_to_delete as $ip ) {
        $api_url = 'https://api.cloudflare.com/client/v4/zones/' . urlencode( $cf_zone_id ) . '/firewall/access_rules/rules?configuration.value=' . urlencode( $ip );
        $headers = array(
            'Content-Type' => 'application/json',
            'X-Auth-Email' => $cf_email,
            'X-Auth-Key'   => $cf_api_key,
        );
        $args = array(
            'headers' => $headers,
            'method'  => 'GET',
            'timeout' => 30,
        );
        $response = wp_remote_get( $api_url, $args );
        if ( is_wp_error( $response ) ) {
            continue;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['result'] ) ) {
            continue;
        }
        $matchedRuleId   = $data['result'][0]['id'];
        $matchedRuleType = $data['result'][0]['scope']['type'];

        if ( $matchedRuleType === 'zone' ) {
            $delete_url = 'https://api.cloudflare.com/client/v4/zones/' . urlencode( $cf_zone_id ) . '/firewall/access_rules/rules/' . urlencode( $matchedRuleId );
        } else {
            $delete_url = 'https://api.cloudflare.com/client/v4/accounts/' . urlencode( $cf_account_id ) . '/firewall/access_rules/rules/' . urlencode( $matchedRuleId );
        }

        $delete_args = array(
            'headers' => $headers,
            'method'  => 'DELETE',
            'timeout' => 30,
        );
        $delete_response = wp_remote_request( $delete_url, $delete_args );
        if ( is_wp_error( $delete_response ) ) {
            continue;
        }
        $delete_data = json_decode( wp_remote_retrieve_body( $delete_response ), true );
        if ( ! empty( $delete_data['success'] ) && true === $delete_data['success'] ) {
            $deleted_ips[] = $ip;

            // Also delete from local DB
            global $wpdb;
            $table_name = $wpdb->prefix . 'pssx_blocked_ips';
            $wpdb->delete(
                $table_name,
                array( 'ip' => $ip ),
                array( '%s' )
            );
        }
    }

    if ( ! empty( $deleted_ips ) ) {
        wp_send_json_success( array(
            'type'        => 'success',
            'message'     => 'IPs deleted successfully from Cloudflare.',
            'deleted_ips' => $deleted_ips,
        ) );
    } else {
        wp_send_json_error( array(
            'type'    => 'error',
            'message' => 'No valid IP access rules were deleted from Cloudflare.',
        ) );
    }

    wp_die();
}
add_action( 'wp_ajax_pssx_delete_ips_cloudflare', 'pssx_delete_ips_cloudflare' );

// AJAX handler to get all IDs and IPs
function pssx_get_all_ids_ips() {
    check_ajax_referer( 'pssx_ips_tab_action', 'pssx_ips_tab_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Access is not allowed.' );
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'pssx_blocked_ips';

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is known-safe
    $sql = "SELECT id, ip FROM `{$table_name}`";
    $results = $wpdb->get_results( $sql );

    if ( $results ) {
        $ids = array();
        $ips = array();
        foreach ( $results as $row ) {
            $ids[] = $row->id;
            $ips[] = $row->ip;
        }
        wp_send_json_success( array(
            'ids' => $ids,
            'ips' => $ips,
        ) );
    } else {
        wp_send_json_error( 'Failed to fetch IDs and IPs.' );
    }
}
add_action( 'wp_ajax_pssx_get_all_ids_ips', 'pssx_get_all_ids_ips' );
