<?php
/**
 * Plugin settings.
 *
 * @package PS_Hyphenate
 */

namespace PS_Hyphenate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {
	const OPTION_NAME = 'ps_hyphenate_settings';
	const LEGACY_OPTION_NAME = 'ps_decompose_word_settings';

	/**
	 * Request-local defaults cache.
	 *
	 * @var array<string,mixed>|null
	 */
	private $defaults = null;

	/**
	 * Request-local merged options cache.
	 *
	 * @var array<string,mixed>|null
	 */
	private $options = null;

	/**
	 * Common prose/title blocks that should receive server-side processing by default.
	 *
	 * @var array<int,string>
	 */
	const COMMON_BLOCK_TYPES = array(
		'core/post-title',
		'core/query-title',
		'core/heading',
		'core/paragraph',
		'core/list',
		'core/list-item',
		'core/quote',
		'core/pullquote',
		'core/table',
		'core/verse',
		'core/media-text',
		'core/cover',
		'core/group',
		'core/column',
	);

	/**
	 * Register admin hooks and settings.
	 *
	 * @return void
	 */
	public function register() {
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		}
	}

	/**
	 * Default options.
	 *
	 * @return array<string,mixed>
	 */
	public function get_defaults() {
		if ( null !== $this->defaults ) {
			return $this->defaults;
		}

		$this->defaults = array(
			'enabled'         => 1,
			'server_enabled'  => 0,
			'pattern_enabled' => 1,
			'min_word_length' => 14,
			'block_types'     => self::COMMON_BLOCK_TYPES,
			'exceptions'      => '',
			'excluded_tags'   => array( 'a', 'button', 'code', 'kbd', 'math', 'pre', 'samp', 'script', 'style', 'svg', 'textarea' ),
		);

		return $this->defaults;
	}

	/**
	 * Get merged options.
	 *
	 * @return array<string,mixed>
	 */
	public function get_options() {
		if ( null !== $this->options ) {
			return $this->options;
		}

		$options = \get_option( self::OPTION_NAME, array() );

		if ( empty( $options ) ) {
			$options = \get_option( self::LEGACY_OPTION_NAME, array() );
		}

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$this->options = \wp_parse_args( $options, $this->get_defaults() );

		return $this->options;
	}

	/**
	 * Register Settings API entries.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'ps_hyphenate',
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_options' ),
			)
		);

		add_settings_section(
			'ps_hyphenate_main',
			__( 'Hyphenation', 'ps-hyphenate' ),
			'__return_false',
			'ps-hyphenate'
		);

		add_settings_field( 'enabled', __( 'CSS hyphenation', 'ps-hyphenate' ), array( $this, 'render_enabled_field' ), 'ps-hyphenate', 'ps_hyphenate_main' );
		add_settings_field( 'server_enabled', __( 'Soft hyphen exceptions', 'ps-hyphenate' ), array( $this, 'render_server_enabled_field' ), 'ps-hyphenate', 'ps_hyphenate_main' );
		add_settings_field( 'pattern_enabled', __( 'TeX pattern hyphenation', 'ps-hyphenate' ), array( $this, 'render_pattern_enabled_field' ), 'ps-hyphenate', 'ps_hyphenate_main' );
		add_settings_field( 'min_word_length', __( 'Minimum word length', 'ps-hyphenate' ), array( $this, 'render_min_word_length_field' ), 'ps-hyphenate', 'ps_hyphenate_main' );
		add_settings_field( 'block_types', __( 'Block types', 'ps-hyphenate' ), array( $this, 'render_block_types_field' ), 'ps-hyphenate', 'ps_hyphenate_main' );
		add_settings_field( 'exceptions', __( 'Exception dictionary', 'ps-hyphenate' ), array( $this, 'render_exceptions_field' ), 'ps-hyphenate', 'ps_hyphenate_main' );
	}

	/**
	 * Add settings page.
	 *
	 * @return void
	 */
	public function add_options_page() {
		add_options_page(
			__( 'PS Hyphenate', 'ps-hyphenate' ),
			__( 'PS Hyphenate', 'ps-hyphenate' ),
			'manage_options',
			'ps-hyphenate',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string,mixed> $input Raw input.
	 * @return array<string,mixed>
	 */
	public function sanitize_options( $input ) {
		$defaults = $this->get_defaults();
		$input    = is_array( $input ) ? $input : array();

		$block_types = $this->parse_lines_or_csv( isset( $input['block_types'] ) ? (string) wp_unslash( $input['block_types'] ) : '' );
		$block_types = array_filter( array_map( array( $this, 'sanitize_block_type' ), $block_types ) );

		$min_word_length = isset( $input['min_word_length'] ) ? \absint( $input['min_word_length'] ) : $defaults['min_word_length'];
		$min_word_length = max( 6, min( 60, $min_word_length ) );

		return array(
			'enabled'         => empty( $input['enabled'] ) ? 0 : 1,
			'server_enabled'  => empty( $input['server_enabled'] ) ? 0 : 1,
			'pattern_enabled' => empty( $input['pattern_enabled'] ) ? 0 : 1,
			'min_word_length' => $min_word_length,
			'block_types'     => empty( $block_types ) ? $defaults['block_types'] : array_values( $block_types ),
			'exceptions'      => isset( $input['exceptions'] ) ? sanitize_textarea_field( wp_unslash( $input['exceptions'] ) ) : '',
			'excluded_tags'   => $defaults['excluded_tags'],
		);
	}

	/**
	 * Merge common block presets into existing options without removing custom entries.
	 *
	 * @param array<string,mixed> $options Existing options.
	 * @return array<string,mixed>
	 */
	public function merge_common_block_types( $options ) {
		$options = is_array( $options ) ? $options : array();
		$current = isset( $options['block_types'] ) && is_array( $options['block_types'] ) ? $options['block_types'] : array();
		$current = array_filter( array_map( array( $this, 'sanitize_block_type' ), $current ) );

		$options['block_types'] = array_values( array_unique( array_merge( self::COMMON_BLOCK_TYPES, $current ) ) );

		return $options;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'PS Hyphenate', 'ps-hyphenate' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'ps_hyphenate' );
				do_settings_sections( 'ps-hyphenate' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render enabled field.
	 *
	 * @return void
	 */
	public function render_enabled_field() {
		$options = $this->get_options();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled]" value="1" <?php checked( 1, $options['enabled'] ); ?> />
			<?php echo esc_html__( 'Apply native CSS hyphenation to frontend content.', 'ps-hyphenate' ); ?>
		</label>
		<?php
	}

	/**
	 * Render server field.
	 *
	 * @return void
	 */
	public function render_server_enabled_field() {
		$options = $this->get_options();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[server_enabled]" value="1" <?php checked( 1, $options['server_enabled'] ); ?> />
			<?php echo esc_html__( 'Insert soft hyphens at render time.', 'ps-hyphenate' ); ?>
		</label>
		<?php
	}

	/**
	 * Render pattern engine field.
	 *
	 * @return void
	 */
	public function render_pattern_enabled_field() {
		$options = $this->get_options();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[pattern_enabled]" value="1" <?php checked( 1, $options['pattern_enabled'] ); ?> />
			<?php echo esc_html__( 'Use TeX hyphenation dictionaries after explicit exceptions.', 'ps-hyphenate' ); ?>
		</label>
		<?php
	}

	/**
	 * Render minimum word length field.
	 *
	 * @return void
	 */
	public function render_min_word_length_field() {
		$options = $this->get_options();
		?>
		<input type="number" min="6" max="60" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[min_word_length]" value="<?php echo esc_attr( (string) $options['min_word_length'] ); ?>" />
		<?php
	}

	/**
	 * Render block types field.
	 *
	 * @return void
	 */
	public function render_block_types_field() {
		$options = $this->get_options();
		$value   = implode( "\n", (array) $options['block_types'] );
		?>
		<textarea name="<?php echo esc_attr( self::OPTION_NAME ); ?>[block_types]" rows="10" cols="48" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php echo esc_html__( 'One block type per line. Common prose, title, list, quote, table, and layout blocks are prefilled. Classic content is handled separately by the_content.', 'ps-hyphenate' ); ?></p>
		<?php
	}

	/**
	 * Render exception dictionary field.
	 *
	 * @return void
	 */
	public function render_exceptions_field() {
		$options = $this->get_options();
		?>
		<textarea name="<?php echo esc_attr( self::OPTION_NAME ); ?>[exceptions]" rows="10" cols="72" class="large-text code" placeholder="Donaudampfschifffahrtsgesellschaft=Donau-dampf-schiff-fahrts-gesellschaft&#10;nb_NO:menneskerettighetsorganisasjon=menneske-rettighets-organisasjon"><?php echo esc_textarea( (string) $options['exceptions'] ); ?></textarea>
		<p class="description"><?php echo esc_html__( 'Use hyphens in replacements to mark soft hyphen positions. Prefix with locale and a colon for locale-specific entries.', 'ps-hyphenate' ); ?></p>
		<?php
	}

	/**
	 * Parse comma- or line-separated values.
	 *
	 * @param string $value Raw value.
	 * @return array<int,string>
	 */
	private function parse_lines_or_csv( $value ) {
		$items = preg_split( '/[\r\n,]+/', $value );

		if ( ! is_array( $items ) ) {
			return array();
		}

		return array_filter( array_map( 'trim', $items ) );
	}

	/**
	 * Sanitize a Gutenberg block type.
	 *
	 * @param string $block_type Block type.
	 * @return string
	 */
	private function sanitize_block_type( $block_type ) {
		return preg_replace( '/[^a-z0-9_\/-]/', '', strtolower( $block_type ) );
	}
}