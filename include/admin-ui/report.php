<?php
global $GOOGLEANALYTICSCLIENT_plugin_url_path;
global $GOOGLEANALYTICSCLIENT_plugin_version;
global $GOOGLEANALYTICSCLIENT_account;
global $GOOGLEANALYTICSCLIENT_plugin_dir;
?>
<h2><img width="32" height="32" src="<?php print $GOOGLEANALYTICSCLIENT_plugin_url_path . "/images/google-analytics-client-64x64.png"; ?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php print __("Google Analytics Client", "google-analytics-client") . " " . $GOOGLEANALYTICSCLIENT_plugin_version; ?></h2>
<h3><?php _e("Report", "google-analytics-client"); ?></h3>
<hr>
<p>

    <?php
    foreach ($GOOGLEANALYTICSCLIENT_account["profile"] as $data) {
        echo __("Collected: ", "google-analytics-client") . $data["collected"] . "<br>";
        echo __("Today: ", "google-analytics-client") . $data["visits.today"] . "<br>";
        echo __("This week: ", "google-analytics-client") . $data["visits.week"] . "<br>";
        echo __("This month: ", "google-analytics-client") . $data["visits.month"] . "<br>";
        echo __("Total visits: ", "google-analytics-client") . $data["visits.all"] . "<br>";
        echo "<hr>";
    }
    ?>
</p>
