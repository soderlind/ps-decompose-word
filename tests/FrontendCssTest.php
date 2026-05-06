<?php
declare(strict_types=1);

it( 'covers paragraph elements rendered by core paragraph blocks', function (): void {
	$css = file_get_contents( dirname( __DIR__ ) . '/assets/frontend.css' );

	expect( $css )->toContain( '.ps-hyphenate-enabled :where(' )
		->and( $css )->toContain( '.ps-hyphenate-server-enabled :where(' )
		->and( $css )->toContain( '.wp-block-post-content p' )
		->and( $css )->toContain( '.entry-content p' );
} );

it( 'keeps server mode at manual hyphenation without forcing ugly breaks', function (): void {
	$css = file_get_contents( dirname( __DIR__ ) . '/assets/frontend.css' );

	expect( $css )->toMatch( '/\.ps-hyphenate-server-enabled\s+:where\([^}]+hyphens:\s*manual;/s' )
		->and( $css )->toMatch( '/\.ps-hyphenate-server-enabled\s+:where\([^}]+word-break:\s*normal;/s' )
		->and( $css )->not->toContain( 'overflow-wrap: anywhere' );
} );