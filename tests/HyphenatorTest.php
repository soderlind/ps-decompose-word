<?php
declare(strict_types=1);

it( 'matches locale shorthand exceptions for lowercase words', function (): void {
	$hyphenator = ps_hyphenate_make_hyphenator();

	expect( ps_hyphenate_soft_hyphens_visible( $hyphenator->hyphenate_text( 'digitaliseringsorganisasjon', 'nb_NO' ) ) )
		->toBe( 'digitaliserings|organisasjon' );
} );

it( 'matches exceptions case-insensitively and preserves title case', function (): void {
	$hyphenator = ps_hyphenate_make_hyphenator();

	expect( ps_hyphenate_soft_hyphens_visible( $hyphenator->hyphenate_text( 'Digitaliseringsorganisasjon', 'nb_NO' ) ) )
		->toBe( 'Digitaliserings|organisasjon' );
} );

it( 'matches exceptions case-insensitively and preserves uppercase', function (): void {
	$hyphenator = ps_hyphenate_make_hyphenator();

	expect( ps_hyphenate_soft_hyphens_visible( $hyphenator->hyphenate_text( 'DIGITALISERINGSORGANISASJON', 'nb_NO' ) ) )
		->toBe( 'DIGITALISERINGS|ORGANISASJON' );
} );

it( 'matches global explicit exceptions across locales', function (): void {
	$hyphenator = ps_hyphenate_make_hyphenator();

	expect( ps_hyphenate_soft_hyphens_visible( $hyphenator->hyphenate_text( 'Donaudampfschifffahrtsgesellschaft', 'de_DE' ) ) )
		->toBe( 'Donau|dampf|schiff|fahrts|gesellschaft' );
} );

it( 'falls back to TeX pattern dictionaries when no exception exists', function ( string $word, string $locale ): void {
	$options = array_merge(
		ps_hyphenate_default_test_options(),
		array(
			'exceptions'      => '',
			'min_word_length' => 8,
		)
	);
	$hyphenator = ps_hyphenate_make_hyphenator( $options );

	expect( ps_hyphenate_soft_hyphens_visible( $hyphenator->hyphenate_text( $word, $locale ) ) )
		->toContain( '|' );
} )->with(
	array(
		'Norwegian Bokmal'  => array( 'digitaliseringsorganisasjon', 'nb_NO' ),
		'Norwegian Nynorsk' => array( 'digitaliseringsorganisasjon', 'nn_NO' ),
		'German'            => array( 'Donaudampfschifffahrtsgesellschaft', 'de_DE' ),
		'Danish'            => array( 'digitaliseringsorganisation', 'da_DK' ),
		'Dutch'             => array( 'belastingdienstmedewerker', 'nl_NL' ),
		'Swedish'           => array( 'digitaliseringsorganisation', 'sv_SE' ),
	)
);

it( 'leaves words unchanged when no TeX dictionary is available for the locale', function (): void {
	$options = array_merge(
		ps_hyphenate_default_test_options(),
		array(
			'exceptions'      => '',
			'min_word_length' => 8,
		)
	);
	$hyphenator = ps_hyphenate_make_hyphenator( $options );

	expect( ps_hyphenate_soft_hyphens_visible( $hyphenator->hyphenate_text( 'digitaliseringsorganisasjon', 'fi_FI' ) ) )
		->toBe( 'digitaliseringsorganisasjon' );
} );

it( 'hyphenates eligible HTML text nodes', function (): void {
	$processor = ps_hyphenate_make_html_processor();

	expect( ps_hyphenate_soft_hyphens_visible( $processor->process( '<h1>DIO Departementenes digitaliseringsorganisasjon</h1>', 'nb_NO' ) ) )
		->toBe( '<h1>DIO De|par|te|men|te|nes digitaliserings|organisasjon</h1>' );
} );

it( 'leaves short HTML fragments unchanged without DOM normalization', function (): void {
	$processor = ps_hyphenate_make_html_processor();
	$html      = '<p>Short <strong>text</strong>.</p>';

	expect( $processor->process( $html, 'nb_NO' ) )->toBe( $html );
} );

it( 'skips excluded HTML elements', function (): void {
	$processor = ps_hyphenate_make_html_processor();

	expect( ps_hyphenate_soft_hyphens_visible( $processor->process( '<p>digitaliseringsorganisasjon <code>digitaliseringsorganisasjon</code></p>', 'nb_NO' ) ) )
		->toBe( '<p>digitaliserings|organisasjon <code>digitaliseringsorganisasjon</code></p>' );
} );