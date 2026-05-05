<?php
/**
 * Plugin Name: PS Decompose Word
 * Description: Improves wrapping of long compound words with native hyphenation and optional render-time soft hyphen exceptions.
 * Version: 0.1.9
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * Author: Per Soderlind
 * Text Domain: ps-decompose-word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PS_DECOMPOSE_WORD_VERSION', '0.1.9' );
define( 'PS_DECOMPOSE_WORD_FILE', __FILE__ );
define( 'PS_DECOMPOSE_WORD_PATH', plugin_dir_path( __FILE__ ) );
define( 'PS_DECOMPOSE_WORD_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( PS_DECOMPOSE_WORD_PATH . 'vendor/autoload.php' ) ) {
	require_once PS_DECOMPOSE_WORD_PATH . 'vendor/autoload.php';
}

require_once PS_DECOMPOSE_WORD_PATH . 'includes/class-settings.php';
require_once PS_DECOMPOSE_WORD_PATH . 'includes/class-hyphenator.php';
require_once PS_DECOMPOSE_WORD_PATH . 'includes/class-html-processor.php';
require_once PS_DECOMPOSE_WORD_PATH . 'includes/class-render-filter.php';
require_once PS_DECOMPOSE_WORD_PATH . 'includes/class-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		\PS_Decompose_Word\Plugin::instance()->register();
	}
);

// GitHub Updater.
use Soderlind\WordPress\GitHubUpdater;

if ( class_exists( GitHubUpdater::class ) ) {
	GitHubUpdater::init(
		github_url:   'https://github.com/soderlind/ps-decompose-word',
		plugin_file:  __FILE__,
		plugin_slug:  'ps-decompose-word',
		name_regex:   '/ps-decompose-word\.zip/',
		branch:       'main',
		check_period: 6,
	);
}