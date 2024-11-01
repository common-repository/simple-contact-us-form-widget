<?php
/**
 * Plugin Name: Simple Contact Us Widget
 * Version: 2.2.0
 * Author: Greg Bialowas / gregbialowas.com
 * Author URI: http://www.gregbialowas.com/contact-us-widget
 * Plugin URI: https://bitbucket.org/gregorybialowas/simple-contact-us-widget/src/master/
 * Description: Simple contact form (name, email, message) to be added to sidebars or footer area (as a widget), and/or any post or page (as a shortcode).
 * License: GPLv3
 * Text Domain: gb-simple-contact-us-widget
 *
 * @package gb-simple-contact-us-widget
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GBSCUW_SIMPLE_CONTACT_US_WIDGET_MAIN_FILE', __FILE__ );
define( 'GBSCUW_SIMPLE_CONTACT_US_WIDGET_VERSION', '2.2.0' );

/**
 * Load classes
 */
require_once dirname( GBSCUW_SIMPLE_CONTACT_US_WIDGET_MAIN_FILE ) . '/classes/class-gb-simple-contact-us-widget.php';

/**
 * Load translations.
 */
function gb_cnt_wdgt__lang_load_translation() {
    load_plugin_textdomain( 'gb-simple-contact-us-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
}
add_action( 'plugins_loaded', 'gb_cnt_wdgt__lang_load_translation' );
