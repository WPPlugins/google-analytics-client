<?php
/*
  Plugin Name: Google Analytics Client
  Plugin URI: http://www.devtech.cz/
  Version: 1.0.2
  Description: Helps users to add google analytics code and collect reporting data also show stats as widget.
  License: GNU v3 \
  This program comes with ABSOLUTELY NO WARRANTY. \
  This is free software, and you are welcome to redistribute it \
  under certain conditions; type `show c' for details. \
  Author: Copyright (C) <2012> Juraj Puchký
  Author URI: http://www.devtech.cz/

 */
global $GOOGLEANALYTICSCLIENT_plugin_url_path;

if (!isset($GOOGLEANALYTICSCLIENT_locale))
    $GOOGLEANALYTICSCLIENT_locale = '';

// Pre-2.6 compatibility
if (!defined('WP_CONTENT_URL'))
    define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if (!defined('WP_PLUGIN_URL'))
    define('WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins');

$GOOGLEANALYTICSCLIENT_plugin_basename = plugin_basename(dirname(__FILE__));


if (basename(dirname(__FILE__)) == "mu-plugins") {
    $GOOGLEANALYTICSCLIENT_plugin_url_path = WPMU_PLUGIN_URL . '/google-analytics-client';
    $GOOGLEANALYTICSCLIENT_plugin_dir = WPMU_PLUGIN_DIR . '/google-analytics-client';
} else {
    $GOOGLEANALYTICSCLIENT_plugin_url_path = WP_PLUGIN_URL . '/' . $GOOGLEANALYTICSCLIENT_plugin_basename;
    $GOOGLEANALYTICSCLIENT_plugin_dir = WP_PLUGIN_DIR . '/' . $GOOGLEANALYTICSCLIENT_plugin_basename;
}


load_plugin_textdomain('google-analytics-client', false, $GOOGLEANALYTICSCLIENT_plugin_basename . '/languages');

// Fix SSL
if (is_ssl())
    $GOOGLEANALYTICSCLIENT_plugin_url_path = str_replace('http:', 'https:', $GOOGLEANALYTICSCLIENT_plugin_url_path);


global $GOOGLEANALYTICSCLIENT_account;
global $GOOGLEANALYTICSCLIENT_plugin_version;
global $GOOGLEANALYTICSCLIENT_app_name;


$GOOGLEANALYTICSCLIENT_app_name = $GOOGLEANALYTICSCLIENT_app_name;
$GOOGLEANALYTICSCLIENT_plugin_version = '1.0.0';
$GOOGLEANALYTICSCLIENT_account = get_option("GOOGLEANALYTICSCLIENT_account");

require_once $GOOGLEANALYTICSCLIENT_plugin_dir . '/include/api/google-api-php-client/Google_Client.php';
require_once $GOOGLEANALYTICSCLIENT_plugin_dir . '/include/api/google-api-php-client/auth/Google_OAuth2.php';
require_once $GOOGLEANALYTICSCLIENT_plugin_dir . '/include/api/google-api-php-client/contrib/Google_AnalyticsService.php';

function GOOGLEANALYTICSCLIENT_account() {
    global $GOOGLEANALYTICSCLIENT_plugin_dir;

    require_once($GOOGLEANALYTICSCLIENT_plugin_dir . '/include/admin-ui/account.php');
}

function GOOGLEANALYTICSCLIENT_report() {
    global $GOOGLEANALYTICSCLIENT_plugin_dir;

    require_once($GOOGLEANALYTICSCLIENT_plugin_dir . '/include/admin-ui/report.php');
}

function GOOGLEANALYTICSCLIENT_dashboard() {
    global $GOOGLEANALYTICSCLIENT_plugin_dir;

    require_once($GOOGLEANALYTICSCLIENT_plugin_dir . '/include/admin-ui/dashboard.php');
}

function GOOGLEANALYTICSCLIENT_create_menu() {
    global $GOOGLEANALYTICSCLIENT_plugin_url_path;

    add_menu_page(
            __('GA Client', "google-analytics-client"), __('GA Client', "google-analytics-client"), 'manage_options', 'GOOGLEANALYTICSCLIENT_dashboard', 'GOOGLEANALYTICSCLIENT_dashboard', $GOOGLEANALYTICSCLIENT_plugin_url_path . '/images/google-analytics-client-16x16.png');

    add_submenu_page(
            'GOOGLEANALYTICSCLIENT_dashboard', __("Account", "google-analytics-client"), __("Account", "google-analytics-client"), 'manage_options', 'GOOGLEANALYTICSCLIENT_account', 'GOOGLEANALYTICSCLIENT_account'
    );

    add_submenu_page(
            'GOOGLEANALYTICSCLIENT_dashboard', __("Report", "google-analytics-client"), __("Report", "google-analytics-client"), 'manage_options', 'GOOGLEANALYTICSCLIENT_report', 'GOOGLEANALYTICSCLIENT_report'
    );

    global $submenu;
    if (isset($submenu['GOOGLEANALYTICSCLIENT_dashboard']))
        $submenu['GOOGLEANALYTICSCLIENT_dashboard'][0][0] = __('Dashboard', 'google-analytics-client');
}

function GOOGLEANALYTICSCLIENT_widget_init() {
    global $GOOGLEANALYTICSCLIENT_plugin_dir;

    include_once($GOOGLEANALYTICSCLIENT_plugin_dir . '/include/widget/google-analytics-reportWidget.php');
    register_widget('GOOGLEANALYTICSREPORT_Widget');
}

function GOOGLEANALYTICSCLIENT_collect_report_data_task() {
    global $GOOGLEANALYTICSCLIENT_account;


// Reschedule hook after collecting datas

    if (!wp_next_scheduled('GOOGLEANALYTICSCLIENT_collect_report_data_hook')) {
        wp_schedule_event(time(), $GOOGLEANALYTICSCLIENT_account['collectEach'], 'GOOGLEANALYTICSCLIENT_collect_report_data_hook');
    }

    $GOOGLEANALYTICSCLIENT_account = get_option("GOOGLEANALYTICSCLIENT_account");

    $optParams = array('metrics' => 'ga:visits', 'max-results' => '25');

    if (isset($GOOGLEANALYTICSCLIENT_account["accountId"]) && isset($GOOGLEANALYTICSCLIENT_account["clientEmail"]) && isset($GOOGLEANALYTICSCLIENT_account["clientId"]) && isset($GOOGLEANALYTICSCLIENT_account["keyFile"]) && isset($GOOGLEANALYTICSCLIENT_account["keyPass"])) {

        try {
            error_log("GOOGLEANALYTICSCLIENT: Collecting data.");

            $client = new Google_Client();
            $client->setApplicationName($GOOGLEANALYTICSCLIENT_account["accountName"]);

            $client->setAssertionCredentials(
                    new Google_AssertionCredentials(
                            $GOOGLEANALYTICSCLIENT_account["clientEmail"],
                            array('https://www.googleapis.com/auth/analytics.readonly'),
                            file_get_contents($GOOGLEANALYTICSCLIENT_account["keyFile"]),
                            $GOOGLEANALYTICSCLIENT_account["keyPass"]
            ));

            // other settings
            $client->setClientId($GOOGLEANALYTICSCLIENT_account["clientId"]);
            $client->setAccessType('offline');

            $service = new Google_AnalyticsService($client);
            $profiles = $service->management_profiles->listManagementProfiles($GOOGLEANALYTICSCLIENT_account["accountId"], $GOOGLEANALYTICSCLIENT_account["code"]);
            foreach ($profiles["items"] as $profile) {
                error_log($profile["id"] . ":" . $profile["name"]);
                $GOOGLEANALYTICSCLIENT_account["profile"][$profile["id"]]["collected"] = date("Y-m-d h:i:s", time());
                $data1 = $service->data_ga->get('ga:' . $profile["id"], date("Y-m-d"), date("Y-m-d"), 'ga:visits', $optParams);
                $GOOGLEANALYTICSCLIENT_account["profile"][$profile["id"]]["visits.today"] = $data1["totalsForAllResults"]["ga:visits"];
                $data2 = $service->data_ga->get('ga:' . $profile["id"], date("Y-m-d", strtotime("last Monday")), date("Y-m-d"), 'ga:visits', $optParams);
                $GOOGLEANALYTICSCLIENT_account["profile"][$profile["id"]]["visits.week"] = $data2["totalsForAllResults"]["ga:visits"];
                $data3 = $service->data_ga->get('ga:' . $profile["id"], date("Y-m-01"), date("Y-m-d"), 'ga:visits', $optParams);
                $GOOGLEANALYTICSCLIENT_account["profile"][$profile["id"]]["visits.month"] = $data3["totalsForAllResults"]["ga:visits"];
                $data4 = $service->data_ga->get('ga:' . $profile["id"], '2005-01-01', date("Y-m-d"), 'ga:visits', $optParams);
                $GOOGLEANALYTICSCLIENT_account["profile"][$profile["id"]]["visits.all"] = $data4["totalsForAllResults"]["ga:visits"];
                error_log($profile["name"] . ":visits.today:" . $GOOGLEANALYTICSCLIENT_account["profile"][$profile["id"]]["visits.today"]);
                error_log($profile["name"] . ":visits.week:" . $GOOGLEANALYTICSCLIENT_account["profile"][$profile["id"]]["visits.week"]);
                error_log($profile["name"] . ":visits.month:" . $GOOGLEANALYTICSCLIENT_account["profile"][$profile["id"]]["visits.month"]);
                error_log($profile["name"] . ":visits.all:" . $GOOGLEANALYTICSCLIENT_account["profile"][$profile["id"]]["visits.all"]);
            }
            update_option("GOOGLEANALYTICSCLIENT_account", $GOOGLEANALYTICSCLIENT_account);
            echo "<font color='blue'>INFO: Stats was succesfully downloaded.</font>";
        } catch (Exception $e) {
            error_log("GOOGLEANALYTICSCLIENT: Was failed with authorization you should configure account settings properly.");
            echo "<font color='red'>ERROR: Was failed with authorization you should configure account settings properly.</font>";
        }
    } else {
        error_log("GOOGLEANALYTICSCLIENT: API was not authorized or profile was not selected yet.");
        echo "<font color='red'>ERROR: API was not authorized or profile was not selected yet</font>";
    }
}

function GOOGLEANALYTICSCLIENT_add_code_action() {
    global $GOOGLEANALYTICSCLIENT_account;
    if (isset($GOOGLEANALYTICSCLIENT_account["code"])) {
        ?>

        <script type="text/javascript">

            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', '<?php echo $GOOGLEANALYTICSCLIENT_account["code"]; ?>']);
            _gaq.push(['_trackPageview']);

            (function() {
                var ga = document.createElement('script');
                ga.type = 'text/javascript';
                ga.async = true;
                ga.src = ('https:' === document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(ga, s);
            })();

        </script>    
        <?php
    }
}

function GOOGLEANALYTICSCLIENT_init() {
    global $GOOGLEANALYTICSCLIENT_account;

// Reschedule hook 
    if (!wp_next_scheduled('GOOGLEANALYTICSCLIENT_collect_report_data_hook')) {
        wp_schedule_event(time(), $GOOGLEANALYTICSCLIENT_account['collectEach'], 'GOOGLEANALYTICSCLIENT_collect_report_data_hook');
    }

    add_action('wp_head', 'GOOGLEANALYTICSCLIENT_add_code_action');

// create custom plugin settings menu
    add_action('admin_menu', 'GOOGLEANALYTICSCLIENT_create_menu');
}

add_action('GOOGLEANALYTICSCLIENT_collect_report_data_hook', 'GOOGLEANALYTICSCLIENT_collect_report_data_task');
add_action('init', 'GOOGLEANALYTICSCLIENT_init');

// Register widgets    
add_action('widgets_init', 'GOOGLEANALYTICSCLIENT_widget_init');
?>