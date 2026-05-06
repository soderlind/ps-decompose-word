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

it( 'preserves visible compound hyphens from double hyphen exceptions', function ( string $word, string $pattern, string $expected ): void {
	$options = array_merge(
		ps_hyphenate_default_test_options(),
		array(
			'exceptions' => 'nb_NO:' . $word . '=' . $pattern,
		)
	);
	$hyphenator = ps_hyphenate_make_hyphenator( $options );

	expect( ps_hyphenate_soft_hyphens_visible( $hyphenator->hyphenate_text( $word, 'nb_NO' ) ) )
		->toBe( $expected );
} )->with(
	array(
		'personvernforordningen' => array( 'personvernforordningen', 'per-son-vern--for-ord-nin-gen', 'per|son|vern|-for|ord|nin|gen' ),
		'tilgjengelighetserklaering' => array( 'tilgjengelighetserklæring', 'til-gjen-ge-lig-hets--er-klæ-ring', 'til|gjen|ge|lig|hets|-er|klæ|ring' ),
		'menneskerettighetsorganisasjon' => array( 'menneskerettighetsorganisasjon', 'men-neske-ret-tig-hets--or-ga-ni-sa-sjon', 'men|neske|ret|tig|hets|-or|ga|ni|sa|sjon' ),
	)
);

it( 'does not add TeX pattern breaks to words matched by exceptions', function (): void {
	$options = array_merge(
		ps_hyphenate_default_test_options(),
		array(
			'pattern_enabled' => 1,
			'min_word_length' => 8,
			'exceptions'      => 'nb_NO:personvernforordningen=per-son-vern--for-ord-nin-gen',
		)
	);
	$hyphenator = ps_hyphenate_make_hyphenator( $options );

	expect( ps_hyphenate_soft_hyphens_visible( $hyphenator->hyphenate_text( 'personvernforordningen', 'nb_NO' ) ) )
		->toBe( 'per|son|vern|-for|ord|nin|gen' );
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

it( 'normalizes duplicate soft hyphens from pattern dictionaries', function (): void {
	$options = array_merge(
		ps_hyphenate_default_test_options(),
		array(
			'exceptions'      => '',
			'min_word_length' => 8,
		)
	);
	$hyphenator = ps_hyphenate_make_hyphenator( $options );
	$hyphenated = ps_hyphenate_soft_hyphens_visible( $hyphenator->hyphenate_text( 'personvernforordningen', 'nb_NO' ) );

	expect( $hyphenated )->toContain( '|' )
		->and( $hyphenated )->not->toContain( '||' );
} );

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
		->toBe( '<h1 class="ps-hyphenate-soft">DIO De|par|te|men|te|nes digitaliserings|organisasjon</h1>' );
} );

it( 'marks rendered paragraph blocks that receive soft hyphens', function (): void {
	$processor = ps_hyphenate_make_html_processor();

	expect( ps_hyphenate_soft_hyphens_visible( $processor->process( '<p class="has-large-font-size">Departementenes digitaliseringsorganisasjon</p>', 'nb_NO' ) ) )
		->toBe( '<p class="has-large-font-size ps-hyphenate-soft">De|par|te|men|te|nes digitaliserings|organisasjon</p>' );
} );

it( 'marks elements that already contain title soft hyphens', function (): void {
	$processor = ps_hyphenate_make_html_processor();
	$html      = '<h1 class="wp-block-post-title">DIO Departementenes di' . "\xC2\xAD" . 'gitaliseringsorganisasjon</h1>';

	expect( ps_hyphenate_soft_hyphens_visible( $processor->process( $html, 'nb_NO' ) ) )
		->toBe( '<h1 class="wp-block-post-title ps-hyphenate-soft">DIO Departementenes di|gitaliseringsorganisasjon</h1>' );
} );

it( 'leaves short HTML fragments unchanged without DOM normalization', function (): void {
	$processor = ps_hyphenate_make_html_processor();
	$html      = '<p>Short <strong>text</strong>.</p>';

	expect( $processor->process( $html, 'nb_NO' ) )->toBe( $html );
} );

it( 'skips excluded HTML elements', function (): void {
	$processor = ps_hyphenate_make_html_processor();

	expect( ps_hyphenate_soft_hyphens_visible( $processor->process( '<p>digitaliseringsorganisasjon <code>digitaliseringsorganisasjon</code></p>', 'nb_NO' ) ) )
		->toBe( '<p class="ps-hyphenate-soft">digitaliserings|organisasjon <code>digitaliseringsorganisasjon</code></p>' );
} );