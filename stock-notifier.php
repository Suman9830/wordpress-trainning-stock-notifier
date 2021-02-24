<?php
/**
 * Stock Notifier
 *
 * @package StockNotifier
 */
/**
 * Plugin Name: Stock Notifier
 * Plugin URI:
 * Description: Stock Notifier Helps User Uptodated.
 * Author: Suman Debnath
 * Version: 1.0.1
 * Author URI: https://www.stocknotifier.com
 */

use StockNotifier\Managers\DataBaseManager;
use StockNotifier\StockNotifier;

function wc_woo_stock_notifier_loader(){

    global $wpWooStockNotifier;

    if($wpWooStockNotifier != null){
        return true;
    }

    define('SN_WP_PLUGIN_DIR', __DIR__ . '/');
    define('SN_WP_PLUGIN_URL', plugin_dir_url(__FILE__) . '/');
    define('SN_WP_PLUGIN_FILE', __FILE__);
    define('SN_WP_PLUGIN_VERSION', '1.0.1');
    define('SN_WP_PLUGIN_SMTP_SERVER', '1.0.1');
    define('SN_WP_PLUGIN_SMTP_SERVER_PORT', '1.0.1');
    define('SN_WP_PLUGIN_SMTP_EMAIL', '1.0.1');
    define('SN_WP_PLUGIN_SMTP_EMAIL_PASSWORD', '1.0.1');

    // Load all the required files
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        include_once __DIR__ . '/vendor/autoload.php';
    }

    $wpWooStockNotifier = StockNotifier::get_instance();
    $wpWooStockNotifier->add_hook();

    return true;
}

function activate_sn_plugin(){
    add_option('sn_plugin_is_activated', true);
}

function deactivate_sn_plugin(){
    delete_option('sn_plugin_is_activated');
    delete_option(DataBaseManager::DB_VERSION_OPTION_CHECKER);
    delete_option(DataBaseManager::DB_VERSION);
}

add_action('plugins_loaded', 'wc_woo_stock_notifier_loader', 10);
register_activation_hook(__FILE__, 'activate_sn_plugin');
register_deactivation_hook(__FILE__, 'deactivate_sn_plugin');