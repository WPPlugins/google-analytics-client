<?php
/*
  Widget Name: Google Analytics Report Widget
  Plugin URI: http://www.devtech.cz/
  Description: Help users to put analytics data stats as a widget.
  Version: 1.0.9
  License: GNU v3 \
  This program comes with ABSOLUTELY NO WARRANTY. \
  This is free software, and you are welcome to redistribute it \
  under certain conditions; type `show c' for details. \
  Author: Copyright (C) <2012> Juraj Puchký
  Author URI: http://www.devtech.cz/
 */

class GOOGLEANALYTICSREPORT_Widget extends WP_Widget {

    /** constructor */
    function GOOGLEANALYTICSREPORT_Widget() {
        parent::WP_Widget('', __('Google Analytics Report', 'google-analytics-client'), array('description' => __('Help users to put analytics data stats as a widget.', 'google-analytics-client')), array('width' => 400));
    }

    /** Backwards compatibility for AdsContent_Widget::display(); usage */
    function display($args = false) {
        self::widget($args, NULL);
    }

    /** @see WP_Widget::widget */
    function widget($args = array(), $instance) {
        global $GOOGLEANALYTICSCLIENT_account;

        $defaults = array(
            'before_widget' => '',
            'after_widget' => '',
            'before_title' => '',
            'after_title' => '',
        );

        $args = wp_parse_args($args, $defaults);
        extract($args);

        $title = (isset($instance) && isset($instance['title'])) ? esc_attr($instance['title']) : '';
        $poweredby = (isset($instance) && isset($instance['poweredby'])) ? esc_attr($instance['poweredby']) : '';

        echo $before_widget;

        if ($title) {
            echo $before_title . $title . $after_title;
        }

        // Dynamic content of widget
        ?>
        <p>
            <?php
            foreach ($GOOGLEANALYTICSCLIENT_account["profile"] as $data) {
                echo __("Today: ", "google-analytics-client") . $data["visits.today"] . "<br>";
                echo __("This week: ", "google-analytics-client") . $data["visits.week"] . "<br>";
                echo __("This month: ", "google-analytics-client") . $data["visits.month"] . "<br>";
                echo __("Total visits: ", "google-analytics-client") . $data["visits.all"] . "<br>";
            }
            ?>
        </p>
        <?php
        //
        // Devtech ad
        if($poweredby) {
        ?>
        <div style="text-align:right;"><a href="http://www.devtech.cz/" style="font-size:5px;font-style: none;color: #555588" title="preshashop plugin,wordpress plugin,vpn,b2b,eshop,blog,annonce,link,seo,proxy,mailing,affiliate">Devtech</a></div>
        <?php
        //
        }

        echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['poweredby'] = esc_attr($new_instance['poweredby']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        $title = (isset($instance) && isset($instance['title'])) ? esc_attr($instance['title']) : '';
        $poweredby = (isset($instance) && isset($instance['poweredby'])) ? esc_attr($instance['poweredby']) : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'google-analytics-client'); ?></label> 
            <input class="widefat" 
                   id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" 
                   type="text" 
                   value="<?php echo $title; ?>" />
        </p>        
        <p>
            <input id="<?php echo $this->get_field_id('poweredby'); ?>" 
                   name="<?php echo $this->get_field_name('poweredby'); ?>" 
                   type="checkbox" 
                   <?php
                   if ($poweredby) {
                       echo "checked ";
                   }
                   ?>
                   value="poweredby" />
            <label for="<?php echo $this->get_field_id('poweredby'); ?>"><?php _e('You can promote us with small link on bottom of ad.', 'google-analytics-client'); ?></label>
        </p>
        <br style="clear: both;">
        <?php
    }

}
?>
