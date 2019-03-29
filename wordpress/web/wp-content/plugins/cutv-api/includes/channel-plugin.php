<?php

// Load plugin class files
require_once('channel/class-wordpress-plugin-template.php');
require_once('channel/class-wordpress-plugin-template-settings.php');

// Load plugin libraries
require_once('channel/lib/class-wordpress-plugin-template-admin-api.php');
require_once('channel/lib/class-wordpress-plugin-template-post-type.php');
require_once('channel/lib/class-wordpress-plugin-template-taxonomy.php');


/**
 * Returns the main instance of CUTV_Channel to prevent the need to use globals.
 *
 * @since  0.0.1
 * @return object CUTV_Channel
 */
function CUTV_Channel()
{
    $instance = CUTV_Channel::instance(__FILE__, '1.0.0');

    if (is_null($instance->settings)) {
        $instance->settings = CUTV_Channel_Settings::instance($instance);
    }

    return $instance;
}

CUTV_Channel();

