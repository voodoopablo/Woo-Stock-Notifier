<?php
/*
Plugin Name: Stock Notifier 
Description: Gets Email, Manage and Notifies customers when a WooCommerce product is back in stock for Woocommerce.
Version: 1.0
Author: voodoopablo
Author URI: https://github.com/voodoopablo
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) exit;

define('WSN_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once WSN_PLUGIN_DIR . 'includes/frontend.php';
require_once WSN_PLUGIN_DIR . 'includes/backend.php';
require_once WSN_PLUGIN_DIR . 'includes/email-handler.php';


