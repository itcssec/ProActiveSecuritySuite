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
function wtc_delete_ips_cloudflare() {
    // Verify the nonce before processing the request.
    check_ajax_referer( 'wtc_ips_tab_action', 'wtc_ips_tab_nonce' );

    if ( ! current_user_can( 'manage_options' ) || empty( $_POST['ips'] ) ) {
        wp_send_json_error( array(
            'type'    => 'error',
            'message' => 'Invalid request data.',
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

    $ips_to_delete = $_POST['ips'];
    $deleted_ips   = array();

    foreach ( $ips_to_delete as $ip ) {
        // Construct the API URL to fetch the access rule for the IP.
        $api_url = "https://api.cloudflare.com/client/v4/zones/{$cf_zone_id}/firewall/access_rules/rules?configuration.value={$ip}";
        $headers = array(
            "Content-Type: application/json",
            "X-Auth-Email: {$cf_email}",
            "X-Auth-Key: {$cf_api_key}",
        );

        // Get all IP access rules from Cloudflare.
        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_HTTPHEADER     => $headers,
        ) );
        $response = curl_exec( $curl );
        $err      = curl_error( $curl );
        curl_close( $curl );

        if ( $err ) {
            $error_message = "Failed to fetch IP access rules from Cloudflare for IP: {$ip} - Error: {$err}";
            error_log( $error_message );
            continue;
        }

        $data = json_decode( $response, true );

        // Log the data received from Cloudflare.
        error_log( "Data received from Cloudflare for IP: {$ip}" );
        error_log( print_r( $data, true ) );

        if ( empty( $data['result'] ) ) {
            $error_message = "No matching IP access rule found in Cloudflare for IP: {$ip}";
            error_log( $error_message );
            continue;
        }

        $matchedRuleId   = $data['result'][0]['id'];
        $matchedRuleType = $data['result'][0]['scope']['type'];
        //error_log( "Matched Rule ID: " . $matchedRuleId );
        //error_log( "Matched Rule Type: " . $matchedRuleType );

        // Delete the matched IP rule from Cloudflare.
        if ( $matchedRuleType == 'zone' ) {
            $delete_url = "https://api.cloudflare.com/client/v4/zones/{$cf_zone_id}/firewall/access_rules/rules/{$matchedRuleId}";
        } else {
            $delete_url = "https://api.cloudflare.com/client/v4/accounts/{$cf_account_id}/firewall/access_rules/rules/{$matchedRuleId}";
        }

        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => $delete_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => "DELETE",
            CURLOPT_HTTPHEADER     => $headers,
        ) );
        $delete_response = curl_exec( $curl );
        $delete_err      = curl_error( $curl );
        curl_close( $curl );

        if ( $delete_err ) {
            $error_message = "Failed to delete IP access rule for IP: {$ip} from Cloudflare - Error: {$delete_err}";
            error_log( $error_message );
            continue;
        }

        $delete_data = json_decode( $delete_response, true );
        //error_log( "Data received from Delete Cloudflare for IP: {$ip}" );
        //error_log( print_r( $delete_data, true ) );

        if ( ! empty( $delete_data['messages'] ) ) {
            foreach ( $delete_data['messages'] as $message ) {
                error_log( "Message: " . $message );
            }
        }

        if ( ! empty( $delete_data['success'] ) && $delete_data['success'] === true ) {
            $deleted_ips[] = $ip;
            global $wpdb;
            $table_name = $wpdb->prefix . 'wtc_blocked_ips';
            // Delete the IP from the custom table.
            $wpdb->delete( $table_name, array( 'ip' => $ip ), array( '%s' ) );
        } else {
            $error_message = "Failed to delete IP access rule for IP: {$ip} from Cloudflare. Response: " . print_r( $delete_data, true );
            error_log( $error_message );
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
