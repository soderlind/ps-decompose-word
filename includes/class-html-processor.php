<?php
/**
 * Safe HTML text-node processor.
 *
 * @package PS_Hyphenate
 */

namespace PS_Hyphenate;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HTML_Processor {
	const SOFT_HYPHEN_CLASS = 'ps-hyphenate-soft';

	/**
	 * Hyphenator service.
	 *
	 * @var Hyphenator
	 */
	private $hyphenator;

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Excluded tag lookup map.
	 *
	 * @var array<string,bool>|null
	 */
	private $excluded_tags = null;

	/**
	 * Constructor.
	 *
	 * @param Hyphenator $hyphenator Hyphenator service.
	 * @param Settings   $settings Settings service.
	 */
	public function __construct( Hyphenator $hyphenator, Settings $settings ) {
		$this->hyphenator = $hyphenator;
		$this->settings   = $settings;
	}

	/**
	 * Process rendered HTML.
	 *
	 * @param string $html Rendered HTML fragment.
	 * @param string $locale Locale.
	 * @return string
	 */
	public function process( $html, $locale ) {
		if ( '' === trim( $html ) || ! class_exists( 'DOMDocument' ) ) {
			return $html;
		}

		$options         = $this->settings->get_options();
		$min_word_length = \absint( $options['min_word_length'] );

		if ( ! preg_match( '/[\p{L}\p{M}]{' . $min_word_length . ',}/u', $html ) ) {
			return $html;
		}

		$document = new DOMDocument( '1.0', 'UTF-8' );
		$previous = libxml_use_internal_errors( true );
		$wrapped  = '<div id="ps-hyphenate-root">' . $html . '</div>';
		$loaded   = $document->loadHTML( '<?xml encoding="UTF-8">' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			return $html;
		}

		$root = $document->getElementById( 'ps-hyphenate-root' );

		if ( ! $root instanceof DOMElement ) {
			return $html;
		}

		$this->walk( $root, $locale );

		$output = '';

		foreach ( $root->childNodes as $child ) {
			$output .= $document->saveHTML( $child );
		}

		return '' === $output ? $html : $output;
	}

	/**
	 * Walk DOM nodes.
	 *
	 * @param DOMNode $node Current node.
	 * @param string  $locale Locale.
	 * @return bool Whether this node or a descendant received soft hyphens.
	 */
	private function walk( DOMNode $node, $locale ) {
		if ( $node instanceof DOMText ) {
			$original        = $node->nodeValue;
			$node->nodeValue = $this->hyphenator->hyphenate_text( $original, $locale );

			return false !== strpos( $node->nodeValue, Hyphenator::SOFT_HYPHEN );
		}

		if ( $node instanceof DOMElement && $this->is_excluded_element( $node ) ) {
			return false;
		}

		$children = array();
		$changed  = false;

		foreach ( $node->childNodes as $child ) {
			$children[] = $child;
		}

		foreach ( $children as $child ) {
			$changed = $this->walk( $child, $locale ) || $changed;
		}

		if ( $changed && $node instanceof DOMElement && 'ps-hyphenate-root' !== $node->getAttribute( 'id' ) ) {
			$this->add_soft_hyphen_class( $node );
		}

		return $changed;
	}

	/**
	 * Mark an element that contains inserted soft hyphens.
	 *
	 * @param DOMElement $element Element to mark.
	 * @return void
	 */
	private function add_soft_hyphen_class( DOMElement $element ) {
		$classes = preg_split( '/\s+/', trim( $element->getAttribute( 'class' ) ) );
		$classes = is_array( $classes ) ? array_filter( $classes ) : array();

		if ( in_array( self::SOFT_HYPHEN_CLASS, $classes, true ) ) {
			return;
		}

		$classes[] = self::SOFT_HYPHEN_CLASS;
		$element->setAttribute( 'class', implode( ' ', $classes ) );
	}

	/**
	 * Check whether an element should be skipped.
	 *
	 * @param DOMElement $element Element.
	 * @return bool
	 */
	private function is_excluded_element( DOMElement $element ) {
		$tag = strtolower( $element->tagName );

		if ( isset( $this->get_excluded_tag_map()[ $tag ] ) ) {
			return true;
		}

		if ( $element->hasAttribute( 'data-ps-hyphenate-skip' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get excluded tags as a lookup map.
	 *
	 * @return array<string,bool>
	 */
	private function get_excluded_tag_map() {
		if ( null !== $this->excluded_tags ) {
			return $this->excluded_tags;
		}

		$options = $this->settings->get_options();
		$tags    = isset( $options['excluded_tags'] ) && is_array( $options['excluded_tags'] ) ? $options['excluded_tags'] : array();

		$this->excluded_tags = array_fill_keys( array_map( 'strtolower', $tags ), true );

		return $this->excluded_tags;
	}
}