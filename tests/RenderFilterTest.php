<?php
declare(strict_types=1);

it( 'marks body and post classes when server soft hyphenation is enabled', function (): void {
	$render_filter = ps_decompose_word_make_render_filter();

	expect( $render_filter->add_body_class( array() ) )->toContain( 'ps-decompose-word-enabled', 'ps-decompose-word-server-enabled' )
		->and( $render_filter->add_post_class( array() ) )->toContain( 'ps-decompose-word', 'ps-decompose-word-server-enabled' );
} );

it( 'does not mark server classes when only native CSS hyphenation is enabled', function (): void {
	$options = ps_decompose_word_default_test_options();
	$options['server_enabled'] = 0;

	$render_filter = ps_decompose_word_make_render_filter( $options );

	expect( $render_filter->add_body_class( array() ) )->toContain( 'ps-decompose-word-enabled' )
		->not->toContain( 'ps-decompose-word-server-enabled' )
		->and( $render_filter->add_post_class( array() ) )->toContain( 'ps-decompose-word' )
		->not->toContain( 'ps-decompose-word-server-enabled' );
} );