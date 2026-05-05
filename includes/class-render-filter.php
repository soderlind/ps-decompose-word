<?php
/**
 * Frontend render filters.
 *
 * @package PS_Decompose_Word
 */

namespace PS_Decompose_Word;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Render_Filter {
	/**
	 * HTML processor.
	 *
	 * @var HTML_Processor
	 */
	private $html_processor;

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Target block lookup map.
	 *
	 * @var array<string,bool>|null
	 */
	private $target_block_types = null;

	/**
	 * Request-local render cache.
	 *
	 * @var array<string,string>
	 */
	private $runtime_cache = array();

	/**
	 * Constructor.
	 *
	 * @param HTML_Processor $html_processor HTML processor.
	 * @param Settings       $settings Settings service.
	 */
	public function __construct( HTML_Processor $html_processor, Settings $settings ) {
		$this->html_processor = $html_processor;
		$this->settings       = $settings;
	}

	/**
	 * Register filters.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'render_block_core/post-title', array( $this, 'filter_post_title_block' ), 20, 3 );
		add_filter( 'render_block', array( $this, 'filter_block' ), 20, 2 );
		add_filter( 'the_content', array( $this, 'filter_content' ), 20 );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
		add_filter( 'post_class', array( $this, 'add_post_class' ) );
	}

	/**
	 * Filter the dynamic post-title block directly.
	 *
	 * @param string               $block_content Rendered block content.
	 * @param array<string,mixed>  $block Block data.
	 * @param \WP_Block|null       $instance Block instance.
	 * @return string
	 */
	public function filter_post_title_block( $block_content, $block, $instance = null ) {
		if ( ! $this->should_process_server_side() ) {
			return $block_content;
		}

		return $this->process_with_cache( $block_content, get_locale() );
	}

	/**
	 * Filter rendered block output.
	 *
	 * @param string              $block_content Rendered block content.
	 * @param array<string,mixed> $block Block data.
	 * @return string
	 */
	public function filter_block( $block_content, $block ) {
		if ( ! $this->should_process_server_side() ) {
			return $block_content;
		}

		$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';

		if ( '' === $block_name || ! $this->is_target_block_type( $block_name ) ) {
			return $block_content;
		}

		return $this->process_with_cache( $block_content, get_locale() );
	}

	/**
	 * Filter classic content as a fallback.
	 *
	 * @param string $content Rendered content.
	 * @return string
	 */
	public function filter_content( $content ) {
		if ( ! $this->should_process_server_side() || ! $this->is_main_frontend_content() ) {
			return $content;
		}

		return $this->process_with_cache( $content, get_locale() );
	}

	/**
	 * Add frontend class for CSS hyphenation.
	 *
	 * @param array<int,string> $classes Post classes.
	 * @return array<int,string>
	 */
	public function add_post_class( $classes ) {
		$options = $this->settings->get_options();

		if ( ! empty( $options['enabled'] ) && is_array( $classes ) ) {
			$classes[] = 'ps-decompose-word';

			if ( ! empty( $options['server_enabled'] ) ) {
				$classes[] = 'ps-decompose-word-server-enabled';
			}
		}

		return $classes;
	}

	/**
	 * Add a body class so block-theme templates outside post wrappers are covered.
	 *
	 * @param array<int,string> $classes Body classes.
	 * @return array<int,string>
	 */
	public function add_body_class( $classes ) {
		$options = $this->settings->get_options();

		if ( ! empty( $options['enabled'] ) && is_array( $classes ) ) {
			$classes[] = 'ps-decompose-word-enabled';

			if ( ! empty( $options['server_enabled'] ) ) {
				$classes[] = 'ps-decompose-word-server-enabled';
			}
		}

		return $classes;
	}

	/**
	 * Check whether server-side processing should run.
	 *
	 * @return bool
	 */
	private function should_process_server_side() {
		$options = $this->settings->get_options();

		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() ) {
			return false;
		}

		return ! empty( $options['enabled'] ) && ! empty( $options['server_enabled'] );
	}

	/**
	 * Check main frontend content context.
	 *
	 * @return bool
	 */
	private function is_main_frontend_content() {
		if ( function_exists( 'in_the_loop' ) && ! in_the_loop() ) {
			return false;
		}

		if ( function_exists( 'is_main_query' ) && ! is_main_query() ) {
			return false;
		}

		return true;
	}

	/**
	 * Get target block types.
	 *
	 * @return array<int,string>
	 */
	private function get_target_block_types() {
		if ( null !== $this->target_block_types ) {
			return $this->target_block_types;
		}

		$options = $this->settings->get_options();
		$types   = isset( $options['block_types'] ) && is_array( $options['block_types'] ) ? $options['block_types'] : array();

		$this->target_block_types = array_fill_keys( $types, true );

		return $this->target_block_types;
	}

	/**
	 * Check if a rendered block should be processed.
	 *
	 * @param string $block_name Block name.
	 * @return bool
	 */
	private function is_target_block_type( $block_name ) {
		if ( 'core/post-title' === $block_name ) {
			return true;
		}

		return isset( $this->get_target_block_types()[ $block_name ] );
	}

	/**
	 * Process HTML with a request/object-cache key tied to settings and locale.
	 *
	 * @param string $html Rendered HTML.
	 * @param string $locale Locale.
	 * @return string
	 */
	private function process_with_cache( $html, $locale ) {
		$options    = $this->settings->get_options();
		$cache_key  = 'render_' . md5( PS_DECOMPOSE_WORD_VERSION . "\0" . $locale . "\0" . $options['min_word_length'] . "\0" . $options['exceptions'] . "\0" . $html );

		if ( isset( $this->runtime_cache[ $cache_key ] ) ) {
			return $this->runtime_cache[ $cache_key ];
		}

		$cached     = wp_cache_get( $cache_key, 'ps_decompose_word' );

		if ( false !== $cached ) {
			$this->runtime_cache[ $cache_key ] = (string) $cached;

			return $this->runtime_cache[ $cache_key ];
		}

		$processed = $this->html_processor->process( $html, $locale );
		$this->runtime_cache[ $cache_key ] = $processed;

		wp_cache_set( $cache_key, $processed, 'ps_decompose_word', HOUR_IN_SECONDS );

		return $processed;
	}
}