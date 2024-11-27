<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fetch blocked IPs from Wordfence and store in custom table.
function wtc_fetch_and_store_blocked_ips() {
    error_log( 'wtc_fetch_and_store_blocked_ips() called at ' . current_time( 'mysql' ) );

    global $wpdb;
    $table_name = $wpdb->prefix . 'wtc_blocked_ips';
    $threshold  = intval( get_option( 'blocked_hits_threshold', 0 ) );

    $blocked_ips = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT IP, unixday as blockedTime, blockCount as blockedHits
            FROM {$wpdb->prefix}wfblockediplog
            WHERE blockCount >= %d
            UNION
            SELECT IP, blockedTime, blockedHits
            FROM {$wpdb->prefix}wfblocks7
            WHERE blockedHits >= %d",
            $threshold,
            $threshold
        )
    );

    if ( $blocked_ips ) {
        foreach ( $blocked_ips as $ip ) {
            $ip_address = inet_ntop( $ip->IP );
            $ip_address = preg_replace( '/^::ffff:/', '', $ip_address );

            if ( filter_var( $ip_address, FILTER_VALIDATE_IP ) === false ) {
                error_log( 'Invalid IP address: ' . $ip_address );
                continue;
            }

            // Check if the IP address already exists in the table.
            $existing_ip = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE ip = %s", $ip_address ) );

            if ( $existing_ip ) {
                continue;
            } else {
                $timestamp = intval( $ip->blockedTime );
                $timezone  = get_option( 'timezone_string' );
                if ( empty( $timezone ) ) {
                    $timezone = 'UTC';
                }
                $date = new DateTime();
                $date->setTimestamp( $timestamp );
                $date->setTimezone( new DateTimeZone( $timezone ) );
                $blocked_time = $date->format( 'Y-m-d H:i:s' );

                $wpdb->insert(
                    $table_name,
                    array(
                        'blockedTime'     => $blocked_time,
                        'blockedHits'     => intval( $ip->blockedHits ),
                        'ip'              => $ip_address,
                        'cfResponse'      => '',
                        'isSent'          => 0,
                        'countryCode'     => '',
                        'usageType'       => '',
                        'isp'             => '',
                        'confidenceScore' => '',
                    ),
                    array( '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
                );
            }
        }
    }
}


// Add the blocked IPs to Cloudflare.
// Add the blocked IPs to Cloudflare.
function wtc_add_ips_to_cloudflare() {
    error_log( 'wtc_add_ips_to_cloudflare() called at ' . current_time( 'mysql' ) );

    global $wpdb;
    $table_name      = $wpdb->prefix . 'wtc_blocked_ips';
    $email           = get_option( 'cloudflare_email' );
    $key             = get_option( 'cloudflare_key' );
    $block_scope     = get_option( 'block_scope', 'domain' );
    $block_mode      = get_option( 'block_mode', 'block' );
    $zone_id         = get_option( 'cloudflare_zone_id' );
    $account_id      = get_option( 'cloudflare_account_id' );
    $ips_to_send     = $wpdb->get_results( "SELECT * FROM $table_name WHERE isSent = 0" );

    $wtc_enable_abuseipdb = get_option( 'wtc_enable_abuseipdb', 'no' );
    $abuseipdb_api_key = get_option( 'abuseipdb_api_id', '' );

    $processed_ips_count = 0; // Initialize counter

    if ( $ips_to_send ) {
        foreach ( $ips_to_send as $ip ) {
            $ip_address = $ip->ip;

            // Perform AbuseIPDB lookup if enabled
            if ( $wtc_enable_abuseipdb == 'yes' && ! empty( $abuseipdb_api_key ) ) {
                // Perform the lookup
                $request_url = 'https://api.abuseipdb.com/api/v2/check';

                $args = array(
                    'headers' => array(
                        'Key' => $abuseipdb_api_key,
                        'Accept' => 'application/json',
                    ),
                    'timeout' => 15,
                );

                $query_args = array(
                    'ipAddress' => $ip_address,
                    'maxAgeInDays' => '90',
                );

                $response = wp_remote_get( add_query_arg( $query_args, $request_url ), $args );

                if ( is_wp_error( $response ) ) {
                    error_log( 'AbuseIPDB lookup failed for IP ' . $ip_address . ': ' . $response->get_error_message() );
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['data'] ) ) {
                        $country_code = $body['data']['countryCode'];
                        $usage_type = $body['data']['usageType'];
                        $isp = $body['data']['isp'];
                        $confidence_score = $body['data']['abuseConfidenceScore'];

                        // Update the database record
                        $wpdb->update(
                            $table_name,
                            array(
                                'countryCode' => sanitize_text_field( $country_code ),
                                'usageType' => sanitize_text_field( $usage_type ),
                                'isp' => sanitize_text_field( $isp ),
                                'confidenceScore' => sanitize_text_field( $confidence_score ),
                            ),
                            array( 'id' => $ip->id ),
                            array( '%s', '%s', '%s', '%s' ),
                            array( '%d' )
                        );
                    } else {
                        error_log( 'AbuseIPDB lookup failed for IP ' . $ip_address . ': ' . wp_remote_retrieve_body( $response ) );
                    }
                }
            }

            // Existing code to send IP to Cloudflare
            if ( $block_scope == 'domain' ) {
                $api_url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/firewall/access_rules/rules";
            } else {
                $api_url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/firewall/access_rules/rules";
            }

            $args = array(
                'headers' => array(
                    'X-Auth-Email' => $email,
                    'X-Auth-Key'   => $key,
                    'Content-Type' => 'application/json',
                ),
                'body'    => wp_json_encode( array(
                    'mode'          => $block_mode,
                    'configuration' => array(
                        'target' => 'ip',
                        'value'  => $ip_address,
                    ),
                    'notes'         => 'Blocked by Wordfence to Cloudflare plugin'. " " . current_time( 'mysql' ),
                ) ),
                'timeout' => 30,
            );

            $response = wp_remote_post( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                error_log( 'Failed to create access rule: ' . $response->get_error_message() );
                continue;
            }

            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( ! empty( $body['errors'] ) ) {
                $error = $body['errors'][0];
                $responseCode = $error['code'];
                $responseMessage = $error['message'];

                if ( $responseCode == '10009' && $responseMessage == 'firewallaccessrules.api.duplicate_of_existing' ) {
                    wtc_update_cloudflare_response( $ip->id, wp_remote_retrieve_body( $response ) );
                    $wpdb->update(
                        $table_name,
                        array( 'isSent' => 1 ),
                        array( 'id' => $ip->id ),
                        array( '%d' ),
                        array( '%d' )
                    );
                    $processed_ips_count++;
                    continue;
                } else {
                    error_log( 'Failed to create access rule: ' . print_r( $body, true ) );
                    continue;
                }
            }

            wtc_update_cloudflare_response( $ip->id, wp_remote_retrieve_body( $response ) );

            // Mark IP as sent.
            $wpdb->update(
                $table_name,
                array( 'isSent' => 1 ),
                array( 'id' => $ip->id ),
                array( '%d' ),
                array( '%d' )
            );

            $processed_ips_count++;
        }
    }

    // Update the options after processing
    update_option( 'wtc_last_processed_time', current_time( 'mysql' ) );
    update_option( 'wtc_processed_ips_count', $processed_ips_count );

    error_log( 'wtc_add_ips_to_cloudflare() completed. Processed IPs: ' . $processed_ips_count );
}


// Update Cloudflare response in the custom table.
function wtc_update_cloudflare_response( $ip_id, $cf_response ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wtc_blocked_ips';

    $wpdb->update(
        $table_name,
        array(
            'cfResponse' => sanitize_text_field( $cf_response ),
        ),
        array(
            'id' => intval( $ip_id ),
        ),
        array( '%s' ),
        array( '%d' )
    );
}

// Hook the functions to the custom cron action.
add_action( 'wtc_check_new_blocked_ips', 'wtc_fetch_and_store_blocked_ips' );
add_action( 'wtc_check_new_blocked_ips', 'wtc_add_ips_to_cloudflare' );

// Render Blocked IPs Tab Content.
function wtc_render_ips_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Access is not allowed.' );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wtc_blocked_ips';

    $ips = $wpdb->get_results( "SELECT * FROM $table_name" );

    // Get the total number of rows
    $totalRows = count( $ips );

    // Render the table HTML.
    ?>
    <h2><?php esc_html_e( 'Blocked IPs', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></h2>

    <style>
    /* Add custom styles for the checkbox column */
    .wtc-checkbox-column {
        width: 50px;
        text-align: center;
    }
    /* Center the checkbox within the header */
    .wtc-checkbox-column-header {
        display: flex;
        justify-content: center;
        align-items: center;
        width:40px;
    }
    </style>

    <table id="wtc-ips-table" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'ID', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th><?php esc_html_e( 'Blocked Time', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th><?php esc_html_e( 'Blocked Hits', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th><?php esc_html_e( 'IP', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th><?php esc_html_e( 'Country Code', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th><?php esc_html_e( 'Usage Type', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th><?php esc_html_e( 'ISP', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th><?php esc_html_e( 'Confidence Score', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th><?php esc_html_e( 'CF Response', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th><?php esc_html_e( 'Is Sent', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th class="wtc-checkbox-column">
                    <div class="wtc-checkbox-column-header">
                        <input type="checkbox" id="wtc-select-all">
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $ips as $ip ) : ?>
                <tr>
                    <td><?php echo esc_html( $ip->id ); ?></td>
                    <td><?php echo esc_html( $ip->blockedTime ); ?></td>
                    <td><?php echo esc_html( $ip->blockedHits ); ?></td>
                    <td><?php echo esc_html( $ip->ip ); ?></td>
                    <td><?php echo esc_html( $ip->countryCode ); ?></td>
                    <td><?php echo esc_html( $ip->usageType ); ?></td>
                    <td><?php echo esc_html( $ip->isp ); ?></td>
                    <td><?php echo esc_html( $ip->confidenceScore ); ?></td>
                    <td><?php echo esc_html( $ip->cfResponse ); ?></td>
                    <td><?php echo esc_html( $ip->isSent ); ?></td>
                    <td class="wtc-checkbox-column">
                        <input type="checkbox" class="wtc-delete-checkbox" value="<?php echo esc_attr( $ip->id ); ?>" data-ip="<?php echo esc_attr( $ip->ip ); ?>">
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <button id="wtc-delete-selected" class="button button-primary"><?php esc_html_e( 'Delete Selected', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></button>
    <button id="wtc-delete-selected-cloudflare" class="button button-primary"><?php esc_html_e( 'Delete Selected (Cloudflare)', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></button>

    <script type="text/javascript">
        var totalRows = <?php echo $totalRows; ?>;
    </script>
    <?php
}
