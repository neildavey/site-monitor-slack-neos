<?php
/**
 * Plugin Name: Site Monitor Slack NEOS
 * Description: Sends a Slack to RANE team when the site is down.
 * Version: 2.2
 * Author: NEO
 */



 function site_monitor_slack_send_message($message) {
    $slack_webhook_url = get_option('site_monitor_slack_webhook_url');

    $payload = array(
        'text' => $message
    );

    wp_remote_post($slack_webhook_url, array(
        'body' => json_encode($payload),
        'headers' => array(
            'Content-Type' => 'application/json'
        )
    ));
}

function site_monitor_slack_check_site() {
    $site_url = get_site_url();
    $response = wp_remote_get($site_url);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200 || !site_monitor_slack_check_database()) {
        $error_message = 'The site ' . $site_url . ' seems to be down. Please check it!';
        site_monitor_slack_send_message($error_message);
    }
}

function site_monitor_slack_check_database() {
    global $wpdb;
    return $wpdb->check_connection(false);
}

function site_monitor_slack_schedule_check() {
    if (!wp_next_scheduled('site_monitor_slack_cron_hook')) {
        wp_schedule_event(time(), 'hourly', 'site_monitor_slack_cron_hook');
    }
}
add_action('wp', 'site_monitor_slack_schedule_check');

add_action('site_monitor_slack_cron_hook', 'site_monitor_slack_check_site');

// Create a new settings field
function site_monitor_slack_register_settings() {
    register_setting('site_monitor_slack_options_group', 'site_monitor_slack_webhook_url');
}
add_action('admin_init', 'site_monitor_slack_register_settings');

// Add a settings page for the plugin
function site_monitor_slack_create_settings_page() {
    add_options_page('Site Monitor Slack', 'Site Monitor Slack', 'manage_options', 'site_monitor_slack', 'site_monitor_slack_settings_page');
}
add_action('admin_menu', 'site_monitor_slack_create_settings_page');

// Create the settings page content
function site_monitor_slack_settings_page() {
    ?>
    <div class="wrap">
        <h1>Site Monitor Slack</h1>
        <form method="post" action="options.php">
            <?php settings_fields('site_monitor_slack_options_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Slack Webhook URL</th>
                    <td>
                        <input type="text" name="site_monitor_slack_webhook_url" value="<?php echo esc_attr(get_option('site_monitor_slack_webhook_url')); ?>" size="80" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

