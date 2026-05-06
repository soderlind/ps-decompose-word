<?php
declare(strict_types=1);

use Brain\Monkey\Functions;

it( 'loads frontend CSS when server soft hyphenation is enabled without native CSS hyphenation', function (): void {
	$options = ps_hyphenate_default_test_options();
	$options['enabled'] = 0;
	$options['server_enabled'] = 1;
	ps_hyphenate_set_test_options( $options );

	Functions\expect( 'wp_enqueue_style' )
		->once()
		->with(
			'ps-hyphenate',
			PS_HYPHENATE_URL . 'assets/frontend.css',
			array(),
			PS_HYPHENATE_VERSION
		);

	PS_Hyphenate\Plugin::instance()->enqueue_assets();
} );