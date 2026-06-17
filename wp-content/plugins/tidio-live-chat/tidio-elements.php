<?php

if (!defined('WPINC')) {
    die('File loaded directly. Exiting.');
}

/**
 * Plugin Name: Tidio Chat
 * Plugin URI: http://www.tidio.com
 * Description: Tidio Live Chat - live chat boosted with chatbots for your online business. Integrates with your website in less than 20 seconds.
 * Version: 7.0.0
 * Requires at least: 4.7
 * Requires PHP: 7.2
 * Author: Tidio LLC
 * Author URI: http://www.tidio.com
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tidio-live-chat
 * Domain Path: /languages/
 * Update URI: https://wordpress.org/plugins/tidio-live-chat/
 */

define('TIDIOCHAT_VERSION', '7.0.0');
define('AFFILIATE_CONFIG_FILE_PATH', get_template_directory() . '/tidio_affiliate_ref_id.txt');

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use TidioLiveChat\IntegrationState;
use TidioLiveChat\Routing;
use TidioLiveChat\TidioLiveChat;

$container = new \TidioLiveChat\Container();

// Load plugin data
add_action('init', static function () use ($container) {
    if (!empty($_GET['tidio_chat_version'])) {
        echo TIDIOCHAT_VERSION;
        exit;
    }

    $tidioLiveChat = new TidioLiveChat($container);
    $tidioLiveChat->load();
});

// Turn on async loading
register_activation_hook(__FILE__, static function () use ($container) {
    /** @var IntegrationState $integrationState */
    $integrationState = $container->get(IntegrationState::class);
    $integrationState->turnOnAsyncLoading();
});

// Redirect to tidio plugin page after activation
add_action('activated_plugin', static function ($plugin)  {
    if ($plugin === plugin_basename(__FILE__)) {
        exit(wp_safe_redirect(admin_url('admin.php?page=tidio-live-chat')));
    }
});
