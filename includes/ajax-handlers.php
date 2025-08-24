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
        pssx_add_admin_notice( __( 'Access is not allowed.', 'proactive-security-suite' ) );
        wp_send_json_error( array(
            'type'    => 'error',
            'message' => 'Access is not allowed.',
        ) );
        wp_die();
    }

    // Unslash + sanitize raw IP array.
    $ips_raw = isset( $_POST['ips'] )
        ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['ips'] ) )
        : array();

    // Validate IP addresses.
    $ips_to_delete = array();
    foreach ( $ips_raw as $ip_raw ) {
        if ( filter_var( $ip_raw, FILTER_VALIDATE_IP ) ) {
            $ips_to_delete[] = $ip_raw;
        }
    }

    if ( empty( $ips_to_delete ) ) {
        pssx_add_admin_notice( __( 'No valid IPs provided.', 'proactive-security-suite' ) );
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
    $cf_list_id    = get_option( 'pssx_cf_list_id' );

    if ( ! $cf_api_key || ! $cf_email ) {
        pssx_add_admin_notice( __( 'Cloudflare credentials are not set.', 'proactive-security-suite' ) );
        wp_send_json_error(
            array(
                'type'    => 'error',
                'message' => 'Cloudflare credentials are not set.',
            )
        );
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'pssx_blocked_ips';

    $headers = array(
        'Content-Type' => 'application/json',
        'X-Auth-Email' => $cf_email,
        'X-Auth-Key'   => $cf_api_key,
    );

    $deleted_ips = array();

    foreach ( $ips_to_delete as $ip ) {
        // Check if this IP has a Cloudflare list item ID.
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT cf_item_id FROM `{$table_name}` WHERE ip = %s", $ip ) );

        if ( $row && ! empty( $row->cf_item_id ) ) {
            if ( ! $cf_account_id || ! $cf_list_id ) {
                pssx_add_admin_notice( __( 'Cloudflare list credentials are not set.', 'proactive-security-suite' ) );
                wp_send_json_error(
                    array(
                        'type'    => 'error',
                        'message' => 'Cloudflare list credentials are not set.',
                    )
                );
                wp_die();
            }

            $delete_url  = 'https://api.cloudflare.com/client/v4/accounts/' . urlencode( $cf_account_id ) . '/rules/lists/' . urlencode( $cf_list_id ) . '/items';
            $delete_args = array(
                'headers' => $headers,
                'method'  => 'DELETE',
                'body'    => wp_json_encode(
                    array(
                        'items' => array(
                            array( 'id' => sanitize_text_field( $row->cf_item_id ) ),
                        ),
                    )
                ),
                'timeout' => 30,
            );
            $delete_response = wp_remote_request( $delete_url, $delete_args );
            if ( is_wp_error( $delete_response ) ) {
                pssx_add_admin_notice( $delete_response->get_error_message() );
                wp_send_json_error(
                    array(
                        'type'    => 'error',
                        'message' => $delete_response->get_error_message(),
                    )
                );
                wp_die();
            }

            $delete_data = json_decode( wp_remote_retrieve_body( $delete_response ), true );
            if ( empty( $delete_data['success'] ) ) {
                $error_message = isset( $delete_data['errors'][0]['message'] ) ? sanitize_text_field( $delete_data['errors'][0]['message'] ) : 'Unknown error.';
                pssx_add_admin_notice( $error_message );
                wp_send_json_error(
                    array(
                        'type'    => 'error',
                        'message' => $error_message,
                    )
                );
                wp_die();
            }

            $wpdb->delete( $table_name, array( 'ip' => $ip ), array( '%s' ) );
            $deleted_ips[] = array(
                'ip'     => $ip,
                'method' => 'list',
            );
        } else {
            if ( ! $cf_zone_id ) {
                pssx_add_admin_notice( __( 'Cloudflare zone ID is not set.', 'proactive-security-suite' ) );
                wp_send_json_error(
                    array(
                        'type'    => 'error',
                        'message' => 'Cloudflare zone ID is not set.',
                    )
                );
                wp_die();
            }

            $api_url = 'https://api.cloudflare.com/client/v4/zones/' . urlencode( $cf_zone_id ) . '/firewall/access_rules/rules?configuration.value=' . urlencode( $ip );
            $args    = array(
                'headers' => $headers,
                'method'  => 'GET',
                'timeout' => 30,
            );
            $response = wp_remote_get( $api_url, $args );
            if ( is_wp_error( $response ) ) {
                $msg = sprintf( __( 'Failed to retrieve access rule for IP %1$s: %2$s', 'proactive-security-suite' ), $ip, $response->get_error_message() );
                pssx_add_admin_notice( $msg );
                wp_send_json_error(
                    array(
                        'type'    => 'error',
                        'message' => $msg,
                    )
                );
                wp_die();
            }

            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( empty( $data['result'] ) ) {
                $msg = sprintf( __( 'No access rule found for IP %s.', 'proactive-security-suite' ), $ip );
                pssx_add_admin_notice( $msg );
                wp_send_json_error(
                    array(
                        'type'    => 'error',
                        'message' => $msg,
                    )
                );
                wp_die();
            }

            $matchedRuleId   = $data['result'][0]['id'];
            $matchedRuleType = $data['result'][0]['scope']['type'];

            if ( 'zone' === $matchedRuleType ) {
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
                $msg = sprintf( __( 'Failed to delete access rule for IP %1$s: %2$s', 'proactive-security-suite' ), $ip, $delete_response->get_error_message() );
                pssx_add_admin_notice( $msg );
                wp_send_json_error(
                    array(
                        'type'    => 'error',
                        'message' => $msg,
                    )
                );
                wp_die();
            }

            $delete_data = json_decode( wp_remote_retrieve_body( $delete_response ), true );
            if ( empty( $delete_data['success'] ) ) {
                $error_message = isset( $delete_data['errors'][0]['message'] ) ? sanitize_text_field( $delete_data['errors'][0]['message'] ) : 'Unknown error.';
                $msg           = sprintf( __( 'Cloudflare API error deleting IP %1$s: %2$s', 'proactive-security-suite' ), $ip, $error_message );
                pssx_add_admin_notice( $msg );
                wp_send_json_error(
                    array(
                        'type'    => 'error',
                        'message' => $msg,
                    )
                );
                wp_die();
            }

            $wpdb->delete( $table_name, array( 'ip' => $ip ), array( '%s' ) );
            $deleted_ips[] = array(
                'ip'     => $ip,
                'method' => 'access_rule',
            );
        }
    }

    if ( ! empty( $deleted_ips ) ) {
        $messages = array();
        foreach ( $deleted_ips as $info ) {
            $messages[] = $info['ip'] . ' (' . $info['method'] . ')';
        }
        wp_send_json_success(
            array(
                'type'        => 'success',
                'message'     => 'IPs deleted successfully from Cloudflare: ' . implode( ', ', $messages ),
                'deleted_ips' => $deleted_ips,
            )
        );
    } else {
        $msg = __( 'No valid IPs were deleted from Cloudflare.', 'proactive-security-suite' );
        pssx_add_admin_notice( $msg );
        wp_send_json_error(
            array(
                'type'    => 'error',
                'message' => $msg,
            )
        );
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

/**
 * AJAX handler to add an IP address to Cloudflare and store it locally.
 */
function pssx_add_ip_cloudflare() {
    check_ajax_referer( 'pssx_ips_tab_action', 'pssx_ips_tab_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        pssx_add_admin_notice( __( 'Access is not allowed.', 'proactive-security-suite' ) );
        wp_send_json_error( array( 'message' => 'Access is not allowed.' ) );
        wp_die();
    }

    $ip      = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';
    $comment = isset( $_POST['comment'] ) ? sanitize_text_field( wp_unslash( $_POST['comment'] ) ) : '';

    if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
        pssx_add_admin_notice( __( 'Invalid IP address.', 'proactive-security-suite' ) );
        wp_send_json_error( array( 'message' => 'Invalid IP address.' ) );
        wp_die();
    }

    $use_waf_rule  = get_option( 'pssx_use_waf_rule', 'no' );
    $block_scope   = get_option( 'pssx_block_scope', 'domain' );
    $block_mode    = get_option( 'pssx_block_mode', 'block' );
    $cf_account_id = get_option( 'pssx_cloudflare_account_id' );
    $cf_list_id    = get_option( 'pssx_cf_list_id' );
    $cf_zone_id    = get_option( 'pssx_cloudflare_zone_id' );
    $cf_api_key    = get_option( 'pssx_cloudflare_key' );
    $cf_email      = get_option( 'pssx_cloudflare_email' );

    if ( ! $cf_api_key || ! $cf_email ) {
        pssx_add_admin_notice( __( 'Cloudflare credentials are not set.', 'proactive-security-suite' ) );
        wp_send_json_error( array( 'message' => 'Cloudflare credentials are not set.' ) );
        wp_die();
    }

    $headers = array(
        'X-Auth-Email' => $cf_email,
        'X-Auth-Key'   => $cf_api_key,
        'Content-Type' => 'application/json',
    );

    if ( 'yes' === $use_waf_rule ) {
        if ( ! $cf_account_id || ! $cf_list_id ) {
            pssx_add_admin_notice( __( 'Cloudflare list credentials are not set.', 'proactive-security-suite' ) );
            wp_send_json_error( array( 'message' => 'Cloudflare list credentials are not set.' ) );
            wp_die();
        }

        $api_url = 'https://api.cloudflare.com/client/v4/accounts/' . urlencode( $cf_account_id ) . '/rules/lists/' . urlencode( $cf_list_id ) . '/items';
        $args    = array(
            'headers' => $headers,
            'body'    => wp_json_encode(
                array(
                    array(
                        'ip'      => $ip,
                        'comment' => $comment,
                    ),
                )
            ),
            'timeout' => 30,
        );
        $response = wp_remote_post( $api_url, $args );
        if ( is_wp_error( $response ) ) {
            pssx_add_admin_notice( $response->get_error_message() );
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
            wp_die();
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['success'] ) ) {
            $error_message = isset( $body['errors'][0]['message'] ) ? sanitize_text_field( $body['errors'][0]['message'] ) : 'Unknown error.';
            pssx_add_admin_notice( $error_message );
            wp_send_json_error( array( 'message' => $error_message ) );
            wp_die();
        }
        $item_id         = isset( $body['result'][0]['id'] ) ? sanitize_text_field( $body['result'][0]['id'] ) : '';
        $cf_list_item_id = wp_json_encode( array( $item_id ) );
    } else {
        if ( 'domain' === $block_scope ) {
            $api_url = 'https://api.cloudflare.com/client/v4/zones/' . urlencode( $cf_zone_id ) . '/firewall/access_rules/rules';
        } else {
            $api_url = 'https://api.cloudflare.com/client/v4/accounts/' . urlencode( $cf_account_id ) . '/firewall/access_rules/rules';
        }
        $args     = array(
            'headers' => $headers,
            'body'    => wp_json_encode(
                array(
                    'mode'          => $block_mode,
                    'configuration' => array(
                        'target' => 'ip',
                        'value'  => $ip,
                    ),
                    'notes'         => $comment,
                )
            ),
            'timeout' => 30,
        );
        $response = wp_remote_post( $api_url, $args );
        if ( is_wp_error( $response ) ) {
            pssx_add_admin_notice( $response->get_error_message() );
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
            wp_die();
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['errors'] ) ) {
            $error_message = isset( $body['errors'][0]['message'] ) ? sanitize_text_field( $body['errors'][0]['message'] ) : 'Unknown error.';
            pssx_add_admin_notice( $error_message );
            wp_send_json_error( array( 'message' => $error_message ) );
            wp_die();
        }
        $item_id         = isset( $body['result']['id'] ) ? sanitize_text_field( $body['result']['id'] ) : '';
        $cf_list_item_id = '';
    }

    global $wpdb;
    $table_name   = $wpdb->prefix . 'pssx_blocked_ips';
    $blocked_time = current_time( 'mysql' );

    $wpdb->insert(
        $table_name,
        array(
            'blockedTime'     => $blocked_time,
            'blockedHits'     => 0,
            'ip'              => $ip,
            'cfResponse'      => wp_remote_retrieve_body( $response ),
            'cf_list_item_id' => $cf_list_item_id,
            'cf_item_id'      => $item_id,
            'isSent'          => 1,
            'countryCode'     => '',
            'usageType'       => '',
            'isp'             => '',
            'confidenceScore' => '',
            'block_mode'      => $block_mode,
        ),
        array( '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
    );

    wp_send_json_success( array( 'message' => 'IP added successfully.' ) );
    wp_die();
}
add_action( 'wp_ajax_pssx_add_ip_cloudflare', 'pssx_add_ip_cloudflare' );

/**
 * AJAX handler to sync IPs from Cloudflare list with local table.
 */
function pssx_sync_ips_cloudflare() {
    check_ajax_referer( 'pssx_ips_tab_action', 'pssx_ips_tab_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        pssx_add_admin_notice( __( 'Access is not allowed.', 'proactive-security-suite' ) );
        wp_send_json_error( array( 'message' => 'Access is not allowed.' ) );
        wp_die();
    }

    $use_waf_rule  = get_option( 'pssx_use_waf_rule', 'no' );
    if ( 'yes' !== $use_waf_rule ) {
        pssx_add_admin_notice( __( 'Cloudflare list option is disabled.', 'proactive-security-suite' ) );
        wp_send_json_error( array( 'message' => 'Cloudflare list option is disabled.' ) );
        wp_die();
    }

    $cf_account_id = get_option( 'pssx_cloudflare_account_id' );
    $cf_list_id    = get_option( 'pssx_cf_list_id' );
    $cf_api_key    = get_option( 'pssx_cloudflare_key' );
    $cf_email      = get_option( 'pssx_cloudflare_email' );

    if ( ! $cf_account_id || ! $cf_list_id || ! $cf_api_key || ! $cf_email ) {
        pssx_add_admin_notice( __( 'Cloudflare list credentials are not set.', 'proactive-security-suite' ) );
        wp_send_json_error( array( 'message' => 'Cloudflare list credentials are not set.' ) );
        wp_die();
    }

    $headers  = array(
        'X-Auth-Email' => $cf_email,
        'X-Auth-Key'   => $cf_api_key,
        'Content-Type' => 'application/json',
    );
    $api_url  = 'https://api.cloudflare.com/client/v4/accounts/' . urlencode( $cf_account_id ) . '/rules/lists/' . urlencode( $cf_list_id ) . '/items';
    $response = wp_remote_get( $api_url, array( 'headers' => $headers, 'timeout' => 30 ) );
    if ( is_wp_error( $response ) ) {
        pssx_add_admin_notice( $response->get_error_message() );
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        wp_die();
    }
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['success'] ) ) {
        $error_message = isset( $body['errors'][0]['message'] ) ? sanitize_text_field( $body['errors'][0]['message'] ) : 'Unknown error.';
        pssx_add_admin_notice( $error_message );
        wp_send_json_error( array( 'message' => $error_message ) );
        wp_die();
    }

    global $wpdb;
    $table_name   = $wpdb->prefix . 'pssx_blocked_ips';
    $block_mode   = get_option( 'pssx_block_mode', 'block' );
    $cf_ips       = array();
    $added        = 0;
    $updated      = 0;
    $removed      = 0;

    if ( isset( $body['result'] ) && is_array( $body['result'] ) ) {
        foreach ( $body['result'] as $item ) {
            $ip      = isset( $item['ip'] ) ? sanitize_text_field( $item['ip'] ) : '';
            $item_id = isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : '';
            if ( ! $ip ) {
                continue;
            }
            $cf_ips[] = $ip;
            $existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM `$table_name` WHERE ip = %s", $ip ) );
            if ( $existing ) {
                $wpdb->update( $table_name, array( 'cf_item_id' => $item_id, 'isSent' => 1 ), array( 'id' => intval( $existing->id ) ), array( '%s', '%d' ), array( '%d' ) );
                $updated++;
            } else {
                $wpdb->insert( $table_name, array( 'blockedTime' => current_time( 'mysql' ), 'blockedHits' => 0, 'ip' => $ip, 'cfResponse' => '', 'cf_list_item_id' => wp_json_encode( array( $item_id ) ), 'cf_item_id' => $item_id, 'isSent' => 1, 'countryCode' => '', 'usageType' => '', 'isp' => '', 'confidenceScore' => '', 'block_mode' => $block_mode ), array( '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ) );
                $added++;
            }
        }

        $local_rows = $wpdb->get_results( "SELECT id, ip FROM `{$table_name}` WHERE cf_item_id IS NOT NULL" );
        if ( $local_rows ) {
            foreach ( $local_rows as $row ) {
                if ( ! in_array( $row->ip, $cf_ips, true ) ) {
                    $wpdb->delete( $table_name, array( 'id' => intval( $row->id ) ), array( '%d' ) );
                    $removed++;
                }
            }
        }
    }

    wp_send_json_success( array( 'message' => sprintf( 'Sync complete. Added %d, updated %d, removed %d.', $added, $updated, $removed ) ) );
    wp_die();
}
add_action( 'wp_ajax_pssx_sync_ips_cloudflare', 'pssx_sync_ips_cloudflare' );
