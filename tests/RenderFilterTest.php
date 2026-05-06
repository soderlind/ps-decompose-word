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

it( 'hyphenates classic theme titles when server processing is enabled', function (): void {
	$render_filter = ps_hyphenate_make_render_filter();

	expect( ps_hyphenate_soft_hyphens_visible( $render_filter->filter_title( 'Departementenes digitaliseringsorganisasjon', 123 ) ) )
		->toBe( 'De|par|te|men|te|nes digitaliserings|organisasjon' );
} );

it( 'keeps server soft hyphenation active when native CSS hyphenation is disabled', function (): void {
	$options = ps_hyphenate_default_test_options();
	$options['enabled'] = 0;
	$options['server_enabled'] = 1;

	$render_filter = ps_hyphenate_make_render_filter( $options );

	expect( $render_filter->add_body_class( array() ) )->toContain( 'ps-hyphenate-server-enabled' )
		->not->toContain( 'ps-hyphenate-enabled' )
		->and( $render_filter->add_post_class( array() ) )->toContain( 'ps-hyphenate-server-enabled' )
		->not->toContain( 'ps-hyphenate' )
		->and( ps_hyphenate_soft_hyphens_visible( $render_filter->filter_title( 'Departementenes digitaliseringsorganisasjon', 123 ) ) )
		->toBe( 'De|par|te|men|te|nes digitaliserings|organisasjon' );
} );