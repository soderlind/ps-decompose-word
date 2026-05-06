<?php
/**
 * Main plugin coordinator.
 *
 * @package PS_Hyphenate
 */

namespace PS_Hyphenate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Render filter service.
	 *
	 * @var Render_Filter
	 */
	private $render_filter;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->settings      = new Settings();
		$hyphenator          = new Hyphenator( $this->settings );
		$html_processor      = new HTML_Processor( $hyphenator, $this->settings );
		$this->render_filter = new Render_Filter( $html_processor, $this->settings );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		$this->settings->register();
		$this->render_filter->register();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue frontend CSS baseline.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$options = $this->settings->get_options();

		if ( empty( $options['enabled'] ) && empty( $options['server_enabled'] ) ) {
			return;
		}

		wp_enqueue_style(
			'ps-hyphenate',
			PS_HYPHENATE_URL . 'assets/frontend.css',
			array(),
			\PS_HYPHENATE_VERSION
		);
	}
}