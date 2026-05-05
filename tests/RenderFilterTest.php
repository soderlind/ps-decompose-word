<?php
declare(strict_types=1);

it( 'marks body and post classes when server soft hyphenation is enabled', function (): void {
	$render_filter = ps_hyphenate_make_render_filter();

	expect( $render_filter->add_body_class( array() ) )->toContain( 'ps-hyphenate-enabled', 'ps-hyphenate-server-enabled' )
		->and( $render_filter->add_post_class( array() ) )->toContain( 'ps-hyphenate', 'ps-hyphenate-server-enabled' );
} );

it( 'does not mark server classes when only native CSS hyphenation is enabled', function (): void {
	$options = ps_hyphenate_default_test_options();
	$options['server_enabled'] = 0;

	$render_filter = ps_hyphenate_make_render_filter( $options );

	expect( $render_filter->add_body_class( array() ) )->toContain( 'ps-hyphenate-enabled' )
		->not->toContain( 'ps-hyphenate-server-enabled' )
		->and( $render_filter->add_post_class( array() ) )->toContain( 'ps-hyphenate' )
		->not->toContain( 'ps-hyphenate-server-enabled' );
} );