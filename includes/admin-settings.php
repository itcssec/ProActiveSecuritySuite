<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include the helpers file to ensure wtc_update_cron_schedule() is available.
require_once WTC_PLUGIN_DIR . 'includes/helpers.php';

// Add settings link to plugin page.
function wtc_add_settings_link( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=wtc-settings' ) ) . '">' . __( 'Settings', 'blocked-ips-for-wordfence-to-cloudflare' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wtc_add_settings_link' );

// Create the admin menu.
function wtc_menu() {
    add_options_page(
        'WTC Settings',
        'WTC Settings',
        'manage_options',
        'wtc-settings',
        'wtc_render_admin_page'
    );
}
add_action( 'admin_menu', 'wtc_menu' );

// Render the admin page.
function wtc_render_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Access is not allowed.' );
    }

    $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'wtc-settings';

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Wordfence to Cloudflare', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></h1>

        <!-- Add Tabs -->
        <h2 class="nav-tab-wrapper">
            <a href="?page=wtc-settings" class="nav-tab <?php echo ( $active_tab === 'wtc-settings' ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></a>
            <a href="?page=wtc-settings&tab=wtc-ips" class="nav-tab <?php echo ( $active_tab === 'wtc-ips' ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Blocked IPs', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></a>
            <?php if ( wor_fs()->is__premium_only() ) : ?>
                <a href="?page=wtc-settings&tab=wtc-traffic" class="nav-tab <?php echo ( $active_tab === 'wtc-traffic' ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Captured Traffic Data', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></a>
                <a href="?page=wtc-settings&tab=wtc-rules" class="nav-tab <?php echo ( $active_tab === 'wtc-rules' ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Rule Builder', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></a>
            <?php else : ?>
                <a href="#" class="nav-tab disabled"><?php esc_html_e( 'Captured Traffic Data', 'blocked-ips-for-wordfence-to-cloudflare' ); ?> <?php esc_html_e( '(Premium)', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></a>
                <a href="#" class="nav-tab disabled"><?php esc_html_e( 'Rule Builder', 'blocked-ips-for-wordfence-to-cloudflare' ); ?> <?php esc_html_e( '(Premium)', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></a>
            <?php endif; ?>
        </h2>

        <!-- Display Tab Content -->
        <?php
        switch ( $active_tab ) {
            case 'wtc-ips':
                wtc_render_ips_tab();
                break;
            case 'wtc-traffic':
                if ( wor_fs()->is__premium_only() ) {
                    wtc_render_traffic_tab();
                } else {
                    echo '<div class="notice notice-warning"><p>' . esc_html__( 'The Captured Traffic Data feature is available in the premium version. Please upgrade to access this feature.', 'blocked-ips-for-wordfence-to-cloudflare' ) . '</p></div>';
                    // Add an upgrade button
                    if ( function_exists( 'wor_fs' ) ) {
                        wor_fs()->get_logger()->warning( 'Attempted access to premium tab: Captured Traffic Data' );
                        wor_fs()->add_upgrade_button();
                    }
                }
                break;
            case 'wtc-rules':
                if ( wor_fs()->is__premium_only() ) {
                    wtc_render_rule_builder_tab();
                } else {
                    echo '<div class="notice notice-warning"><p>' . esc_html__( 'The Rule Builder feature is available in the premium version. Please upgrade to access this feature.', 'blocked-ips-for-wordfence-to-cloudflare' ) . '</p></div>';
                    // Add an upgrade button
                    if ( function_exists( 'wor_fs' ) ) {
                        wor_fs()->get_logger()->warning( 'Attempted access to premium tab: Rule Builder' );
                        wor_fs()->add_upgrade_button();
                    }
                }
                break;
            default:
                wtc_render_settings_tab();
                break;
        }
        ?>
    </div>
    <?php
}

// Render the settings tab.
function wtc_render_settings_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Access is not allowed.' );
    }

    // Handle form submission.
    if ( isset( $_POST['wtc_settings_submit'] ) && check_admin_referer( 'wtc_settings_action', 'wtc_settings_nonce' ) ) {
        // Update options securely.
        $cloudflare_email       = sanitize_email( $_POST['cloudflare_email'] );
        $cloudflare_key_input   = sanitize_text_field( $_POST['cloudflare_key'] );
        $cloudflare_zone_id     = sanitize_text_field( $_POST['cloudflare_zone_id'] );
        $cloudflare_account_id  = sanitize_text_field( $_POST['cloudflare_account_id'] );
        $abuseipdb_api_id_input = sanitize_text_field( $_POST['abuseipdb_api_id'] );
        $blocked_hits_threshold = intval( $_POST['blocked_hits_threshold'] );
        $block_scope            = sanitize_text_field( $_POST['block_scope'] );
        $block_mode             = sanitize_text_field( $_POST['block_mode'] );
        $cron_interval          = sanitize_text_field( $_POST['cron_interval'] );

        update_option( 'cloudflare_email', $cloudflare_email );

        if ( ! empty( $cloudflare_key_input ) && $cloudflare_key_input !== str_repeat( '*', 10 ) ) {
            // Securely store the Cloudflare key.
            update_option( 'cloudflare_key', $cloudflare_key_input );
        }

        update_option( 'cloudflare_zone_id', $cloudflare_zone_id );
        update_option( 'cloudflare_account_id', $cloudflare_account_id );

        if ( ! empty( $abuseipdb_api_id_input ) && $abuseipdb_api_id_input !== str_repeat( '*', 10 ) ) {
            update_option( 'abuseipdb_api_id', $abuseipdb_api_id_input );
        }

        // Enable AbuseIPDB Lookup Option.
        $wtc_enable_abuseipdb = isset( $_POST['wtc_enable_abuseipdb'] ) ? 'yes' : 'no';
        update_option( 'wtc_enable_abuseipdb', $wtc_enable_abuseipdb );

        if ( wor_fs()->is__premium_only() ) {
            $whatismybr_api_id_input = sanitize_text_field( $_POST['whatismybr_api_id'] );
            if ( ! empty( $whatismybr_api_id_input ) && $whatismybr_api_id_input !== str_repeat( '*', 10 ) ) {
                update_option( 'whatismybr_api_id', $whatismybr_api_id_input );
            }

            // Enable Traffic Capture Option.
            $wtc_enable_traffic_capture = isset( $_POST['wtc_enable_traffic_capture'] ) ? 'yes' : 'no';
            update_option( 'wtc_enable_traffic_capture', $wtc_enable_traffic_capture );

            // Enable AbuseIPDB Lookup for Captured Traffic Option.
            $wtc_enable_abuseipdb_lookup_traffic = isset( $_POST['wtc_enable_abuseipdb_lookup_traffic' ] ) ? 'yes' : 'no';
            update_option( 'wtc_enable_abuseipdb_lookup_traffic', $wtc_enable_abuseipdb_lookup_traffic );

            // Save Excluded Roles.
            if ( isset( $_POST['wtc_excluded_roles'] ) && is_array( $_POST['wtc_excluded_roles'] ) ) {
                $wtc_excluded_roles = array_map( 'sanitize_text_field', $_POST['wtc_excluded_roles'] );
                update_option( 'wtc_excluded_roles', $wtc_excluded_roles );
            } else {
                update_option( 'wtc_excluded_roles', array() );
            }
        }

        update_option( 'blocked_hits_threshold', $blocked_hits_threshold );
        update_option( 'block_scope', $block_scope );
        update_option( 'block_mode', $block_mode );
        update_option( 'cron_interval', $cron_interval );

        // Update the cron schedule.
        wtc_update_cron_schedule();

        // Display a success message.
        add_settings_error( 'wtc_settings', 'wtc_settings_updated', __( 'Settings saved.', 'blocked-ips-for-wordfence-to-cloudflare' ), 'updated' );
    }

    // Get options.
    $cloudflare_email       = get_option( 'cloudflare_email', '' );
    $cloudflare_key         = get_option( 'cloudflare_key', '' );
    $cloudflare_zone_id     = get_option( 'cloudflare_zone_id', '' );
    $cloudflare_account_id  = get_option( 'cloudflare_account_id', '' );
    $abuseipdb_api_id       = get_option( 'abuseipdb_api_id', '' );
    $whatismybr_api_id      = get_option( 'whatismybr_api_id', '' );
    $blocked_hits_threshold = get_option( 'blocked_hits_threshold', 0 );
    $block_scope            = get_option( 'block_scope', 'domain' );
    $block_mode             = get_option( 'block_mode', 'block' );
    $cron_interval          = get_option( 'cron_interval', 'hourly' );

    $wtc_last_processed_time = get_option( 'wtc_last_processed_time', '' );
    $wtc_processed_ips_count = get_option( 'wtc_processed_ips_count', 0 );

    // Get the traffic capture setting.
    $wtc_enable_traffic_capture = get_option( 'wtc_enable_traffic_capture', 'yes' );

    // Get the AbuseIPDB enable setting.
    $wtc_enable_abuseipdb = get_option( 'wtc_enable_abuseipdb', 'no' );

    // Get the AbuseIPDB lookup for captured traffic setting.
    $wtc_enable_abuseipdb_lookup_traffic = get_option( 'wtc_enable_abuseipdb_lookup_traffic', 'no' );

    // Get the saved excluded roles.
    $wtc_excluded_roles = get_option( 'wtc_excluded_roles', array() );

    // Retrieve all editable roles.
    $editable_roles = get_editable_roles();

    settings_errors( 'wtc_settings' );

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Settings', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'wtc_settings_action', 'wtc_settings_nonce' ); ?>
            <table class="form-table">
                <!-- Cloudflare Settings -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Cloudflare Email', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                    <td><input type="email" name="cloudflare_email" value="<?php echo esc_attr( $cloudflare_email ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Cloudflare Key', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                    <td><input type="password" name="cloudflare_key" value="<?php echo esc_attr( str_repeat( '*', 10 ) ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Cloudflare Zone ID', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                    <td><input type="text" name="cloudflare_zone_id" value="<?php echo esc_attr( $cloudflare_zone_id ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Cloudflare Account ID', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                    <td><input type="text" name="cloudflare_account_id" value="<?php echo esc_attr( $cloudflare_account_id ); ?>" /></td>
                </tr>

                <!-- AbuseIPDB Settings -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'AbuseIPDB API Key', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                    <td>
                        <?php if ( ! empty( $abuseipdb_api_id ) ) : ?>
                            <input type="password" name="abuseipdb_api_id" value="<?php echo esc_attr( str_repeat( '*', 10 ) ); ?>" />
                        <?php else : ?>
                            <input type="password" name="abuseipdb_api_id" value="" />
                        <?php endif; ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Enable AbuseIPDB Lookup', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                    <td><input type="checkbox" name="wtc_enable_abuseipdb" value="yes" <?php checked( 'yes', $wtc_enable_abuseipdb ); ?> /></td>
                </tr>

                <!-- Premium Features -->
                <?php if ( wor_fs()->is__premium_only() ) : ?>
                    <!-- WhatIsMyBrowser API Key -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'WhatIsMyBrowser API Key', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                        <td>
                            <?php if ( ! empty( $whatismybr_api_id ) ) : ?>
                                <input type="password" name="whatismybr_api_id" value="<?php echo esc_attr( str_repeat( '*', 10 ) ); ?>" />
                            <?php else : ?>
                                <input type="password" name="whatismybr_api_id" value="" />
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Enable Traffic Capture -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Enable Traffic Capture', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                        <td><input type="checkbox" name="wtc_enable_traffic_capture" value="yes" <?php checked( 'yes', $wtc_enable_traffic_capture ); ?> /></td>
                    </tr>

                    <!-- Enable AbuseIPDB Lookup for Captured Traffic -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Enable AbuseIPDB Lookup for Captured Traffic', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                        <td><input type="checkbox" name="wtc_enable_abuseipdb_lookup_traffic" value="yes" <?php checked( 'yes', $wtc_enable_abuseipdb_lookup_traffic ); ?> /></td>
                    </tr>

                    <!-- Exclude Roles from Captured Traffic -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Exclude Roles from Captured Traffic', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                        <td>
                            <?php foreach ( $editable_roles as $role_slug => $role_details ) : ?>
                                <label>
                                    <input type="checkbox" name="wtc_excluded_roles[]" value="<?php echo esc_attr( $role_slug ); ?>" <?php checked( in_array( $role_slug, $wtc_excluded_roles ) ); ?> />
                                    <?php echo esc_html( translate_user_role( $role_details['name'] ) ); ?>
                                </label><br>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Select the user roles to exclude from traffic logging.', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></p>
                        </td>
                    </tr>
                <?php else : ?>
                    <!-- WhatIsMyBrowser API Key (Disabled) -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'WhatIsMyBrowser API Key', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                        <td>
                            <input type="password" disabled value="" />
                            <p class="description"><?php esc_html_e( 'Available in the premium version.', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></p>
                        </td>
                    </tr>
                    <!-- Enable Traffic Capture (Disabled) -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Enable Traffic Capture', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                        <td>
                            <input type="checkbox" disabled />
                            <p class="description"><?php esc_html_e( 'Available in the premium version.', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></p>
                        </td>
                    </tr>
                    <!-- Enable AbuseIPDB Lookup for Captured Traffic (Disabled) -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Enable AbuseIPDB Lookup for Captured Traffic', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                        <td>
                            <input type="checkbox" disabled />
                            <p class="description"><?php esc_html_e( 'Available in the premium version.', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></p>
                        </td>
                    </tr>
                    <!-- Exclude Roles from Captured Traffic (Disabled) -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Exclude Roles from Captured Traffic', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                        <td>
                            <p class="description"><?php esc_html_e( 'Available in the premium version.', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></p>
                        </td>
                    </tr>
                <?php endif; ?>

                <!-- Blocked Hits Threshold -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Blocked Hits Threshold', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                    <td><input type="number" min="0" name="blocked_hits_threshold" value="<?php echo esc_attr( $blocked_hits_threshold ); ?>" /></td>
                </tr>

                <!-- Block Scope -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Block Scope', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                    <td>
                        <select name="block_scope">
                            <option value="domain" <?php selected( 'domain', $block_scope ); ?>><?php esc_html_e( 'Domain Specific', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                            <option value="account" <?php selected( 'account', $block_scope ); ?>><?php esc_html_e( 'Entire Account', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                        </select>
                    </td>
                </tr>

                <!-- Block Mode -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Block Mode', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                    <td>
                        <select name="block_mode">
                            <option value="block" <?php selected( 'block', $block_mode ); ?>><?php esc_html_e( 'Block', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                            <option value="managed_challenge" <?php selected( 'managed_challenge', $block_mode ); ?>><?php esc_html_e( 'Managed Challenge', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                            <!-- Add other modes as needed -->
                        </select>
                    </td>
                </tr>

                <!-- Cron Interval -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Cron Interval', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                    <td>
                        <select name="cron_interval">
                            <option value="none" <?php selected( 'none', $cron_interval ); ?>><?php esc_html_e( 'Not Set', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                            <option value="1min" <?php selected( '1min', $cron_interval ); ?>><?php esc_html_e( 'Every Minute', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                            <option value="5min" <?php selected( '5min', $cron_interval ); ?>><?php esc_html_e( 'Every 5 Minutes', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                            <option value="hourly" <?php selected( 'hourly', $cron_interval ); ?>><?php esc_html_e( '1 hour', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                            <option value="twicedaily" <?php selected( 'twicedaily', $cron_interval ); ?>><?php esc_html_e( '12 hours', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                            <option value="daily" <?php selected( 'daily', $cron_interval ); ?>><?php esc_html_e( '24 hours', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>

            <!-- Cron Status -->
            <h2><?php esc_html_e( 'Cron Status', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Last Cron Run:', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                    <td><?php echo esc_html( $wtc_last_processed_time ); ?></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'IPs Processed:', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                    <td><?php echo esc_html( $wtc_processed_ips_count ); ?></td>
                </tr>
            </table>

            <?php submit_button( __( 'Save Settings', 'blocked-ips-for-wordfence-to-cloudflare' ), 'primary', 'wtc_settings_submit' ); ?>
        </form>

        <!-- Run Process Manually -->
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=wtc_run_process' ) ); ?>">
            <?php wp_nonce_field( 'wtc_run_process_action', 'wtc_run_process_nonce' ); ?>
            <button type="submit" name="wtc_run_process" class="button button-primary"><?php esc_html_e( 'Run Process', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></button>
        </form>

        <!-- Clear Data -->
        <br><br>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'wtc_clear_data_action', 'wtc_clear_data_nonce' ); ?>
            <input type="hidden" name="action" value="wtc_clear_data">
            <button type="submit" name="wtc_clear_data" class="button button-secondary"><?php esc_html_e( 'Clear Data', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></button>
        </form>

        <!-- Delete Captured Traffic (Premium Only) -->
        <?php if ( wor_fs()->is__premium_only() ) : ?>
            <br><br>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'wtc_delete_captured_traffic', 'wtc_delete_nonce' ); ?>
                <input type="hidden" name="action" value="wtc_delete_captured_traffic">
                <button type="submit" name="wtc_delete_captured_traffic" class="button button-secondary"><?php esc_html_e( 'Delete All Captured Traffic', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></button>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

// Handle deletion of captured traffic data (Premium Only).
function wtc_handle_delete_captured_traffic_action() {
    if ( isset( $_POST['action'] ) && $_POST['action'] === 'wtc_delete_captured_traffic' && check_admin_referer( 'wtc_delete_captured_traffic', 'wtc_delete_nonce' ) ) {
        if ( ! wor_fs()->is__premium_only() ) {
            wp_die( 'Access is not allowed.' );
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'wtc_traffic_data';

        $wpdb->query( "TRUNCATE TABLE $table_name" );

        $redirect_url = add_query_arg( 'wtc_message', 'deleted', admin_url( 'options-general.php?page=wtc-settings&tab=wtc-traffic' ) );
        wp_safe_redirect( $redirect_url );
        exit;
    }
}
add_action( 'admin_post_wtc_delete_captured_traffic', 'wtc_handle_delete_captured_traffic_action' );

// Clear data handler.
function wtc_clear_data() {
    if ( isset( $_POST['wtc_clear_data'] ) && check_admin_referer( 'wtc_clear_data_action', 'wtc_clear_data_nonce' ) ) {
        delete_option( 'wtc_last_processed_time' );
        delete_option( 'wtc_processed_ips_count' );
        wp_redirect( admin_url( 'options-general.php?page=wtc-settings' ) );
        exit;
    }
}
add_action( 'admin_post_wtc_clear_data', 'wtc_clear_data' );

// Run process manually handler.
function wtc_run_process_manually() {
    if ( isset( $_POST['wtc_run_process'] ) && check_admin_referer( 'wtc_run_process_action', 'wtc_run_process_nonce' ) ) {
        wtc_fetch_and_store_blocked_ips();
        wtc_add_ips_to_cloudflare();
        wp_redirect( admin_url( 'options-general.php?page=wtc-settings' ) );
        exit;
    }
}
add_action( 'admin_post_wtc_run_process', 'wtc_run_process_manually' );

// Add CSS for disabled tabs and inputs.
function wtc_admin_styles() {
    echo '<style>
    .nav-tab.disabled {
        color: #a7aaad;
        pointer-events: none;
        cursor: default;
    }
    input[disabled], select[disabled], textarea[disabled] {
        background-color: #f1f1f1;
        color: #a7aaad;
    }
    </style>';
}
add_action( 'admin_head', 'wtc_admin_styles' );

// Render the Rule Builder tab.
function wtc_render_rule_builder_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Access is not allowed.' );
    }

    // Handle form submission for adding a new rule.
    if ( isset( $_POST['wtc_add_rule_nonce'] ) && check_admin_referer( 'wtc_add_rule_action', 'wtc_add_rule_nonce' ) ) {
        // Validate and sanitize inputs
        $criteria = array();

        if ( ! empty( $_POST['confidence_score_operator'] ) && isset( $_POST['confidence_score_value'] ) ) {
            $operator = sanitize_text_field( $_POST['confidence_score_operator'] );
            $value = intval( $_POST['confidence_score_value'] );
            $criteria['confidence_score'] = array(
                'operator' => $operator,
                'value' => $value,
            );
        }

        if ( isset( $_POST['is_whitelisted'] ) && $_POST['is_whitelisted'] !== '' ) {
            $is_whitelisted = sanitize_text_field( $_POST['is_whitelisted'] );
            $criteria['is_whitelisted'] = $is_whitelisted;
        }

        if ( isset( $_POST['is_abusive'] ) && $_POST['is_abusive'] !== '' ) {
            $is_abusive = sanitize_text_field( $_POST['is_abusive'] );
            $criteria['is_abusive'] = $is_abusive;
        }

        // Add other criteria as needed

        $action = sanitize_text_field( $_POST['action'] );
        $priority = intval( $_POST['priority'] );

        // Insert the rule into the database
        global $wpdb;
        $rules_table = $wpdb->prefix . 'wtc_rules';

        $wpdb->insert(
            $rules_table,
            array(
                'criteria' => wp_json_encode( $criteria ),
                'action' => $action,
                'priority' => $priority,
            ),
            array(
                '%s',
                '%s',
                '%d',
            )
        );

        // Redirect to avoid resubmission
        wp_redirect( add_query_arg( 'message', 'rule_added', admin_url( 'options-general.php?page=wtc-settings&tab=wtc-rules' ) ) );
        exit;
    }

    // Handle deletion of a rule.
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_rule' && isset( $_GET['rule_id'] ) && check_admin_referer( 'wtc_delete_rule_nonce', '_wpnonce' ) ) {
        $rule_id = intval( $_GET['rule_id'] );
        global $wpdb;
        $rules_table = $wpdb->prefix . 'wtc_rules';

        $wpdb->delete( $rules_table, array( 'id' => $rule_id ), array( '%d' ) );

        // Redirect back to rules page
        wp_redirect( add_query_arg( 'message', 'rule_deleted', admin_url( 'options-general.php?page=wtc-settings&tab=wtc-rules' ) ) );
        exit;
    }

    // Fetch existing rules.
    global $wpdb;
    $rules_table = $wpdb->prefix . 'wtc_rules';
    $rules = $wpdb->get_results( "SELECT * FROM $rules_table ORDER BY priority DESC" );

    // Display any messages.
    if ( isset( $_GET['message'] ) ) {
        if ( $_GET['message'] === 'rule_added' ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Rule added successfully.', 'blocked-ips-for-wordfence-to-cloudflare' ) . '</p></div>';
        } elseif ( $_GET['message'] === 'rule_deleted' ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Rule deleted successfully.', 'blocked-ips-for-wordfence-to-cloudflare' ) . '</p></div>';
        }
    }

    ?>
    <h2><?php esc_html_e( 'Rule Builder', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></h2>

    <!-- Disclaimer -->
    <div class="notice notice-warning">
        <p><?php esc_html_e( 'Please use automatic mitigation rules with caution. Misconfigured rules may block legitimate traffic, including known bots. Always ensure that "isWhitelisted" is set to "false" when creating rules based on confidence scores.', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></p>
    </div>

    <!-- Existing Rules Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Priority', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th><?php esc_html_e( 'Criteria', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th><?php esc_html_e( 'Action', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <th><?php esc_html_e( 'Manage', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $rules ) ) : ?>
                <?php foreach ( $rules as $rule ) : ?>
                    <?php $criteria = json_decode( $rule->criteria, true ); ?>
                    <tr>
                        <td><?php echo esc_html( $rule->priority ); ?></td>
                        <td>
                            <?php
                            // Display criteria in a readable format
                            foreach ( $criteria as $key => $value ) {
                                echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ) . ': ';
                                if ( is_array( $value ) ) {
                                    echo esc_html( $value['operator'] . ' ' . $value['value'] );
                                } else {
                                    echo esc_html( $value );
                                }
                                echo '<br>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $rule->action ) ) ); ?></td>
                        <td>
                            <!-- Delete Button -->
                            <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete_rule', 'rule_id' => $rule->id ), admin_url( 'options-general.php?page=wtc-settings&tab=wtc-rules' ) ), 'wtc_delete_rule_nonce' ) ); ?>"><?php esc_html_e( 'Delete', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4"><?php esc_html_e( 'No rules defined.', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Add New Rule Form -->
    <h3><?php esc_html_e( 'Add New Rule', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></h3>
    <form method="post" action="">
        <?php wp_nonce_field( 'wtc_add_rule_action', 'wtc_add_rule_nonce' ); ?>
        <table class="form-table">
            <!-- Confidence Score Criteria -->
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Confidence Score', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <td>
                    <select name="confidence_score_operator">
                        <option value=">"><?php esc_html_e( '>', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                        <option value=">="><?php esc_html_e( '>=', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                        <option value="<"><?php esc_html_e( '<', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                        <option value="<="><?php esc_html_e( '<=', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                        <option value="="><?php esc_html_e( '=', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                    </select>
                    <input type="number" name="confidence_score_value" min="0" max="100" />
                </td>
            </tr>
            <!-- isWhitelisted Criteria -->
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Is Whitelisted', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <td>
                    <select name="is_whitelisted">
                        <option value=""><?php esc_html_e( 'Any', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                        <option value="true"><?php esc_html_e( 'True', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                        <option value="false"><?php esc_html_e( 'False', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                    </select>
                </td>
            </tr>
            <!-- is_abusive Criteria -->
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Is Abusive', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <td>
                    <select name="is_abusive">
                        <option value=""><?php esc_html_e( 'Any', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                        <option value="true"><?php esc_html_e( 'True', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                        <option value="false"><?php esc_html_e( 'False', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                    </select>
                </td>
            </tr>
            <!-- Action -->
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Cloudflare Action', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <td>
                    <select name="action">
                        <option value="block"><?php esc_html_e( 'Block', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                        <option value="managed_challenge"><?php esc_html_e( 'Managed Challenge', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></option>
                        <!-- Add other actions as needed -->
                    </select>
                </td>
            </tr>
            <!-- Priority -->
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Priority', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></th>
                <td>
                    <input type="number" name="priority" min="0" value="0" />
                    <p class="description"><?php esc_html_e( 'Higher priority rules are evaluated first.', 'blocked-ips-for-wordfence-to-cloudflare' ); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button( __( 'Add Rule', 'blocked-ips-for-wordfence-to-cloudflare' ) ); ?>
    </form>
    <?php
}

