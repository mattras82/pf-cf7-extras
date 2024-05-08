<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              github.com/mattras82
 * @since             1.0.0
 * @package           PF_Cf7_Extras
 *
 * @wordpress-plugin
 * Plugin Name:       PF CF7 Extras
 * Plugin URI:        https://github.com/mattras82/pf-cf7-extras
 * Description:       This plugin adds validation, special email tags,  IP [user_ip], Referrer URL [user_referrer], User PPC [user_ppc], and Visited Path [user_path] shortcodes for WP CF7 mails.
 * Version:           1.0.8
 * Author:            Public Function
 * Author URI:        https://github.com/mattas82
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       pf-cf7-extras
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/mattras82/pf-cf7-extras
 * GitHub Branch:     master
 */

spl_autoload_register(function($class) {
	$prefix = 'PublicFunction\\Cf7Extras\\';

	$len = strlen($prefix);
	if (strncmp($prefix, $class, $len) !== 0)
		return;

	$d = DIRECTORY_SEPARATOR;
	$base = __DIR__ . "{$d}lib{$d}";
	$relative_class = substr($class, $len);
	$file = $base . str_replace('\\', $d, $relative_class) . '.php';

	if (file_exists($file)) {
		require $file;
	}

	return;
});

/**
 * This plugin requires at least 5.5.12
 */
if(!version_compare('5.5.12', phpversion(), '<=')) {
	\PublicFunction\Cf7Extras\Plugin::stop(
		sprintf(__( 'You must be using PHP 5.5.12 or greater, currently running %s' ), phpversion()),
		__('Invalid PHP Version', 'pf-cf7-extras')
	);
}


/**
 * Returns an instance of this plugin
 * @param null|string $name
 * @param null|string|callable $value
 * @return \PublicFunction\Cf7Extras\Plugin|mixed
 */
function pf_cf7_extras( $name = null, $value = null ) {
	$instance = \PublicFunction\Cf7Extras\Plugin::getInstance();
	$container = $instance->container();

	if( !empty($value) )
		return $container->set($name, $value);

	if( !empty($name) ) {
		return $container->get($name);
	}

	return $instance;
}

/**
 * Starts the plugin
 */
function pf_cf7_extras_start() {
	\PublicFunction\Cf7Extras\Plugin::start();
}

add_action('plugins_loaded', 'pf_cf7_extras_start');