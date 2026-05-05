<?php
declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once dirname( __DIR__ ) . '/includes/class-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-hyphenator.php';
require_once dirname( __DIR__ ) . '/includes/class-html-processor.php';
require_once dirname( __DIR__ ) . '/includes/class-render-filter.php';

use Brain\Monkey;
use Brain\Monkey\Functions;

$GLOBALS['ps_decompose_word_test_options'] = array();

function ps_decompose_word_register_wordpress_mocks(): void {
	Monkey\setUp();

	$get_option = static function ( $name, $default = false ) {
		$options = isset( $GLOBALS['ps_decompose_word_test_options'] ) && is_array( $GLOBALS['ps_decompose_word_test_options'] )
			? $GLOBALS['ps_decompose_word_test_options']
			: array();

		return array_key_exists( $name, $options ) ? $options[ $name ] : $default;
	};

	$wp_parse_args = static function ( $args, $defaults = array() ) {
		return array_merge( (array) $defaults, (array) $args );
	};

	$absint = static function ( $value ) {
		return abs( (int) $value );
	};

	$apply_filters = static function ( $hook_name, $value ) {
		return $value;
	};

	$wp_unslash = static function ( $value ) {
		return $value;
	};

	$sanitize_textarea_field = static function ( $value ) {
		return is_string( $value ) ? trim( $value ) : '';
	};

	Functions\when( 'get_option' )->alias( $get_option );
	Functions\when( 'wp_parse_args' )->alias( $wp_parse_args );
	Functions\when( 'absint' )->alias( $absint );
	Functions\when( 'apply_filters' )->alias( $apply_filters );
	Functions\when( 'wp_unslash' )->alias( $wp_unslash );
	Functions\when( 'sanitize_textarea_field' )->alias( $sanitize_textarea_field );

	register_shutdown_function(
		static function (): void {
			Monkey\tearDown();
		}
	);
}

ps_decompose_word_register_wordpress_mocks();

function ps_decompose_word_set_test_options( array $options ): void {
	$GLOBALS['ps_decompose_word_test_options'] = array(
		PS_Decompose_Word\Settings::OPTION_NAME => $options,
	);
}

function ps_decompose_word_default_test_options(): array {
	return array(
		'enabled'         => 1,
		'server_enabled'  => 1,
		'pattern_enabled' => 1,
		'min_word_length' => 10,
		'block_types'     => array( 'core/post-title', 'core/paragraph' ),
		'exceptions'      => "nb_NO:digitaliserings-organisasjon\nDonaudampfschifffahrtsgesellschaft=Donau-dampf-schiff-fahrts-gesellschaft",
		'excluded_tags'   => array( 'a', 'button', 'code', 'kbd', 'math', 'pre', 'samp', 'script', 'style', 'svg', 'textarea' ),
	);
}

function ps_decompose_word_soft_hyphens_visible( string $value ): string {
	return str_replace( "\xC2\xAD", '|', $value );
}

function ps_decompose_word_make_hyphenator( ?array $options = null ): PS_Decompose_Word\Hyphenator {
	ps_decompose_word_set_test_options( $options ?? ps_decompose_word_default_test_options() );

	return new PS_Decompose_Word\Hyphenator( new PS_Decompose_Word\Settings() );
}

function ps_decompose_word_make_html_processor( ?array $options = null ): PS_Decompose_Word\HTML_Processor {
	ps_decompose_word_set_test_options( $options ?? ps_decompose_word_default_test_options() );

	$settings   = new PS_Decompose_Word\Settings();
	$hyphenator = new PS_Decompose_Word\Hyphenator( $settings );

	return new PS_Decompose_Word\HTML_Processor( $hyphenator, $settings );
}

function ps_decompose_word_make_render_filter( ?array $options = null ): PS_Decompose_Word\Render_Filter {
	ps_decompose_word_set_test_options( $options ?? ps_decompose_word_default_test_options() );

	$settings       = new PS_Decompose_Word\Settings();
	$hyphenator     = new PS_Decompose_Word\Hyphenator( $settings );
	$html_processor = new PS_Decompose_Word\HTML_Processor( $hyphenator, $settings );

	return new PS_Decompose_Word\Render_Filter( $html_processor, $settings );
}