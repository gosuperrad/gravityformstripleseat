<?php

/*
Plugin Name: Gravity Forms Tripleseat Add-On
Plugin URI: https://github.com/mondaynightbrewing/gravityformstripleseat
Description: Integrates Gravity Forms with Tripleseat, sending form submissions to the Tripleseat Lead API. Supports per-form feeds, field mapping, and UTM/GCLID campaign tracking.
Version: 1.0.0
Author: Super Rad
Author URI: https://gosuperrad.com/
Text Domain: gravityformstripleseat
Requires Plugins: gravityforms
License: GPL-2.0-or-later
*/

if (! defined('ABSPATH')) {
    exit;
}

define('GF_TRIPLESEAT_VERSION', '1.0.0');
define('GF_TRIPLESEAT_URL', plugin_dir_url(__FILE__));

add_action('gform_loaded', ['GF_Tripleseat_Bootstrap', 'load'], 5);

class GF_Tripleseat_Bootstrap
{
    public static function load()
    {
        if (! method_exists('GFForms', 'include_feed_addon_framework')) {
            return;
        }

        require_once __DIR__ . '/includes/class-gf-tripleseat-utm.php';
        require_once __DIR__ . '/includes/class-gf-tripleseat-date.php';
        require_once __DIR__ . '/class-gf-tripleseat.php';

        GFAddOn::register('GF_Tripleseat');

        GF_Tripleseat_UTM::get_instance()->init();
        GF_Tripleseat_Date::get_instance()->init();
    }
}

/**
 * Convenience accessor for the add-on instance.
 *
 * @return GF_Tripleseat
 */
function gf_tripleseat()
{
    return GF_Tripleseat::get_instance();
}
