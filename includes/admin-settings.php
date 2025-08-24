<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include the helpers file to ensure pssx_update_cron_schedule() is available.
require_once PSSX_PLUGIN_DIR . 'includes/helpers.php';

// Include our new premium class file (only if premium is active).
if ( function_exists( 'pssx_fs' ) && pssx_fs()->is__premium_only() ) {
    require_once PSSX_PLUGIN_DIR . 'includes/class-wtc-traffic-insights.php';
}

/**
 * Add the Settings link to the plugin listing page.
 */
function pssx_add_settings_link( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=pss-settings' ) ) . '">' . __( 'Settings', 'proactive-security-suite' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pssx_add_settings_link' );

/**
 * Add the "PSS Settings" submenu under "Settings".
 */
function pssx_menu() {
    add_options_page(
        'PSS Settings',
        'PSS Settings',
        'manage_options',
        'pss-settings',
        'pssx_render_admin_page'
    );
}
add_action( 'admin_menu', 'pssx_menu' );

/**
 * Main admin page router.
 */
function pssx_render_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Access is not allowed.', 'proactive-security-suite' ) );
    }

    // Build the base URL for our settings page.
    $base_url = admin_url( 'options-general.php?page=pss-settings' );

    // Generate links for each tab (without a tab-switch nonce).
    $settings_tab_url = add_query_arg( array( 'tab' => 'pss-settings' ), $base_url );
    $ips_tab_url      = add_query_arg( array( 'tab' => 'pssx-ips' ), $base_url );

    if ( function_exists( 'pssx_fs' ) && pssx_fs()->is__premium_only() ) {
        $traffic_tab_url  = add_query_arg( array( 'tab' => 'pssx-traffic' ), $base_url );
        $rules_tab_url    = add_query_arg( array( 'tab' => 'pssx-rules' ), $base_url );
        $insights_tab_url = add_query_arg( array( 'tab' => 'pssx-insights' ), $base_url );
    }

    // Default tab is 'pss-settings'; override if ?tab= is present.
    $active_tab = 'pss-settings';
    if ( isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ) {
        $active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Proactive Security Suite', 'proactive-security-suite' ); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="<?php echo esc_url( $settings_tab_url ); ?>"
               class="nav-tab <?php echo ( $active_tab === 'pss-settings' ) ? 'nav-tab-active' : ''; ?>">
               <?php esc_html_e( 'Settings', 'proactive-security-suite' ); ?>
            </a>
            <a href="<?php echo esc_url( $ips_tab_url ); ?>"
               class="nav-tab <?php echo ( $active_tab === 'pssx-ips' ) ? 'nav-tab-active' : ''; ?>">
               <?php esc_html_e( 'Blocked IPs', 'proactive-security-suite' ); ?>
            </a>

            <?php if ( function_exists( 'pssx_fs' ) && pssx_fs()->is__premium_only() ) : ?>
                <a href="<?php echo esc_url( $traffic_tab_url ); ?>"
                   class="nav-tab <?php echo ( $active_tab === 'pssx-traffic' ) ? 'nav-tab-active' : ''; ?>">
                   <?php esc_html_e( 'Captured Traffic Data', 'proactive-security-suite' ); ?>
                </a>
                <a href="<?php echo esc_url( $rules_tab_url ); ?>"
                   class="nav-tab <?php echo ( $active_tab === 'pssx-rules' ) ? 'nav-tab-active' : ''; ?>">
                   <?php esc_html_e( 'Rule Builder', 'proactive-security-suite' ); ?>
                </a>
                <a href="<?php echo esc_url( $insights_tab_url ); ?>"
                   class="nav-tab <?php echo ( $active_tab === 'pssx-insights' ) ? 'nav-tab-active' : ''; ?>">
                   <?php esc_html_e( 'Traffic Insights', 'proactive-security-suite' ); ?>
                </a>
            <?php else : ?>
                <a href="#" class="nav-tab disabled">
                    <?php esc_html_e( 'Captured Traffic Data (Premium)', 'proactive-security-suite' ); ?>
                </a>
                <a href="#" class="nav-tab disabled">
                    <?php esc_html_e( 'Rule Builder (Premium)', 'proactive-security-suite' ); ?>
                </a>
                <a href="#" class="nav-tab disabled">
                    <?php esc_html_e( 'Traffic Insights (Premium)', 'proactive-security-suite' ); ?>
                </a>
            <?php endif; ?>
        </h2>

        <?php
        switch ( $active_tab ) {
            case 'pssx-ips':
                pssx_render_ips_tab();
                break;
            case 'pssx-traffic':
                if ( function_exists( 'pssx_fs' ) && pssx_fs()->is__premium_only() ) {
                    include 'captured-traffic.php';
                    pssx_render_traffic_tab();
                } else {
                    echo '<div class="notice notice-warning"><p>' .
                         esc_html__( 'The Captured Traffic Data feature is available in the premium version. Please upgrade to access this feature.', 'proactive-security-suite' ) .
                         '</p></div>';
                    if ( function_exists( 'pssx_fs' ) ) {
                        pssx_fs()->get_logger()->warning( 'Attempted access to premium tab: Captured Traffic Data' );
                        pssx_fs()->add_upgrade_button();
                    }
                }
                break;
            case 'pssx-rules':
                if ( function_exists( 'pssx_fs' ) && pssx_fs()->is__premium_only() ) {
                    include 'rule-builder.php';
                    pssx_render_rule_builder_tab();
                } else {
                    echo '<div class="notice notice-warning"><p>' .
                         esc_html__( 'The Rule Builder feature is available in the premium version. Please upgrade to access this feature.', 'proactive-security-suite' ) .
                         '</p></div>';
                    if ( function_exists( 'pssx_fs' ) ) {
                        pssx_fs()->get_logger()->warning( 'Attempted access to premium tab: Rule Builder' );
                        pssx_fs()->add_upgrade_button();
                    }
                }
                break;
            case 'pssx-insights':
                if ( function_exists( 'pssx_fs' ) && pssx_fs()->is__premium_only() ) {
                    pssx_render_traffic_insights_tab();
                } else {
                    echo '<div class="notice notice-warning"><p>' .
                         esc_html__( 'Traffic Insights is available in the premium version. Please upgrade to access this feature.', 'proactive-security-suite' ) .
                         '</p></div>';
                    if ( function_exists( 'pssx_fs' ) ) {
                        pssx_fs()->get_logger()->warning( 'Attempted access to premium tab: Traffic Insights' );
                        pssx_fs()->add_upgrade_button();
                    }
                }
                break;
            default:
                pssx_render_settings_tab();
                break;
        }
        ?>
    </div>
    <?php

    }

/**
 * Renders the premium-only "Traffic Insights" tab.
 */
function pssx_render_traffic_insights_tab() {
    if ( class_exists( 'PSSX_Traffic_Insights' ) ) {
        PSSX_Traffic_Insights::render_insights_page();
    } else {
        echo '<div class="notice notice-error"><p>' .
             esc_html__( 'Traffic Insights class is not available.', 'proactive-security-suite' ) .
             '</p></div>';
    }
}

/**
 * Renders the main (default) "Settings" tab.
 */
function pssx_render_settings_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Access is not allowed.', 'proactive-security-suite' ) );
    }

    $pssx_recreate_waf_rule = isset( $_POST['pssx_recreate_waf_rule'] );

    // Check form submission with nonce.
    if (
        ( isset( $_POST['pssx_settings_submit'] ) || $pssx_recreate_waf_rule ) &&
        isset( $_POST['pssx_settings_nonce'] ) &&
        check_admin_referer( 'pssx_settings_action', 'pssx_settings_nonce' )
    ) {
        // Collect data with proper sanitization.
        $cloudflare_email = '';
        if ( isset( $_POST['pssx_cloudflare_email'] ) ) {
            $cloudflare_email = sanitize_email( wp_unslash( $_POST['pssx_cloudflare_email'] ) );
        }
        $cloudflare_key_input = '';
        if ( isset( $_POST['pssx_cloudflare_key'] ) ) {
            $cloudflare_key_input = sanitize_text_field( wp_unslash( $_POST['pssx_cloudflare_key'] ) );
        }
        $cloudflare_zone_id = '';
        if ( isset( $_POST['pssx_cloudflare_zone_id'] ) ) {
            $cloudflare_zone_id = sanitize_text_field( wp_unslash( $_POST['pssx_cloudflare_zone_id'] ) );
        }
        $cloudflare_account_id = '';
        if ( isset( $_POST['pssx_cloudflare_account_id'] ) ) {
            $cloudflare_account_id = sanitize_text_field( wp_unslash( $_POST['pssx_cloudflare_account_id'] ) );
        }
        $abuseipdb_api_id_input = '';
        if ( isset( $_POST['pssx_abuseipdb_api_key'] ) ) {
            $abuseipdb_api_id_input = sanitize_text_field( wp_unslash( $_POST['pssx_abuseipdb_api_key'] ) );
        }
        $blocked_hits_threshold = 0;
        if ( isset( $_POST['pssx_blocked_hits_threshold'] ) ) {
            $blocked_hits_threshold = intval( wp_unslash( $_POST['pssx_blocked_hits_threshold'] ) );
        }
        $block_scope = '';
        if ( isset( $_POST['pssx_block_scope'] ) ) {
            $block_scope = sanitize_text_field( wp_unslash( $_POST['pssx_block_scope'] ) );
        }
        $block_mode = '';
        if ( isset( $_POST['pssx_block_mode'] ) ) {
            $block_mode = sanitize_text_field( wp_unslash( $_POST['pssx_block_mode'] ) );
        }
        $cron_interval = '';
        if ( isset( $_POST['pssx_cron_interval'] ) ) {
            $cron_interval = sanitize_text_field( wp_unslash( $_POST['pssx_cron_interval'] ) );
        }

        $pssx_use_waf_rule  = isset( $_POST['pssx_use_waf_rule'] ) ? 'yes' : 'no';
        $pssx_waf_rule_name = '';
        if ( isset( $_POST['pssx_waf_rule_name'] ) ) {
            $pssx_waf_rule_name = sanitize_text_field( wp_unslash( $_POST['pssx_waf_rule_name'] ) );
        }

        // Update options
        update_option( 'pssx_cloudflare_email', $cloudflare_email );
        if ( ! empty( $cloudflare_key_input ) && $cloudflare_key_input !== str_repeat( '*', 10 ) ) {
            update_option( 'pssx_cloudflare_key', $cloudflare_key_input );
        }
        update_option( 'pssx_cloudflare_zone_id', $cloudflare_zone_id );
        update_option( 'pssx_cloudflare_account_id', $cloudflare_account_id );

        if ( ! empty( $abuseipdb_api_id_input ) && $abuseipdb_api_id_input !== str_repeat( '*', 10 ) ) {
            update_option( 'pssx_abuseipdb_api_key', $abuseipdb_api_id_input );
        }

        $pssx_enable_abuseipdb = isset( $_POST['pssx_enable_abuseipdb'] ) ? 'yes' : 'no';
        update_option( 'pssx_enable_abuseipdb', $pssx_enable_abuseipdb );

        update_option( 'pssx_use_waf_rule', $pssx_use_waf_rule );
        if ( 'no' === $pssx_use_waf_rule ) {
            delete_option( 'pssx_cf_rule_id' );
            delete_option( 'pssx_cf_list_id' );
            error_log( 'pssx_use_waf_rule disabled: Cloudflare WAF rule and list IDs removed.' );
        }
        update_option( 'pssx_waf_rule_name', $pssx_waf_rule_name );

        if ( function_exists( 'pssx_fs' ) && pssx_fs()->is__premium_only() ) {
            // Premium fields
            $whatismybr_api_id_input = '';
            if ( isset( $_POST['pssx_whatismybr_api_id'] ) ) {
                $whatismybr_api_id_input = sanitize_text_field( wp_unslash( $_POST['pssx_whatismybr_api_id'] ) );
            }
            if ( ! empty( $whatismybr_api_id_input ) && $whatismybr_api_id_input !== str_repeat( '*', 10 ) ) {
                update_option( 'pssx_whatismybr_api_id', $whatismybr_api_id_input );
            }

            $pssx_enable_traffic_capture = isset( $_POST['pssx_enable_traffic_capture'] ) ? 'yes' : 'no';
            update_option( 'pssx_enable_traffic_capture', $pssx_enable_traffic_capture );

            $pssx_enable_abuseipdb_lookup_traffic = isset( $_POST['pssx_enable_abuseipdb_lookup_traffic'] ) ? 'yes' : 'no';
            update_option( 'pssx_enable_abuseipdb_lookup_traffic', $pssx_enable_abuseipdb_lookup_traffic );

            $ipdata_api_id_input = '';
            if ( isset( $_POST['pssx_ipdata_api_id'] ) ) {
                $ipdata_api_id_input = sanitize_text_field( wp_unslash( $_POST['pssx_ipdata_api_id'] ) );
            }
            if ( ! empty( $ipdata_api_id_input ) && $ipdata_api_id_input !== str_repeat( '*', 10 ) ) {
                update_option( 'pssx_ipdata_api_id', $ipdata_api_id_input );
            }

            $pssx_enable_ipdata_lookup_traffic = isset( $_POST['pssx_enable_ipdata_lookup_traffic'] ) ? 'yes' : 'no';
            update_option( 'pssx_enable_ipdata_lookup_traffic', $pssx_enable_ipdata_lookup_traffic );

            if ( isset( $_POST['pssx_excluded_roles'] ) && is_array( $_POST['pssx_excluded_roles'] ) ) {
                $clean_roles = array_map( 'sanitize_text_field', wp_unslash( $_POST['pssx_excluded_roles'] ) );
                update_option( 'pssx_excluded_roles', $clean_roles );
            } else {
                update_option( 'pssx_excluded_roles', array() );
            }
        }

        if ( $pssx_recreate_waf_rule ) {
            delete_option( 'pssx_cf_rule_id' );
            delete_option( 'pssx_cf_list_id' );
            pssx_create_cf_waf_rule();
            if ( get_option( 'pssx_cf_rule_id' ) && get_option( 'pssx_cf_list_id' ) ) {
                pssx_add_admin_notice( __( 'Cloudflare WAF rule recreated successfully.', 'proactive-security-suite' ), 'success' );
            } else {
                pssx_add_admin_notice( __( 'Failed to recreate Cloudflare WAF rule.', 'proactive-security-suite' ) );
            }
        } elseif ( 'yes' === $pssx_use_waf_rule ) {
            pssx_create_cf_waf_rule();
        }

        update_option( 'pssx_blocked_hits_threshold', $blocked_hits_threshold );
        update_option( 'pssx_block_scope', $block_scope );
        update_option( 'pssx_block_mode', $block_mode );
        update_option( 'pssx_cron_interval', $cron_interval );

        // Update cron schedule if changed
        pssx_update_cron_schedule();

        add_settings_error( 'pssx_settings', 'pssx_settings_updated', __( 'Settings saved.', 'proactive-security-suite' ), 'updated' );
    }

    // Retrieve existing settings
    $cloudflare_email                   = get_option( 'pssx_cloudflare_email', '' );
    $cloudflare_key                     = get_option( 'pssx_cloudflare_key', '' );
    $cloudflare_zone_id                 = get_option( 'pssx_cloudflare_zone_id', '' );
    $cloudflare_account_id              = get_option( 'pssx_cloudflare_account_id', '' );
    $abuseipdb_api_id                   = get_option( 'pssx_abuseipdb_api_key', '' );
    $whatismybr_api_id                  = get_option( 'pssx_whatismybr_api_id', '' );
    $blocked_hits_threshold             = get_option( 'pssx_blocked_hits_threshold', 0 );
    $block_scope                        = get_option( 'pssx_block_scope', 'domain' );
    $block_mode                         = get_option( 'pssx_block_mode', 'block' );
    $cron_interval                      = get_option( 'pssx_cron_interval', 'hourly' );
    $pssx_last_processed_time           = get_option( 'pssx_last_processed_time', '' );
    $pssx_processed_ips_count           = get_option( 'pssx_processed_ips_count', 0 );
    $pssx_enable_traffic_capture        = get_option( 'pssx_enable_traffic_capture', 'yes' );
    $pssx_enable_abuseipdb             = get_option( 'pssx_enable_abuseipdb', 'no' );
    $pssx_enable_abuseipdb_lookup_traffic = get_option( 'pssx_enable_abuseipdb_lookup_traffic', 'no' );
    $pssx_excluded_roles               = get_option( 'pssx_excluded_roles', array() );
    $editable_roles                    = get_editable_roles();
    $ipdata_api_id                     = get_option( 'pssx_ipdata_api_id', '' );
    $pssx_enable_ipdata_lookup_traffic = get_option( 'pssx_enable_ipdata_lookup_traffic', 'no' );
    $pssx_use_waf_rule                 = get_option( 'pssx_use_waf_rule', 'no' );
    $pssx_waf_rule_name                = get_option( 'pssx_waf_rule_name', '' );

    settings_errors( 'pssx_settings' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Settings', 'proactive-security-suite' ); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'pssx_settings_action', 'pssx_settings_nonce' ); ?>
            <table class="form-table">
                <!-- Cloudflare Settings -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Cloudflare Email', 'proactive-security-suite' ); ?></th>
                    <td>
                        <input type="email" name="pssx_cloudflare_email"
                               value="<?php echo esc_attr( $cloudflare_email ); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Cloudflare Key', 'proactive-security-suite' ); ?></th>
                    <td>
                        <input type="password" name="pssx_cloudflare_key"
                               value="<?php echo esc_attr( str_repeat('*', 10) ); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Cloudflare Zone ID', 'proactive-security-suite' ); ?></th>
                    <td>
                        <input type="text" name="pssx_cloudflare_zone_id"
                               value="<?php echo esc_attr( $cloudflare_zone_id ); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Cloudflare Account ID', 'proactive-security-suite' ); ?></th>
                    <td>
                        <input type="text" name="pssx_cloudflare_account_id"
                               value="<?php echo esc_attr( $cloudflare_account_id ); ?>" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Use Cloudflare WAF Rule', 'proactive-security-suite' ); ?></th>
                    <td>
                        <input type="checkbox" name="pssx_use_waf_rule" value="yes"
                               <?php checked( 'yes', $pssx_use_waf_rule ); ?> />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'WAF Rule Name', 'proactive-security-suite' ); ?></th>
                    <td>
                        <input type="text" name="pssx_waf_rule_name"
                               value="<?php echo esc_attr( $pssx_waf_rule_name ); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">&nbsp;</th>
                    <td>
                        <button type="submit" name="pssx_recreate_waf_rule" class="button">
                            <?php esc_html_e( 'Recreate WAF Rule', 'proactive-security-suite' ); ?>
                        </button>
                    </td>
                </tr>

                <!-- AbuseIPDB Settings -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'AbuseIPDB API Key', 'proactive-security-suite' ); ?></th>
                    <td>
                        <?php if ( ! empty( $abuseipdb_api_id ) ) : ?>
                            <input type="password" name="pssx_abuseipdb_api_key"
                                   value="<?php echo esc_attr( str_repeat('*', 10) ); ?>" />
                        <?php else : ?>
                            <input type="password" name="pssx_abuseipdb_api_key" value="" />
                        <?php endif; ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Enable AbuseIPDB Lookup', 'proactive-security-suite' ); ?></th>
                    <td>
                        <input type="checkbox" name="pssx_enable_abuseipdb" value="yes"
                               <?php checked( 'yes', $pssx_enable_abuseipdb ); ?> />
                    </td>
                </tr>

                <?php if ( function_exists( 'pssx_fs' ) && pssx_fs()->is__premium_only() ) : ?>
                    <!-- WhatIsMyBrowser API Key -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'WhatIsMyBrowser API Key', 'proactive-security-suite' ); ?></th>
                        <td>
                            <?php if ( ! empty( $whatismybr_api_id ) ) : ?>
                                <input type="password" name="pssx_whatismybr_api_id"
                                       value="<?php echo esc_attr( str_repeat('*',10) ); ?>" />
                            <?php else : ?>
                                <input type="password" name="pssx_whatismybr_api_id" value="" />
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Enable Traffic Capture -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Enable Traffic Capture', 'proactive-security-suite' ); ?></th>
                        <td>
                            <input type="checkbox" name="pssx_enable_traffic_capture" value="yes"
                                   <?php checked( 'yes', $pssx_enable_traffic_capture ); ?> />
                        </td>
                    </tr>

                    <!-- Enable AbuseIPDB Lookup for Captured Traffic -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Enable AbuseIPDB Lookup for Captured Traffic', 'proactive-security-suite' ); ?></th>
                        <td>
                            <input type="checkbox" name="pssx_enable_abuseipdb_lookup_traffic" value="yes"
                                   <?php checked( 'yes', $pssx_enable_abuseipdb_lookup_traffic ); ?> />
                        </td>
                    </tr>

                    <!-- IPData API Key -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'IPData API Key', 'proactive-security-suite' ); ?></th>
                        <td>
                            <?php if ( ! empty( $ipdata_api_id ) ) : ?>
                                <input type="password" name="pssx_ipdata_api_id"
                                       value="<?php echo esc_attr( str_repeat('*', 10) ); ?>" />
                            <?php else : ?>
                                <input type="password" name="pssx_ipdata_api_id" value="" />
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Enable IPData Lookup for Captured Traffic -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Enable IPData Lookup for Captured Traffic', 'proactive-security-suite' ); ?></th>
                        <td>
                            <input type="checkbox" name="pssx_enable_ipdata_lookup_traffic" value="yes"
                                   <?php checked( 'yes', $pssx_enable_ipdata_lookup_traffic ); ?> />
                        </td>
                    </tr>

                    <!-- Exclude Roles from Captured Traffic -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Exclude Roles from Captured Traffic', 'proactive-security-suite' ); ?></th>
                        <td>
                            <?php foreach ( $editable_roles as $role_slug => $role_details ) : ?>
                                <label>
                                    <input type="checkbox" name="pssx_excluded_roles[]"
                                           value="<?php echo esc_attr( $role_slug ); ?>"
                                           <?php checked( in_array( $role_slug, $pssx_excluded_roles, true ) ); ?> />
                                    <?php echo esc_html( translate_user_role( $role_details['name'] ) ); ?>
                                </label>
                                <br>
                            <?php endforeach; ?>
                            <p class="description">
                                <?php esc_html_e( 'Select the user roles to exclude from traffic logging.', 'proactive-security-suite' ); ?>
                            </p>
                        </td>
                    </tr>
                <?php else : ?>
                    <!-- Readonly placeholders for premium fields -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'WhatIsMyBrowser API Key', 'proactive-security-suite' ); ?></th>
                        <td>
                            <input type="password" disabled value="" />
                            <p class="description"><?php esc_html_e( 'Available in the premium version.', 'proactive-security-suite' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Enable Traffic Capture', 'proactive-security-suite' ); ?></th>
                        <td>
                            <input type="checkbox" disabled />
                            <p class="description"><?php esc_html_e( 'Available in the premium version.', 'proactive-security-suite' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Enable AbuseIPDB Lookup for Captured Traffic', 'proactive-security-suite' ); ?></th>
                        <td>
                            <input type="checkbox" disabled />
                            <p class="description"><?php esc_html_e( 'Available in the premium version.', 'proactive-security-suite' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Exclude Roles from Captured Traffic', 'proactive-security-suite' ); ?></th>
                        <td>
                            <p class="description"><?php esc_html_e( 'Available in the premium version.', 'proactive-security-suite' ); ?></p>
                        </td>
                    </tr>
                <?php endif; ?>

                <!-- Blocked Hits Threshold -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Blocked Hits Threshold', 'proactive-security-suite' ); ?></th>
                    <td>
                        <input type="number" min="0" name="pssx_blocked_hits_threshold"
                               value="<?php echo esc_attr( $blocked_hits_threshold ); ?>" />
                    </td>
                </tr>

                <!-- Block Scope -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Block Scope', 'proactive-security-suite' ); ?></th>
                    <td>
                        <select name="pssx_block_scope">
                            <option value="domain" <?php selected( 'domain', $block_scope ); ?>>
                                <?php esc_html_e( 'Domain Specific', 'proactive-security-suite' ); ?>
                            </option>
                            <option value="account" <?php selected( 'account', $block_scope ); ?>>
                                <?php esc_html_e( 'Entire Account', 'proactive-security-suite' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>

                <!-- Block Mode -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Block Mode', 'proactive-security-suite' ); ?></th>
                    <td>
                        <select name="pssx_block_mode">
                            <option value="block" <?php selected( 'block', $block_mode ); ?>>
                                <?php esc_html_e( 'Block', 'proactive-security-suite' ); ?>
                            </option>
                            <option value="managed_challenge" <?php selected( 'managed_challenge', $block_mode ); ?>>
                                <?php esc_html_e( 'Managed Challenge', 'proactive-security-suite' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>

                <!-- Cron Interval -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Cron Interval', 'proactive-security-suite' ); ?></th>
                    <td>
                        <select name="pssx_cron_interval">
                            <option value="none" <?php selected( 'none', $cron_interval ); ?>>
                                <?php esc_html_e( 'Not Set', 'proactive-security-suite' ); ?>
                            </option>
                            <option value="1min" <?php selected( '1min', $cron_interval ); ?>>
                                <?php esc_html_e( 'Every Minute', 'proactive-security-suite' ); ?>
                            </option>
                            <option value="5min" <?php selected( '5min', $cron_interval ); ?>>
                                <?php esc_html_e( 'Every 5 Minutes', 'proactive-security-suite' ); ?>
                            </option>
                            <option value="hourly" <?php selected( 'hourly', $cron_interval ); ?>>
                                <?php esc_html_e( '1 hour', 'proactive-security-suite' ); ?>
                            </option>
                            <option value="twicedaily" <?php selected( 'twicedaily', $cron_interval ); ?>>
                                <?php esc_html_e( '12 hours', 'proactive-security-suite' ); ?>
                            </option>
                            <option value="daily" <?php selected( 'daily', $cron_interval ); ?>>
                                <?php esc_html_e( '24 hours', 'proactive-security-suite' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Cron Status', 'proactive-security-suite' ); ?></h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Last Cron Run:', 'proactive-security-suite' ); ?></th>
                    <td><?php echo esc_html( $pssx_last_processed_time ); ?></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'IPs Processed:', 'proactive-security-suite' ); ?></th>
                    <td><?php echo esc_html( $pssx_processed_ips_count ); ?></td>
                </tr>
            </table>

            <?php submit_button( __( 'Save Settings', 'proactive-security-suite' ), 'primary', 'pssx_settings_submit' ); ?>
        </form>

        <form method="post"
              action="<?php echo esc_url( admin_url( 'admin-post.php?action=pssx_run_process' ) ); ?>">
            <?php wp_nonce_field( 'pssx_run_process_action', 'pssx_run_process_nonce' ); ?>
            <button type="submit" name="pssx_run_process" class="button button-primary">
                <?php esc_html_e( 'Run Process', 'proactive-security-suite' ); ?>
            </button>
        </form>

        <br><br>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'pssx_clear_data_action', 'pssx_clear_data_nonce' ); ?>
            <input type="hidden" name="action" value="pssx_clear_data">
            <button type="submit" name="pssx_clear_data" class="button button-secondary">
                <?php esc_html_e( 'Clear Data', 'proactive-security-suite' ); ?>
            </button>
        </form>

        <?php if ( function_exists( 'pssx_fs' ) && pssx_fs()->is__premium_only() ) : ?>
            <br><br>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'pssx_delete_captured_traffic', 'pssx_delete_nonce' ); ?>
                <input type="hidden" name="action" value="pssx_delete_captured_traffic">
                <button type="submit" name="pssx_delete_captured_traffic" class="button button-secondary">
                    <?php esc_html_e( 'Delete All Captured Traffic', 'proactive-security-suite' ); ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Handle deleting captured traffic (Premium).
 */
function pssx_handle_delete_captured_traffic_action() {
    if (
        isset( $_POST['action'] ) &&
        'pssx_delete_captured_traffic' === $_POST['action'] &&
        check_admin_referer( 'pssx_delete_captured_traffic', 'pssx_delete_nonce' )
    ) {
        // Check premium status
        if ( ! function_exists( 'pssx_fs' ) || ! pssx_fs()->is__premium_only() ) {
            wp_die( esc_html__( 'Access is not allowed.', 'proactive-security-suite' ) );
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'pssx_traffic_data';
        $wpdb->query( "TRUNCATE TABLE `{$table_name}`" );

        $redirect_url = add_query_arg(
            'pssx_message',
            'deleted',
            admin_url( 'options-general.php?page=pss-settings&tab=pssx-traffic' )
        );
        wp_safe_redirect( esc_url_raw( $redirect_url ) );
        exit;
    }
}
add_action( 'admin_post_pssx_delete_captured_traffic', 'pssx_handle_delete_captured_traffic_action' );

/**
 * Handle clearing data (cron status counters).
 */
function pssx_clear_data() {
    if (
        isset( $_POST['pssx_clear_data'] ) &&
        check_admin_referer( 'pssx_clear_data_action', 'pssx_clear_data_nonce' )
    ) {
        delete_option( 'pssx_last_processed_time' );
        delete_option( 'pssx_processed_ips_count' );

        wp_safe_redirect(
            esc_url_raw( admin_url( 'options-general.php?page=pss-settings' ) )
        );
        exit;
    }
}
add_action( 'admin_post_pssx_clear_data', 'pssx_clear_data' );

/**
 * Handle "Run Process" form submission.
 */
function pssx_run_process_manually() {
    if (
        isset( $_POST['pssx_run_process'] ) &&
        check_admin_referer( 'pssx_run_process_action', 'pssx_run_process_nonce' )
    ) {
        pssx_fetch_and_store_blocked_ips();
        pssx_add_ips_to_cloudflare();

        wp_safe_redirect(
            esc_url_raw( admin_url( 'options-general.php?page=pss-settings' ) )
        );
        exit;
    }
}
add_action( 'admin_post_pssx_run_process', 'pssx_run_process_manually' );

/**
 * Properly enqueue admin CSS for our settings pages.
 */
function pssx_admin_styles( $hook ) {
    // Only enqueue on our plugin settings pages.
    if ( 'settings_page_pss-settings' !== $hook ) {
        return;
    }

    // We don't have an external .css file, so register a "blank" style and add inline CSS.
    wp_register_style( 'pssx-admin-styles', false );
    wp_enqueue_style( 'pssx-admin-styles' );

    $inline_css = '
        .nav-tab.disabled {
            color: #a7aaad;
            pointer-events: none;
            cursor: default;
        }
        input[disabled], select[disabled], textarea[disabled] {
            background-color: #f1f1f1;
            color: #a7aaad;
        }
    ';
    wp_add_inline_style( 'pssx-admin-styles', $inline_css );
}
add_action( 'admin_enqueue_scripts', 'pssx_admin_styles' );

