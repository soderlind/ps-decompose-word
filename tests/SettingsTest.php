<?php
declare(strict_types=1);

it( 'keeps core hyphenation defaults enabled conservatively', function (): void {
	$settings = new PS_Hyphenate\Settings();
	$defaults = $settings->get_defaults();

	expect( $defaults['enabled'] )->toBe( 1 )
		->and( $defaults['server_enabled'] )->toBe( 0 )
		->and( $defaults['pattern_enabled'] )->toBe( 1 );
} );

it( 'reads legacy settings after the plugin rename', function (): void {
	$options = ps_hyphenate_default_test_options();
	$options['min_word_length'] = 18;

	ps_hyphenate_set_legacy_test_options( $options );

	$settings = new PS_Hyphenate\Settings();

	expect( $settings->get_options()['min_word_length'] )->toBe( 18 );
} );

it( 'sanitizes core hyphenation toggles as booleans', function (): void {
	$settings = new PS_Hyphenate\Settings();

	$enabled = $settings->sanitize_options(
		array(
			'enabled'         => '1',
			'server_enabled'  => '1',
			'pattern_enabled' => '1',
			'min_word_length' => '14',
			'block_types'     => "core/paragraph\ncore/heading",
			'exceptions'      => 'nb_NO:digitaliserings-organisasjon',
		)
	);

	$missing = $settings->sanitize_options(
		array(
			'min_word_length' => '14',
			'block_types'     => 'core/paragraph',
		)
	);

	expect( $enabled['enabled'] )->toBe( 1 )
		->and( $enabled['server_enabled'] )->toBe( 1 )
		->and( $enabled['pattern_enabled'] )->toBe( 1 )
		->and( $missing['enabled'] )->toBe( 0 )
		->and( $missing['server_enabled'] )->toBe( 0 )
		->and( $missing['pattern_enabled'] )->toBe( 0 );
} );

it( 'keeps block type multi-select submissions as block type arrays', function (): void {
	$settings = new PS_Hyphenate\Settings();

	$sanitized = $settings->sanitize_options(
		array(
			'enabled'         => '1',
			'server_enabled'  => '1',
			'pattern_enabled' => '1',
			'min_word_length' => '14',
			'block_types'     => array( 'core/paragraph', 'core/heading', 'core/paragraph', 'acf/hero_block' ),
		)
	);

	expect( $sanitized['block_types'] )->toBe( array( 'core/paragraph', 'core/heading', 'acf/hero_block' ) )
		->and( $sanitized['block_types'] )->not->toContain( 'array' );
} );