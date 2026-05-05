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
	 * @return void
	 */
	private function walk( DOMNode $node, $locale ) {
		if ( $node instanceof DOMText ) {
			$node->nodeValue = $this->hyphenator->hyphenate_text( $node->nodeValue, $locale );
			return;
		}

		if ( $node instanceof DOMElement && $this->is_excluded_element( $node ) ) {
			return;
		}

		$children = array();

		foreach ( $node->childNodes as $child ) {
			$children[] = $child;
		}

		foreach ( $children as $child ) {
			$this->walk( $child, $locale );
		}
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