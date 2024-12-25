<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fetch blocked IPs from Wordfence and store in custom table.
 */
function pssx_fetch_and_store_blocked_ips() {
    error_log( 'pssx_fetch_and_store_blocked_ips() called at ' . current_time( 'mysql' ) );

    global $wpdb;
    $table_name = $wpdb->prefix . 'pssx_blocked_ips';
    $threshold  = intval( get_option( 'pssx_blocked_hits_threshold', 0 ) ); // FIXED: Updated option name to include prefix

    // Fetch blocked IPs from Wordfence tables
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
            // Convert binary IP to readable format
            $ip_address = inet_ntop( $ip->IP );
            $ip_address = preg_replace( '/^::ffff:/', '', $ip_address );

            // Validate IP address
            if ( filter_var( $ip_address, FILTER_VALIDATE_IP ) === false ) {
                error_log( 'Invalid IP address: ' . $ip_address );
                continue;
            }

            // Check if the IP address already exists in the table.
            $existing_ip = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM `$table_name` WHERE `ip` = %s",
                    $ip_address
                )
            ); // FIXED: Prepared statement with placeholders

            if ( $existing_ip ) {
                continue;
            } else { // FIXED: Retained the else clause
                $timestamp = intval( $ip->blockedTime );
                $timezone  = get_option( 'timezone_string' );
                if ( empty( $timezone ) ) {
                    $timezone = 'UTC';
                }
                $date = new DateTime();
                $date->setTimestamp( $timestamp );
                $date->setTimezone( new DateTimeZone( $timezone ) );
                $blocked_time = $date->format( 'Y-m-d H:i:s' );

                // Retrieve block mode with prefix
                $block_mode = sanitize_text_field( get_option( 'pssx_block_mode', 'block' ) ); // FIXED: Updated option name to include prefix and sanitized

                // Insert the new blocked IP into the custom table
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
                        'block_mode'      => $block_mode,
                    ),
                    array( '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
                );
            }
        }
    }
}

/**
 * Update Cloudflare response in the custom table.
 *
 * @param int    $ip_id       ID of the IP record.
 * @param string $cf_response Cloudflare API response.
 */
function pssx_update_cloudflare_response( $ip_id, $cf_response ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pssx_blocked_ips';

    $wpdb->update(
        $table_name,
        array(
            'cfResponse' => sanitize_text_field( $cf_response ), // FIXED: Sanitized
        ),
        array(
            'id' => intval( $ip_id ), // FIXED: Ensured integer
        ),
        array( '%s' ), // Data format
        array( '%d' )
    );
}

/**
 * Add the blocked IPs to Cloudflare.
 */
function pssx_add_ips_to_cloudflare() {
    error_log( 'pssx_add_ips_to_cloudflare() called at ' . current_time( 'mysql' ) );

    global $wpdb;
    $table_name      = $wpdb->prefix . 'pssx_blocked_ips';
    $email           = sanitize_email( get_option( 'pssx_cloudflare_email' ) ); // FIXED: Updated option name and sanitized
    $key             = sanitize_text_field( get_option( 'pssx_cloudflare_key' ) ); // FIXED: Updated option name and sanitized
    $block_scope     = sanitize_text_field( get_option( 'pssx_block_scope', 'domain' ) ); // FIXED: Updated option name and sanitized
    $block_mode      = sanitize_text_field( get_option( 'pssx_block_mode', 'block' ) ); // FIXED: Updated option name and sanitized
    $zone_id         = sanitize_text_field( get_option( 'pssx_cloudflare_zone_id' ) ); // FIXED: Updated option name and sanitized
    $account_id      = sanitize_text_field( get_option( 'pssx_cloudflare_account_id' ) ); // FIXED: Updated option name and sanitized
    $ips_to_send     = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM `$table_name` WHERE `isSent` = %d",
            0
        )
    ); // FIXED: Prepared statement with placeholders

    $pssx_enable_abuseipdb = sanitize_text_field( get_option( 'pssx_enable_abuseipdb', 'no' ) ); // FIXED: Sanitized
    $abuseipdb_api_key     = sanitize_text_field( get_option( 'pssx_abuseipdb_api_key', '' ) );   // FIXED: Updated option name and sanitized

    $processed_ips_count = 0; // Initialize counter

    if ( $ips_to_send ) {
        foreach ( $ips_to_send as $ip ) {
            $ip_address = sanitize_text_field( $ip->ip ); // FIXED: Sanitized
            $block_mode = sanitize_text_field( $ip->block_mode ); // FIXED: Sanitized

            // Perform AbuseIPDB lookup if enabled
            if ( 'yes' === $pssx_enable_abuseipdb && ! empty( $abuseipdb_api_key ) ) {
                // Perform the lookup
                $request_url = 'https://api.abuseipdb.com/api/v2/check';

                $args = array(
                    'headers' => array(
                        'Key'    => $abuseipdb_api_key,
                        'Accept' => 'application/json',
                    ),
                    'timeout' => 15,
                );

                $query_args = array(
                    'ipAddress'    => $ip_address,
                    'maxAgeInDays' => '90',
                );

                $response = wp_remote_get( add_query_arg( $query_args, $request_url ), $args );

                if ( is_wp_error( $response ) ) {
                    error_log( 'AbuseIPDB lookup failed for IP ' . $ip_address . ': ' . $response->get_error_message() );
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['data'] ) ) {
                        $country_code     = sanitize_text_field( $body['data']['countryCode'] );
                        $usage_type       = sanitize_text_field( $body['data']['usageType'] );
                        $isp              = sanitize_text_field( $body['data']['isp'] );
                        $confidence_score = intval( $body['data']['abuseConfidenceScore'] );

                        // Update the database record
                        $wpdb->update(
                            $table_name,
                            array(
                                'countryCode'     => $country_code,
                                'usageType'       => $usage_type,
                                'isp'             => $isp,
                                'confidenceScore' => $confidence_score,
                            ),
                            array( 'id' => intval( $ip->id ) ),
                            array( '%s', '%s', '%s', '%d' ), // FIXED: Correct format specifiers
                            array( '%d' )
                        );
                    } else {
                        error_log( 'AbuseIPDB lookup failed for IP ' . $ip_address . ': ' . wp_remote_retrieve_body( $response ) );
                    }
                }
            }

            // Send IP to Cloudflare
            if ( 'domain' === $block_scope ) {
                $api_url = "https://api.cloudflare.com/client/v4/zones/" . urlencode( $zone_id ) . "/firewall/access_rules/rules";
            } else {
                $api_url = "https://api.cloudflare.com/client/v4/accounts/" . urlencode( $account_id ) . "/firewall/access_rules/rules";
            }

            $args = array(
                'headers' => array(
                    'X-Auth-Email' => $email,
                    'X-Auth-Key'   => $key,
                    'Content-Type' => 'application/json',
                ),
                'body'    => wp_json_encode( array(
                    'mode'          => $block_mode, // Use the block_mode from the IP record
                    'configuration' => array(
                        'target' => 'ip',
                        'value'  => $ip_address,
                    ),
                    'notes'         => 'Blocked by Proactive Security Suite on ' . current_time( 'mysql' ),
                ) ),
                'timeout' => 30,
            );

            $response = wp_remote_post( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                error_log( 'Failed to create access rule for IP ' . $ip_address . ': ' . $response->get_error_message() );
                continue;
            }

            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( ! empty( $body['errors'] ) ) {
                $error           = $body['errors'][0];
                $responseCode    = sanitize_text_field( $error['code'] );
                $responseMessage = sanitize_text_field( $error['message'] );

                // If we get "duplicate_of_existing," mark it as sent but log the response
                if ( '10009' === $responseCode && 'firewallaccessrules.api.duplicate_of_existing' === $responseMessage ) {
                    pssx_update_cloudflare_response( intval( $ip->id ), wp_remote_retrieve_body( $response ) );
                    $wpdb->update(
                        $table_name,
                        array( 'isSent' => 1 ),
                        array( 'id' => intval( $ip->id ) ),
                        array( '%d' ),
                        array( '%d' )
                    );
                    $processed_ips_count++;
                    continue;
                } else {
                    error_log( 'Failed to create access rule for IP ' . $ip_address . ': ' . print_r( $body, true ) );
                    continue;
                }
            }

            // Successfully created the rule
            pssx_update_cloudflare_response( intval( $ip->id ), wp_remote_retrieve_body( $response ) );

            // Mark IP as sent in the custom table
            $wpdb->update(
                $table_name,
                array( 'isSent' => 1 ),
                array( 'id' => intval( $ip->id ) ),
                array( '%d' ),
                array( '%d' )
            );

            $processed_ips_count++;
        }
    }
    update_option( 'pssx_last_processed_time', current_time( 'mysql' ) );
    update_option( 'pssx_processed_ips_count', $processed_ips_count );
}

/**
 * Hook the functions to the custom cron action.
 */
add_action( 'pssx_check_new_blocked_ips', 'pssx_fetch_and_store_blocked_ips' );
add_action( 'pssx_check_new_blocked_ips', 'pssx_add_ips_to_cloudflare' );

/**
 * Render the Blocked IPs Tab Content.
 */
function pssx_render_ips_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Access is not allowed.' );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'pssx_blocked_ips';

    // Fetch all blocked IPs
    $ips = $wpdb->get_results( "SELECT * FROM `$table_name`" ); // FIXED: Enclosed table name in backticks

    // Get the total number of rows
    $totalRows = count( $ips );

    // Since DataTables scripts are enqueued and initialized globally,
    // we do not need to enqueue scripts or initialize the table again here.

    ?>
    <h2><?php esc_html_e( 'Blocked IPs', 'proactive-security-suite' ); ?></h2>

    <table id="pssx-ips-table" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'ID', 'proactive-security-suite' ); ?></th>
                <th><?php esc_html_e( 'Blocked Time', 'proactive-security-suite' ); ?></th>
                <th><?php esc_html_e( 'IP', 'proactive-security-suite' ); ?></th>
                <th><?php esc_html_e( 'Rule Details', 'proactive-security-suite' ); ?></th>
                <th><?php esc_html_e( 'Country Code', 'proactive-security-suite' ); ?></th>
                <th><?php esc_html_e( 'Usage Type', 'proactive-security-suite' ); ?></th>
                <th><?php esc_html_e( 'ISP', 'proactive-security-suite' ); ?></th>
                <th><?php esc_html_e( 'Confidence Score', 'proactive-security-suite' ); ?></th>
                <th><?php esc_html_e( 'Block Mode', 'proactive-security-suite' ); ?></th>
                <th><?php esc_html_e( 'CF Response', 'proactive-security-suite' ); ?></th>
                <th><?php esc_html_e( 'Is Sent', 'proactive-security-suite' ); ?></th>
                <th class="pssx-checkbox-column">
                    <div class="pssx-checkbox-column-header">
                        <input type="checkbox" id="pssx-select-all">
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $ips ) : ?>
                <?php foreach ( $ips as $ip ) : ?>
                    <tr>
                        <td><?php echo esc_html( $ip->id ); ?></td>
                        <td><?php echo esc_html( $ip->blockedTime ); ?></td>
                        <td><?php echo esc_html( $ip->ip ); ?></td>
                        <td>
                            <?php
                            // Display the rule details if available
                            if ( ! empty( $ip->rule_details ) ) {
                                $rule_details = json_decode( $ip->rule_details, true );
                                if ( is_array( $rule_details ) && isset( $rule_details['criteria'] ) ) {
                                    foreach ( $rule_details['criteria'] as $key => $value ) {
                                        if ( is_array( $value ) ) {
                                            echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) . ': ' . $value['operator'] . ' ' . $value['value'] ) . '<br>';
                                        } else {
                                            echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) . ': ' . $value ) . '<br>';
                                        }
                                    }
                                    if ( isset( $rule_details['action'] ) ) {
                                        echo '<strong>' . esc_html__( 'Action:', 'proactive-security-suite' ) . '</strong> ' . esc_html( ucfirst( str_replace( '_', ' ', $rule_details['action'] ) ) );
                                    }
                                } else {
                                    echo esc_html__( 'N/A', 'proactive-security-suite' );
                                }
                            } else {
                                echo esc_html__( 'N/A', 'proactive-security-suite' );
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html( $ip->countryCode ); ?></td>
                        <td><?php echo esc_html( $ip->usageType ); ?></td>
                        <td><?php echo esc_html( $ip->isp ); ?></td>
                        <td><?php echo esc_html( $ip->confidenceScore ); ?></td>
                        <td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $ip->block_mode ) ) ); ?></td>
                        <td><?php echo esc_html( $ip->cfResponse ); ?></td>
                        <td><?php echo esc_html( $ip->isSent ); ?></td>
                        <td class="pssx-checkbox-column">
                            <input type="checkbox" class="pssx-delete-checkbox" value="<?php echo esc_attr( $ip->id ); ?>" data-ip="<?php echo esc_attr( $ip->ip ); ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="12"><?php esc_html_e( 'No blocked IPs found.', 'proactive-security-suite' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <button id="pssx-delete-selected" class="button button-primary">
        <?php esc_html_e( 'Delete Selected', 'proactive-security-suite' ); ?>
    </button>
    <button id="pssx-delete-selected-cloudflare" class="button button-primary">
        <?php esc_html_e( 'Delete Selected (Cloudflare)', 'proactive-security-suite' ); ?>
    </button>
    <?php
}

