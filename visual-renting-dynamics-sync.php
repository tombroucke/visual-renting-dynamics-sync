<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Otomaties\VisualRentingDynamicsSync\Api;
use Otomaties\VisualRentingDynamicsSync\Plugin;
use Otomaties\VisualRentingDynamicsSync\Helpers\View;
use Otomaties\VisualRentingDynamicsSync\Admin\Settings;
use Otomaties\VisualRentingDynamicsSync\Helpers\Assets;

/*
 * Plugin Name:       Visual Renting Dynamics Sync
 * Description:       Syncs Visual Renting Dynamics with WooCommerce
 * Version:           1.0.0
 * Author:            Tom Broucke
 * Author URI:        https://tombroucke.be
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       visual-renting-dynamics-sync
 * Domain Path:       /resources/languages
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
        $apiKey = $plugin->make(Settings::class)->get('api_key') ?? '';
        $logger = $plugin->make(Logger::class);
        $client = $plugin->make(\GuzzleHttp\Client::class);
        return new Api($apiKey, $client, $logger);
    });

    $plugin->singleton(View::class, function ($plugin, $args) {
        $path = plugin_dir_path(__FILE__) . 'resources/views/';
        return new View($path);
    });

    $plugin->singleton(Assets::class, function ($plugin, $args) {
        $path = plugin_dir_path(__FILE__);
        return new Assets($path);
    });

    $plugin->singleton('custom-checkout-fields', function ($plugin, $args) {
        return [
            'vrd_shipping_method' => __('Shipping method', 'visual-renting-dynamics-sync'),
            'vrd_shipping_date' => __('Shipping date', 'visual-renting-dynamics-sync'),
            'vrd_return_date' => __('Return date', 'visual-renting-dynamics-sync'),
        ];
    });
});

visualRentingDynamicSync(); // Run the main plugin functionality
