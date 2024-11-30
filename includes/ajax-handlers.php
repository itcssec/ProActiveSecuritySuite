<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// AJAX handler for deleting IPs from the database.
function wtc_delete_ips() {
    check_ajax_referer( 'wtc_ips_tab_action', 'wtc_ips_tab_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Access is not allowed.' );
        wp_die();
    }

    $ids = isset( $_POST['ids'] ) ? array_map( 'absint', $_POST['ids'] ) : array();

    if ( empty( $ids ) ) {
        wp_send_json_error( 'No IDs provided.' );
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wtc_blocked_ips';
    $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

    $query = "DELETE FROM {$table_name} WHERE id IN ($placeholders)";
    $wpdb->query( $wpdb->prepare( $query, $ids ) );

    wp_send_json_success( 'Selected records deleted successfully from the database.' );
    wp_die();
}
add_action( 'wp_ajax_wtc_delete_ips', 'wtc_delete_ips' );

// AJAX handler for deleting IPs from Cloudflare.
// AJAX handler for deleting IPs from Cloudflare.
function wtc_delete_ips_cloudflare() {
    // Verify the nonce before processing the request.
    check_ajax_referer( 'wtc_ips_tab_action', 'wtc_ips_tab_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array(
            'type'    => 'error',
            'message' => 'Access is not allowed.',
        ) );
        wp_die();
    }

    // Sanitize and validate the IPs from $_POST['ips']
    $ips_raw = isset( $_POST['ips'] ) ? wp_unslash( $_POST['ips'] ) : array();

    if ( ! is_array( $ips_raw ) ) {
        wp_send_json_error( array(
            'type'    => 'error',
            'message' => 'Invalid IP data.',
        ) );
        wp_die();
    }

    $ips_to_delete = array();

    foreach ( $ips_raw as $ip ) {
        $ip = trim( $ip );
        if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            $ips_to_delete[] = $ip;
        }
    }

    if ( empty( $ips_to_delete ) ) {
        wp_send_json_error( array(
            'type'    => 'error',
            'message' => 'No valid IPs provided.',
        ) );
        wp_die();
    }

    $cf_zone_id    = get_option( 'cloudflare_zone_id' );
    $cf_account_id = get_option( 'cloudflare_account_id' );
    $cf_api_key    = get_option( 'cloudflare_key' );
    $cf_email      = get_option( 'cloudflare_email' );

    if ( ! $cf_zone_id || ! $cf_api_key || ! $cf_email ) {
        wp_send_json_error( array(
            'type'    => 'error',
            'message' => 'Cloudflare credentials are not set.',
        ) );
        wp_die();
    }

    $deleted_ips = array();

    foreach ( $ips_to_delete as $ip ) {
        // Construct the API URL to fetch the access rule for the IP.
        $api_url = 'https://api.cloudflare.com/client/v4/zones/' . urlencode( $cf_zone_id ) . '/firewall/access_rules/rules?configuration.value=' . urlencode( $ip );

        $headers = array(
            'Content-Type' => 'application/json',
            'X-Auth-Email' => $cf_email,
            'X-Auth-Key'   => $cf_api_key,
        );

        // Get all IP access rules from Cloudflare.
        $args = array(
            'headers' => $headers,
            'method'  => 'GET',
            'timeout' => 30,
        );

        $response = wp_remote_get( $api_url, $args );

        if ( is_wp_error( $response ) ) {
            // Handle the error appropriately.
            continue;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['result'] ) ) {
            // No matching IP access rule found in Cloudflare.
            continue;
        }

        $matchedRuleId   = $data['result'][0]['id'];
        $matchedRuleType = $data['result'][0]['scope']['type'];

        // Delete the matched IP rule from Cloudflare.
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
            // Handle the error appropriately.
            continue;
        }

        $delete_data = json_decode( wp_remote_retrieve_body( $delete_response ), true );

        if ( ! empty( $delete_data['success'] ) && $delete_data['success'] === true ) {
            $deleted_ips[] = $ip;

            global $wpdb;
            $table_name = $wpdb->prefix . 'wtc_blocked_ips';

            // Delete the IP from the custom table.
            $wpdb->delete( $table_name, array( 'ip' => $ip ), array( '%s' ) );
        } else {
            // Handle the error appropriately.
            continue;
        }
    }

    // Send the response.
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
add_action( 'wp_ajax_wtc_delete_ips_cloudflare', 'wtc_delete_ips_cloudflare' );

// AJAX handler to get all IDs and IPs
function wtc_get_all_ids_ips() {
    check_ajax_referer( 'wtc_ips_tab_action', 'wtc_ips_tab_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Access is not allowed.' );
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wtc_blocked_ips';

    $results = $wpdb->get_results( "SELECT id, ip FROM $table_name" );

    if ( $results ) {
        $ids = array();
        $ips = array();
        foreach ( $results as $row ) {
            $ids[] = $row->id;
            $ips[] = $row->ip;
        }
        wp_send_json_success( array( 'ids' => $ids, 'ips' => $ips ) );
    } else {
        wp_send_json_error( 'Failed to fetch IDs and IPs.' );
    }
}
add_action( 'wp_ajax_wtc_get_all_ids_ips', 'wtc_get_all_ids_ips' );
