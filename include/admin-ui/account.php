<?php
global $GOOGLEANALYTICSCLIENT_plugin_url_path;
global $GOOGLEANALYTICSCLIENT_plugin_version;
global $GOOGLEANALYTICSCLIENT_account;
global $GOOGLEANALYTICSCLIENT_plugin_dir;
global $GOOGLEANALYTICSCLIENT_app_name;




$GOOGLEANALYTICSCLIENT_account = get_option("GOOGLEANALYTICSCLIENT_account");
switch ($_POST["action"]) {
    case "updateAccount":
        $GOOGLEANALYTICSCLIENT_account["accountName"] = $_POST["accountName"];
        $GOOGLEANALYTICSCLIENT_account["clientId"] = $_POST["clientId"];
        $GOOGLEANALYTICSCLIENT_account["clientEmail"] = $_POST["clientEmail"];
        $GOOGLEANALYTICSCLIENT_account["collectEach"] = $_POST["collectEach"];
        $GOOGLEANALYTICSCLIENT_account["keyPass"] = $_POST["keyPass"];
        // Store new uploaded key
        if (is_uploaded_file($_FILES["clientPrivateKey"]["tmp_name"])) {
            if ($error == UPLOAD_ERR_OK) {
                $tmp_name = $_FILES["clientPrivateKey"]["tmp_name"];
                $name = $_FILES["clientPrivateKey"]["name"];
                $destkeyfn = "$GOOGLEANALYTICSCLIENT_plugin_dir/keys/$name";
                if (move_uploaded_file($tmp_name, $destkeyfn)) {
                    $GOOGLEANALYTICSCLIENT_account["keyFile"] = $destkeyfn;
                    // Clean up token
                    unset($GOOGLEANALYTICSCLIENT_account["token"]);
                } else {
                    echo "<font color='RED'>" . __("ERROR: Uploading of private key failed.", "google-analytics-client") . "</font><br>";
                }
            }
        }

        if (!isset($GOOGLEANALYTICSCLIENT_account["keyFile"]) || !isset($GOOGLEANALYTICSCLIENT_account["keyFile"])) {
            echo "<font color='RED'>" . __("ERROR: You have to upload or choice private key file or passphrase.", "google-analytics-client") . "</font><br>";
        }
        if (!wp_next_scheduled('GOOGLEANALYTICSCLIENT_collect_report_data_hook')) {
            wp_schedule_event(time(), $GOOGLEANALYTICSCLIENT_account['collectEach'], 'GOOGLEANALYTICSCLIENT_collect_report_data_hook');
        }
        update_option("GOOGLEANALYTICSCLIENT_account", $GOOGLEANALYTICSCLIENT_account);
        break;
    case "unsetFile":
        unset($GOOGLEANALYTICSCLIENT_account["keyFile"]);
        update_option("GOOGLEANALYTICSCLIENT_account", $GOOGLEANALYTICSCLIENT_account);
        break;
    case "updateCode":
        $p = split(";", $_POST["code"]);
        $GOOGLEANALYTICSCLIENT_account["code"] = $p[0];
        $GOOGLEANALYTICSCLIENT_account["accountId"] = $p[1];

        update_option("GOOGLEANALYTICSCLIENT_account", $GOOGLEANALYTICSCLIENT_account);
        break;
    case "collectNow":
        GOOGLEANALYTICSCLIENT_collect_report_data_task();
        break;
    default:
}



require_once $GOOGLEANALYTICSCLIENT_plugin_dir . '/include/api/google-api-php-client/Google_Client.php';
require_once $GOOGLEANALYTICSCLIENT_plugin_dir . '/include/api/google-api-php-client/auth/Google_OAuth2.php';
require_once $GOOGLEANALYTICSCLIENT_plugin_dir . '/include/api/google-api-php-client/contrib/Google_AnalyticsService.php';


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


$errorStatus = "";

try {
    $service = new Google_AnalyticsService($client);
    $GOOGLEANALYTICSCLIENT_account["token"] = $client->getAccessToken();
    $props = $service->management_webproperties->listManagementWebproperties("~all");
    $errorStatus = "";
} catch (Exception $e) {
    update_option("GOOGLEANALYTICSCLIENT_account", $GOOGLEANALYTICSCLIENT_account);
    $errorStatus = "Enable to access API " . $e->getMessage();
    echo "<font color='RED'>" . $errorStatus . "</font><br>";
    ?>
    <script type="text/javascript">
        function flipTrace() {
            var e = document.getElementById('trace');
            if (e.style.display === 'none') {
                e.style.display = 'block';
            } else {
                e.style.display = 'none';
            }
        }
    </script>
    <?php
    echo "<a onclick='javascript:flipTrace();'>" . __("Show trace", "google-analytics-client") . "</a>";
    echo "<div id='trace' style='display:none;'>";
    echo "<pre>";
    echo $e->getTraceAsString();
    echo "</pre>";
    echo "</div>";
}
?>

<h2><img width="32" height="32" src="<?php print $GOOGLEANALYTICSCLIENT_plugin_url_path . "/images/google-analytics-client-64x64.png"; ?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php print __("Google Analytics Client", "google-analytics-client") . " " . $GOOGLEANALYTICSCLIENT_plugin_version; ?> </h2>
<h3><?php _e("Account settings", "google-analytics-client"); ?> </h3>
<hr>
<div style="background-color: lightsteelblue;border-left: 1px appworkspace solid;border-right: 1px appworkspace solid;border-top: 1px appworkspace solid;width: 100px;"><b>&nbsp;&nbsp;<?php _e("Info", "google-analytics-client"); ?></b></div>
<div style="background-color: lightsteelblue;border: 1px appworkspace solid;width: 600px;">
    <?php _e("To get Credentials like Client ID, Client Email and private key you should visit Google Analytics API from", "google-analytics-client"); ?>&nbsp;<a href="https://code.google.com/apis/console">https://code.google.com/apis/console</a> <br>
    <?php _e("In Api Access create new or use existing service account.", "google-analytics-client"); ?> <br>
    <?php _e("Add your google service account email address to supported profiles as new user with minimal role User.", "google-analytics-client"); ?> <br>
    <?php _e("Or choice existing account to connect with this profile what is recomended.", "google-analytics-client"); ?> <br> 
    <?php _e("You have to wait few minutes to synchonizing account status.", "google-analytics-client"); ?> <br>
    <?php _e("Select your site profile and start collecting data.", "google-analytics-client"); ?> <br>
    <a href="https://www.google.com/analytics/web/#management/Profile">https://www.google.com/analytics/web/#management/Profile</a>
</div>
<form
    method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="updateAccount">
    <table>
        <tr>
            <td>
                <label for="accountName"><?php _e("Account name", "google-analytics-client"); ?> </label>
            </td>
            <td>
                <input id="accountName" name="accountName" value="<?php echo $GOOGLEANALYTICSCLIENT_account["accountName"]; ?>" />
            </td>
        </tr>
        <tr>
            <td>
                <label for="clientId"><?php _e("Client Id", "google-analytics-client"); ?> </label>
            </td>
            <td>
                <input id="clientId" name="clientId" value="<?php echo $GOOGLEANALYTICSCLIENT_account["clientId"]; ?>" />
            </td>
        </tr>
        <tr>
            <td>
                <label for="clientEmail"><?php _e("Email address", "google-analytics-client"); ?> </label>
            </td>
            <td>
                <input id="clientEmail" name="clientEmail" value="<?php echo $GOOGLEANALYTICSCLIENT_account["clientEmail"]; ?>" />
            </td>
        </tr>
        <tr>
            <td>
                <label for="clientPrivateKey"><?php _e("Private key file", "google-analytics-client"); ?> </label>
            </td>
            <td>
                <?php
                if (isset($GOOGLEANALYTICSCLIENT_account["keyFile"]) && file_exists($GOOGLEANALYTICSCLIENT_account["keyFile"]) && basename($GOOGLEANALYTICSCLIENT_account["keyFile"]) != "keys") {
                    echo basename($GOOGLEANALYTICSCLIENT_account["keyFile"]);
                }
                ?>
                <input id="clientPrivateKey" name="clientPrivateKey" type="file" />
            </td>
        </tr>      
        <tr>
            <td>
                <label for="keyPass"><?php _e("Passphrase", "google-analytics-client"); ?> </label>
            </td>
            <td>
                <input id="keyPass" name="keyPass" type="password" value="<?php echo $GOOGLEANALYTICSCLIENT_account["keyPass"]; ?>"/>
            </td>
        </tr>          
        <tr>
            <td>
                <label for="collectEach"><?php _e("Collect each", "google-analytics-client"); ?> </label>
            </td>
            <td>
                <select id="collectEach" name="collectEach">
                    <?php
                    $chedules = wp_get_schedules();
                    foreach ($chedules as $skey => $schedule) {
                        echo "<option value=\"$skey\">" . __($schedule['display'], "google-analytics-client") . "</option>";
                    }
                    ?>
                </select>
            </td>
        </tr>          
    </table>     
    <input type="submit" value="<?php _e("Update account", "google-analytics-client"); ?>">   
</form>
<?php
if ($client->getAccessToken() && $errorStatus == "") {
    ?>
    <hr>
    <form method="POST">
        <input type="hidden" name="action" value="updateCode">
        <label for="code"><?php _e("Profile from Google Analytics Account", "google-analytics-client"); ?> </label>    
        <select name="code" id="code">
            <?php
            foreach ($props["items"] as $prop) {
                ?>
                <option  value="<?php echo $prop["id"] . ";" . $prop["accountId"]; ?>" <?php if ($GOOGLEANALYTICSCLIENT_account["code"] == $prop["id"]) echo "selected"; ?>><?php echo $prop["name"]; ?> (<?php echo $prop["websiteUrl"]; ?>) </option>
                <?php
            }
            ?>
        </select>
        <input type="submit" value="<?php _e("Update code", "google-analytics-client"); ?>">
    </form>
    <hr>
    <form method="POST">
        <input type="hidden" name="action" value="collectNow">
        <input type="submit" value="<?php _e("Collect data now", "google-analytics-client"); ?>">
    </form>

    <?php
}
?>