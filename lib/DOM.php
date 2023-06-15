<?php

namespace TwicPics;

/**
 * TwicPics plugin DOM parsing utilities.
 */
class DOM {

    /**
     * Gets all ancestor nodes as an iterable (suitable for foreach)
     *
     * @param DOMNode $node The node element.
     * @param boolean $includes_self Set the param to true to include the node itself.
     */
    static public function all_ancestor_elements( $node, $includes_self = false ) {
        foreach ( self::all_ancestor_nodes( $node, $includes_self ) as $ancestor ) {
            if ( isset( $ancestor->tagName ) ) {
                yield $ancestor;
            }
        }
    }

    /**
     * Gets all ancestor elements as an iterable (suitable for foreach)
     *
     * @param DOMNode $node The node element. If the node if not an element, it will never be part of the iteration.
     * @param boolean $includes_self Set the param to true to include the node itself.
     */
    static public function all_ancestor_nodes( $node, $includes_self = false ) {
        if ( $includes_self ) {
            yield $node;
        }
        while ( ( $node = $node->parentNode ) !== null ) {
            yield $node;
        }
    }

    /**
     * Traverse child elements
     * @param DOMNode $node The node element.
     */
    static public function all_child_elements( $node ) {
        foreach ( self::all_child_nodes( $node ) as $child ) {
            if ( isset( $child->tagName ) ) {
                yield $child;
            }
        }
    }

    /**
     * Traverse child nodes
     * @param DOMNode $node The node element.
     */
    static public function all_child_nodes( $node ) {
        return $node->childNodes;
    }

    /**
     * Class constructor
     *
     * @param string $code is the HTML source code to be parsed.
     * @param string $encoding is the text encoding of the source code.
     */
    public function __construct( $code, $encoding = 'UTF-8' ) {
        $this->_encoding = $encoding;
        $this->_ok = false;
        if ( empty( $code ) || empty( $code = trim( $code ) ) ) {
            return;
        }
        $this->_dom = new \DOMDocument();
        libxml_use_internal_errors( true );
        $this->_ok = $this->_dom->loadHTML( mb_convert_encoding( $code, 'HTML-ENTITIES', $encoding ) );
        libxml_clear_errors();
    }

    /**
     * Creates an element with an associative array of attributes and/or text content
     *
     * @param string         $tag_name The tag name.
     * @param string | array $content_or_attributes The tag name.
     */
    public function create( $tag_name, $content_or_attributes = null, $content = null ) {
        $attributes = is_array( $content_or_attributes ) ? $content_or_attributes : [];
        $content    = ( null === $content ) ? ( is_string( $content_or_attributes ) ? $content_or_attributes : null ) : $content;
        $element    = $this->_dom->createElement( $tag_name, $content );

        foreach ( $attributes as $key => $value ) {
            $element->setAttribute( $key, $value );
        }
        return $element;
    }

    /**
     * Gets all elements with a given tag name in the document
     *
     * @param string $tag_name The tag name.
     */
    public function get_all( $tag_name ) {
        return $this->_dom->getElementsByTagName( $tag_name );
    }

    /**
     * Gets body or top node
     */
    public function get_body() {
        $body = $this->get_first( 'body' );
        return ( $body === null ) ? $this->_dom : $body;
    }

    /**
     * Gets encoding
     */
    public function get_encoding() {
        return $this->_encoding;
    }

    /**
     * Gets the first element with a given tag name found in the document
     *
     * @param string $tag_name The tag name.
     */
    public function get_first( $tag_name ) {
        $all = $this->get_all( $tag_name );
        return count( $all ) ? $all[0] : null;
    }

    /**
     * Gets or creates the head element
     */
    public function get_head() {
        $head = $this->get_first( 'head' );
        if ( null === $head ) {
            $head   = $this->create( 'head' );
            $html   = $this->get_first( 'html' );
            $parent = ( null === $html ) ? $this->_dom : $html;
            $parent.insertBefore( $head, $parent->firstChild );
        }
        return $head;
    }

    /**
     * True if source code was parseable and not empty
     */
    public function is_ok() {
        return $this->_ok;
    }

    /**
     * Return the document as a string of HTML code
     */
    public function to_html() {
        return $this->_dom->saveHTML();
    }
}
