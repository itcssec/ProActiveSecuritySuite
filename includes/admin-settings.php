<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Include the helpers file to ensure wtc_update_cron_schedule() is available.
require_once WTC_PLUGIN_DIR . 'includes/helpers.php';
// Add settings link to plugin page.
function wtc_add_settings_link(  $links  ) {
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
    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( 'Access is not allowed.' );
    }
    $active_tab = ( isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'wtc-settings' );
    ?>
    <div class="wrap">
        <h1><?php 
    esc_html_e( 'Wordfence to Cloudflare', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></h1>

        <!-- Add Tabs -->
        <h2 class="nav-tab-wrapper">
            <a href="?page=wtc-settings" class="nav-tab <?php 
    echo ( $active_tab === 'wtc-settings' ? 'nav-tab-active' : '' );
    ?>"><?php 
    esc_html_e( 'Settings', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></a>
            <a href="?page=wtc-settings&tab=wtc-ips" class="nav-tab <?php 
    echo ( $active_tab === 'wtc-ips' ? 'nav-tab-active' : '' );
    ?>"><?php 
    esc_html_e( 'Blocked IPs', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></a>
            <?php 
    ?>
                <a href="#" class="nav-tab disabled"><?php 
    esc_html_e( 'Captured Traffic Data', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?> <?php 
    esc_html_e( '(Premium)', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></a>
            <?php 
    ?>
        </h2>

        <!-- Display Tab Content -->
        <?php 
    switch ( $active_tab ) {
        case 'wtc-ips':
            wtc_render_ips_tab();
            break;
        case 'wtc-traffic':
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'The Captured Traffic Data feature is available in the premium version. Please upgrade to access this feature.', 'blocked-ips-for-wordfence-to-cloudflare' ) . '</p></div>';
            // Add an upgrade button
            if ( function_exists( 'wor_fs' ) ) {
                wor_fs()->get_logger()->warning( 'Attempted access to premium tab: Captured Traffic Data' );
                wor_fs()->add_upgrade_button();
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
    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( 'Access is not allowed.' );
    }
    // Handle form submission.
    if ( isset( $_POST['wtc_settings_submit'] ) && check_admin_referer( 'wtc_settings_action', 'wtc_settings_nonce' ) ) {
        // Update options securely.
        $cloudflare_email = sanitize_email( $_POST['cloudflare_email'] );
        $cloudflare_key_input = sanitize_text_field( $_POST['cloudflare_key'] );
        $cloudflare_zone_id = sanitize_text_field( $_POST['cloudflare_zone_id'] );
        $cloudflare_account_id = sanitize_text_field( $_POST['cloudflare_account_id'] );
        $abuseipdb_api_id_input = sanitize_text_field( $_POST['abuseipdb_api_id'] );
        $blocked_hits_threshold = intval( $_POST['blocked_hits_threshold'] );
        $block_scope = sanitize_text_field( $_POST['block_scope'] );
        $block_mode = sanitize_text_field( $_POST['block_mode'] );
        $cron_interval = sanitize_text_field( $_POST['cron_interval'] );
        update_option( 'cloudflare_email', $cloudflare_email );
        if ( !empty( $cloudflare_key_input ) && $cloudflare_key_input !== str_repeat( '*', 10 ) ) {
            // Securely store the Cloudflare key.
            update_option( 'cloudflare_key', $cloudflare_key_input );
        }
        update_option( 'cloudflare_zone_id', $cloudflare_zone_id );
        update_option( 'cloudflare_account_id', $cloudflare_account_id );
        if ( !empty( $abuseipdb_api_id_input ) && $abuseipdb_api_id_input !== str_repeat( '*', 10 ) ) {
            update_option( 'abuseipdb_api_id', $abuseipdb_api_id_input );
        }
        // Enable AbuseIPDB Lookup Option
        $wtc_enable_abuseipdb = ( isset( $_POST['wtc_enable_abuseipdb'] ) ? 'yes' : 'no' );
        update_option( 'wtc_enable_abuseipdb', $wtc_enable_abuseipdb );
        update_option( 'blocked_hits_threshold', $blocked_hits_threshold );
        update_option( 'block_scope', $block_scope );
        update_option( 'block_mode', $block_mode );
        update_option( 'cron_interval', $cron_interval );
        // Update the cron schedule.
        wtc_update_cron_schedule();
        // Display a success message.
        add_settings_error(
            'wtc_settings',
            'wtc_settings_updated',
            __( 'Settings saved.', 'blocked-ips-for-wordfence-to-cloudflare' ),
            'updated'
        );
    }
    // Get options.
    $cloudflare_email = get_option( 'cloudflare_email', '' );
    $cloudflare_key = get_option( 'cloudflare_key', '' );
    $cloudflare_zone_id = get_option( 'cloudflare_zone_id', '' );
    $cloudflare_account_id = get_option( 'cloudflare_account_id', '' );
    $abuseipdb_api_id = get_option( 'abuseipdb_api_id', '' );
    $whatismybr_api_id = get_option( 'whatismybr_api_id', '' );
    $blocked_hits_threshold = get_option( 'blocked_hits_threshold', 0 );
    $block_scope = get_option( 'block_scope', 'domain' );
    $block_mode = get_option( 'block_mode', 'block' );
    $cron_interval = get_option( 'cron_interval', 'hourly' );
    $wtc_last_processed_time = get_option( 'wtc_last_processed_time', '' );
    $wtc_processed_ips_count = get_option( 'wtc_processed_ips_count', 0 );
    // Get the traffic capture setting
    $wtc_enable_traffic_capture = get_option( 'wtc_enable_traffic_capture', 'yes' );
    // Get the AbuseIPDB enable setting
    $wtc_enable_abuseipdb = get_option( 'wtc_enable_abuseipdb', 'no' );
    settings_errors( 'wtc_settings' );
    ?>
    <div class="wrap">
        <h1><?php 
    esc_html_e( 'Settings', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></h1>
        <form method="post" action="">
            <?php 
    wp_nonce_field( 'wtc_settings_action', 'wtc_settings_nonce' );
    ?>
            <table class="form-table">
                <!-- Cloudflare Settings -->
                <tr valign="top">
                    <th scope="row"><?php 
    esc_html_e( 'Cloudflare Email', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                    <td><input type="email" name="cloudflare_email" value="<?php 
    echo esc_attr( $cloudflare_email );
    ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php 
    esc_html_e( 'Cloudflare Key', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                    <td><input type="password" name="cloudflare_key" value="<?php 
    echo esc_attr( str_repeat( '*', 10 ) );
    ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php 
    esc_html_e( 'Cloudflare Zone ID', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                    <td><input type="text" name="cloudflare_zone_id" value="<?php 
    echo esc_attr( $cloudflare_zone_id );
    ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php 
    esc_html_e( 'Cloudflare Account ID', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                    <td><input type="text" name="cloudflare_account_id" value="<?php 
    echo esc_attr( $cloudflare_account_id );
    ?>" /></td>
                </tr>

                <!-- AbuseIPDB Settings -->
                <tr valign="top">
                    <th scope="row"><?php 
    esc_html_e( 'AbuseIPDB API Key', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                    <td>
                        <?php 
    if ( !empty( $abuseipdb_api_id ) ) {
        ?>
                            <input type="password" name="abuseipdb_api_id" value="<?php 
        echo esc_attr( str_repeat( '*', 10 ) );
        ?>" />
                        <?php 
    } else {
        ?>
                            <input type="password" name="abuseipdb_api_id" value="" />
                        <?php 
    }
    ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php 
    esc_html_e( 'Enable AbuseIPDB Lookup', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                    <td><input type="checkbox" name="wtc_enable_abuseipdb" value="yes" <?php 
    checked( 'yes', $wtc_enable_abuseipdb );
    ?> /></td>
                </tr>

                <!-- Premium Features -->
                <?php 
    ?>
                    <!-- WhatIsMyBrowser API Key (Disabled) -->
                    <tr valign="top">
                        <th scope="row"><?php 
    esc_html_e( 'WhatIsMyBrowser API Key', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                        <td>
                            <input type="password" disabled value="" />
                            <p class="description"><?php 
    esc_html_e( 'Available in the premium version.', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></p>
                        </td>
                    </tr>
                <?php 
    ?>

                <!-- Blocked Hits Threshold -->
                <tr valign="top">
                    <th scope="row"><?php 
    esc_html_e( 'Blocked Hits Threshold', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                    <td><input type="number" min="0" name="blocked_hits_threshold" value="<?php 
    echo esc_attr( $blocked_hits_threshold );
    ?>" /></td>
                </tr>

                <!-- Block Scope -->
                <tr valign="top">
                    <th scope="row"><?php 
    esc_html_e( 'Block Scope', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                    <td>
                        <select name="block_scope">
                            <option value="domain" <?php 
    selected( 'domain', $block_scope );
    ?>><?php 
    esc_html_e( 'Domain Specific', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></option>
                            <option value="account" <?php 
    selected( 'account', $block_scope );
    ?>><?php 
    esc_html_e( 'Entire Account', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></option>
                        </select>
                    </td>
                </tr>

                <!-- Block Mode -->
                <tr valign="top">
                    <th scope="row"><?php 
    esc_html_e( 'Block Mode', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                    <td>
                        <select name="block_mode">
                            <option value="block" <?php 
    selected( 'block', $block_mode );
    ?>><?php 
    esc_html_e( 'Block', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></option>
                            <option value="managed_challenge" <?php 
    selected( 'managed_challenge', $block_mode );
    ?>><?php 
    esc_html_e( 'Managed Challenge', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></option>
                        </select>
                    </td>
                </tr>

                <!-- Cron Interval -->
                <tr valign="top">
                    <th scope="row"><?php 
    esc_html_e( 'Cron Interval', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                    <td>
                        <select name="cron_interval">
                            <option value="none" <?php 
    selected( 'none', $cron_interval );
    ?>><?php 
    esc_html_e( 'Not Set', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></option>
                            <option value="5min" <?php 
    selected( '5min', $cron_interval );
    ?>><?php 
    esc_html_e( 'Every 5 Minutes', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></option>
                            <option value="hourly" <?php 
    selected( 'hourly', $cron_interval );
    ?>><?php 
    esc_html_e( '1 hour', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></option>
                            <option value="twicedaily" <?php 
    selected( 'twicedaily', $cron_interval );
    ?>><?php 
    esc_html_e( '12 hours', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></option>
                            <option value="daily" <?php 
    selected( 'daily', $cron_interval );
    ?>><?php 
    esc_html_e( '24 hours', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></option>
                        </select>
                    </td>
                </tr>

                <!-- Enable Traffic Capture -->
                <?php 
    ?>
                    <tr valign="top">
                        <th scope="row"><?php 
    esc_html_e( 'Enable Traffic Capture', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                        <td>
                            <input type="checkbox" disabled />
                            <p class="description"><?php 
    esc_html_e( 'Available in the premium version.', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></p>
                        </td>
                    </tr>
                <?php 
    ?>
            </table>

            <!-- Cron Status -->
            <h2><?php 
    esc_html_e( 'Cron Status', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php 
    esc_html_e( 'Last Cron Run:', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                    <td><?php 
    echo esc_html( $wtc_last_processed_time );
    ?></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php 
    esc_html_e( 'IPs Processed:', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></th>
                    <td><?php 
    echo esc_html( $wtc_processed_ips_count );
    ?></td>
                </tr>
            </table>

            <?php 
    submit_button( __( 'Save Settings', 'blocked-ips-for-wordfence-to-cloudflare' ), 'primary', 'wtc_settings_submit' );
    ?>
        </form>

        <!-- Run Process Manually -->
        <form method="post" action="<?php 
    echo esc_url( admin_url( 'admin-post.php?action=wtc_run_process' ) );
    ?>">
            <?php 
    wp_nonce_field( 'wtc_run_process_action', 'wtc_run_process_nonce' );
    ?>
            <button type="submit" name="wtc_run_process" class="button button-primary"><?php 
    esc_html_e( 'Run Process', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></button>
        </form>

        <!-- Clear Data -->
        <br><br>
        <form method="post" action="<?php 
    echo esc_url( admin_url( 'admin-post.php' ) );
    ?>">
            <?php 
    wp_nonce_field( 'wtc_clear_data_action', 'wtc_clear_data_nonce' );
    ?>
            <input type="hidden" name="action" value="wtc_clear_data">
            <button type="submit" name="wtc_clear_data" class="button button-secondary"><?php 
    esc_html_e( 'Clear Data', 'blocked-ips-for-wordfence-to-cloudflare' );
    ?></button>
        </form>

        <!-- Delete Captured Traffic (Premium Only) -->
        <?php 
    ?>
    </div>
    <?php 
}

// Handle deletion of captured traffic data (Premium Only).
function wtc_handle_delete_captured_traffic_action() {
    if ( isset( $_POST['action'] ) && $_POST['action'] === 'wtc_delete_captured_traffic' && check_admin_referer( 'wtc_delete_captured_traffic', 'wtc_delete_nonce' ) ) {
        wp_die( 'Access is not allowed.' );
        global $wpdb;
        $table_name = $wpdb->prefix . 'wtc_traffic_data';
        $wpdb->query( "TRUNCATE TABLE {$table_name}" );
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
// Add CSS for disabled tabs
function wtc_admin_styles() {
    echo '<style>
    .nav-tab.disabled {
        color: #a7aaad;
        pointer-events: none;
        cursor: default;
    }
    </style>';
}

add_action( 'admin_head', 'wtc_admin_styles' );