<?php


use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Otomaties\VisualRentingDynamicsSync\Api;
use Otomaties\VisualRentingDynamicsSync\Plugin;
use Otomaties\VisualRentingDynamicsSync\Admin\Settings;

/*
 * Plugin Name:       Visual Renting Dynamics Sync
 * Description:       Syncs Visual Renting Dynamics with WooCommerce
 * Version:           1.0
 * Author:            Tom Broucke
 * Author URI:        https://tombroucke.be
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       visual-renting-dynamics-sync
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

/**
 * Get main plugin class instance
 *
 * @return Plugin
 */
function visualRentingDynamicSync()
{
    static $plugin;

    if (!$plugin) {
        $plugin = new Plugin();
        $plugin->runHooks();
        
        do_action("visual_renting_dynamics_sync", $plugin);
    }

    return $plugin;
}

/**
 * Bind implementations to the container
 */
add_action('visual_renting_dynamics_sync', function ($plugin) {
    $plugin->singleton(Settings::class, function ($plugin, $args) {
        return new Settings();
    });

    $plugin->singleton(Logger::class, function () {
        $log = new Logger('visual-renting-dynamics-sync');
        $path = wp_upload_dir()['basedir'] . '/visual-renting-dynamics-sync.log';
        $logLevel = isset($_SERVER['WP_ENV']) && $_SERVER['WP_ENV'] === 'development' ? Logger::DEBUG : Logger::INFO;
        $log->pushHandler(new StreamHandler($path, $logLevel));
        return $log;
    });

    $plugin->singleton(Api::class, function ($plugin, $args) {
        $apiKey = $plugin->make(Settings::class)->get('api_key');
        $logger = $plugin->make(Logger::class);
        return new Api($apiKey, $logger);
    });
});

visualRentingDynamicSync(); // Run the main plugin functionality
