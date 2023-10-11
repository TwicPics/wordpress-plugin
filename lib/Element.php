<?php

namespace TwicPics;

/**
 * TwicPics plugin DOM element object.
 */
class Element {

    /**
     * tests fit data, creates associated default transformation and returns it
     */
    static private function get_default_transform( $fit ) {
        if ( $fit === null ) {
            return '*';
        }
        $fit = trim( $fit );
        switch ( $fit ) {
            case 'contain': {
                return 'contain';
            }
            case 'scale-down': {
                return 'max';
            }
        }
        return 'cover';
    }

    /**
     * Parses value of aspec-ratio from Style
     * Returns number or null
     */
    static private function parse_aspect_ratio ( $value ) {
        static $R_ASPECT_RATIO = '/(\d+(?:\.\d+)?)(?:\s*[\/:]\s*(\d+(?:\.\d+)?))?/';
        $parsed_aspect_ratio = null;
        if ( $value ) {
            preg_match( $R_ASPECT_RATIO, $value, $parsed );
            if ( $parsed ) {
                [ , $width, $height ] = $parsed;
                $height = $height ?? 1;
                $parsed_aspect_ratio = (float)$width / (float)$height;
            }
        }
        return $parsed_aspect_ratio;
    }

    /**
     * parses srcset attribute
     */
    static private function parse_srcset( $value ) {
        static $R_PART = '/^\s*(\S+)\s+(\d+(?:\.\d+)?)([wx])\s*$/';
        if ( $value === null ) {
            return null;
        }
        $can_handle = false;
        $array = array_map(
            function ( $part ) use ( &$can_handle, $R_PART ) {
                $matches = [];
                if ( preg_match( $R_PART, $part, $matches ) ) {
                    $item = \TwicPics\Alias::resolve( $matches[ 1 ] );
                    if ( $item !== null ) {
                        $can_handle = true;
                        return ( object ) [
                            'item'     => $item,
                            'modifier' => $matches[ 2 ] . $matches[ 3 ],
                            'width'    => ( $matches[ 3 ] === 'w' ) ? ( ( int ) $matches[ 2 ] ) : null,
                        ];
                    }
                }
                return $part;
            },
            explode( ',', $value )
        );
        return $can_handle ? $array : null;
    }

    /**
     * Selectors that shouldn't be handled by the script
     *
     * @var array $SCRIPT_CLASS_BLACKLIST Blacklisted plugins
     */
    static private $SCRIPT_CLASS_BLACKLIST = [
        '*.wp-block-nextend-smartslider3', // Smart Slider Plugin.
        '*.wds_slider_cont', // Slider.
    ];

    /**
     * Native DOM element
     *
     * @var DOMElement $_element the native DOM element
     */
    private $_element;

    /**
     * Style
     *
     * @var \TwicPics\Style $_style
     */
    private $_style;

    /**
     * Accessor with initialization if needed
     */
    public function get_style() {
        if ( $this->_style === null ) {
            $this->_style = new \TwicPics\Style( $this->attr( 'style' ), $this->_element->ownerDocument->encoding );
        }
        return $this->_style;
    }

    /**
     * Optimization level
     *
     * @var string $_optimization_level the optimization level (api or string)
     */
    public $optimization_level;

    /**
     * Class constructor
     *
     * @param DOMElement $element            native DOM element
     * @param string     $optimization_level the optimization level (api or script)
     */
    public function __construct( $element, $optimization_level ) {
        // sets element
        $this->_element           = $element;
        $this->optimization_level = $optimization_level;
        // switches to API if element is from a blacklisted plugin
        if ( ( $optimization_level === 'script' ) ) {
            if ( $this->is( implode( ',', self::$SCRIPT_CLASS_BLACKLIST ) ) ) {
                $this->optimization_level = 'api';
            }
        }
    }

    /**
     * Get descendants (iterator)
     */
    public function all_descendants() {
        foreach ( \TwicPics\DOM::all_child_elements( $this->_element ) as $child ) {
            $child = new self( $child, $this->optimization_level );
            yield $child;
            yield from $child->all_descendants();
        }
    }

    /**
     * Applies a transformation
     */
    public function apply_transform( $type, $transform, $fit = null ) {
        $expression = $this->transform( $type, $transform, false, $fit );
        if ( $expression !== '*' ) {
            $this->attr( 'data-twic-' . $type . '-transform', $expression );
        }
    }

    /**
     * Get/set aspect_ratio
     */
    public function aspect_ratio( ...$values ) {
        $rValidRatio = '/(\d+(?:\.\d+)?)(?:\s*[\/:]\s*(\d+(?:\.\d+)?))?/';
        $previous = self::parse_aspect_ratio( $this->get_style()->get_string_value( 'aspect-ratio' ) );
        if ( count( $values ) > 0 ) {
            $this->get_style()->set_aspect_ratio( ...$values );
            $this->attr( 'style', $this->get_style()->as_string() );
        }
        return $previous;
    }

    /**
     * Get/set/remove attributes
     */
    public function attr( $name, ...$values ) {
        $exists = $this->has_attr( $name );
        $previous = $exists ? $this->_element->getAttribute( $name ) : null;
        if ( count( $values ) > 0 ) {
            $value = $values[ 0 ];
            if ( $value === null ) {
                if ( $exists ) {
                    $this->_element->removeAttribute( $name );
                }
            } else {
                $this->_element->setAttribute( $name, $value );
            }
        }
        return $previous;
    }

    /**
     * Gets attributes one after the other until one is found
     */
    public function attr_from( ...$names ) {
        foreach ( $names as $name ) {
            $value = $this->attr( $name );
            if ( $value !== null ) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Get/set background
     */
    public function background( ...$values ) {
        $previous = $this->get_style()->get_background();
        if ( count( $values ) > 0 ) {
            $this->get_style()->set_background( ...$values );
            $this->attr( 'style', $this->get_style()->as_string() );
        }
        return $previous;
    }

    /**
     * Get/set class attribute
     */
    public function class( ...$values ) {
        if ( count( $values ) > 0 ) {
            $classes = explode(' ', $this->attr( 'class' ) );
            foreach ( $values as $value ) {
                if ( !in_array( $value, $classes )) {
                    $classes[] = $value;
                }
            }
            $this->attr( 'class', implode(' ', $classes ) );
        }
        return $this->attr( 'class' );
    }

    /**
     * Checks if attribute exists
     */
    public function has_attr( $name ) {
        return $this->_element->hasAttribute( $name );
    }

    /**
     * Get/set object-fit
     */
    public function object_fit( ...$values ) {
        $previous = $this->get_style()->get_string_value( 'object-fit' );
        if ( count( $values ) > 0 ) {
            $this->get_style()->set_object_fit( ...$values );
            $this->attr( 'style', $this->get_style()->as_string() );
        }
        return $previous;
    }

    /**
     * Get/set width
     */
    public function width( ...$values ) {
        $previous = $this->get_style()->get_dimension( 'width' );
        if ( count( $values ) > 0 ) {
            $this->get_style()->set_dimension( 'width', ...$values );
            $this->attr( 'style', $this->get_style()->as_string() );
        }
        return $previous;
    }

    /**
     * Get/set height
     */
    public function height( ...$values ) {
        $previous = $this->get_style()->get_dimension( 'height' );
        if ( count( $values ) > 0 ) {
            $this->get_style()->set_dimension( 'height', ...$values );
            $this->attr( 'style', $this->get_style()->as_string() );
        }
        return $previous;
    }

    // matches helpers
    static private $MATCHERS = [];
    static private $SUBS = [];
    /**
     * Handles special case(s) (for now just the '>' case)
     * and evaluates whether to create an entry in table self::$SUBS
     */
    static private function should_create_sub( $sub ) {
        if (
            !isset( self::$SUBS[ $sub ] ) &&
            $sub === '>'
        ) {
            self::$SUBS[ $sub ] = $sub;
        }
        return !isset( self::$SUBS[$sub] );
    }
    static private function create_sub( $sub ) {
        static $R_DELIMITERS = '/\]([[.])|([[.])/';
        if ( self::should_create_sub( $sub ) ) {
            $list = preg_split( $R_DELIMITERS, $sub, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
            $attributes = [];
            $classes    = [];
            for ( $i = 1; $i < count( $list ); ) {
                $delimiter = $list[ $i++ ];
                $value     = $list[ $i++ ];
                if ( $delimiter === '.' ) {
                    array_push( $classes, '/\b' . preg_quote( $value, '/' ) . '\b/i' );
                } else {
                    array_push( $attributes, $value );
                }
            }
            self::$SUBS[ $sub ] = ( object ) [
                'attributes' => $attributes,
                'classes' => $classes,
                'tag' => (
                    ( empty( $list[ 0 ] ) || ( $list[ 0 ] === '*' ) ) ?
                        null :
                        ( '/^' . preg_quote( $list[ 0 ], '/' ) . '$/i' )
                ),
            ];
        }
        return self::$SUBS[ $sub ];
    }

    static private function create_matchers( $expression ) {
        static $R_SEPARATOR  = '/\s*(>)\s*|\s+/';
        if ( !isset( self::$MATCHERS[ $expression ] ) ) {
            $matchers = [];
            $sub_expressions = explode(",",$expression);
            foreach( $sub_expressions as $sub_expression ) {
                array_push(
                    $matchers,
                    array_map(
                        [ '\\TwicPics\\Element', 'create_sub' ],
                        array_reverse( preg_split( $R_SEPARATOR, trim( $sub_expression ), -1, PREG_SPLIT_DELIM_CAPTURE ) ),
                    )
                );
            }
            self::$MATCHERS[ $expression ] = $matchers;
        }
        return self::$MATCHERS[ $expression ];
    }
    private static function matches( $element, $matcher ){
        if ( count( $matcher ) === 0 ) {
            return true;
        }
        $force_next = true;
        foreach( \TwicPics\DOM::all_ancestor_elements( $element, true ) as $ancestor ) {
            if ( self::matches_sub( $ancestor, $matcher[ 0 ] ) ) {
                array_shift( $matcher );
                if ( count( $matcher ) === 0 ) {
                    return true;
                }
                while ( ( $force_next = ( $matcher[ 0 ] === '>' ) ) ) {
                    array_shift( $matcher );
                    if ( count( $matcher ) === 0 ) {
                        return true;
                    }
                }
            } else if ( $force_next ) {
                return false;
            }
        }
        return false;
    }
    static private function matches_sub( $element, $sub ) {
        if ( !empty( $sub->tag ) ) {
            if ( !preg_match( $sub->tag, $element->tagName ) ) {
                return false;
            }
        }
        if ( count( $sub->classes ) ) {
            $class = $element->getAttribute( 'class' );
            foreach( $sub->classes as $regexp ) {
                if ( !preg_match( $regexp, $class ) ) {
                    return false;
                }
            }
        }
        return true;
    }


    public function append_attr( $name, $value )
    {
        $previous = $this->attr( $name );
        if ( $value ) {
            $this->attr( $name, $previous ? $previous.$value : $value);
        }
    }

    /**
     * Simple matcher
     * Accepts tagNames, classes, direct descendant selector (>) and or condition (,)
     */
    public function is( $expression ) {
        if ( !method_exists( $this->_element, 'getAttribute' ) ) {
            return false;
        }
        $matchers = self::create_matchers( $expression );
        foreach ( $matchers as $matcher ) {
            if ( self::matches( $this->_element, $matcher ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Removes the element from the DOM
     */
    public function remove() {
        $parent = $this->_element->parentNode;
        if ( !empty( $parent ) ) {
            $parent->removeChild( $this->_element );
        }
    }

    /**
     * Gets image dimensions (width and height attributes)
     */
    public function size() {
        $height = $this->attr( 'height' );
        $width  = $this->attr( 'width' );
        return ( object ) [
            'height' => $height ? ( int )$height :null,
            'width'  => $width ? ( int )$width :null,
        ];
    }

    /**
     * Gets resolved img src if available
     */
    public function src() {
        $src = $this->attr_from( 'src', 'data-src' );
        return ( $src === null ) ? null : \TwicPics\Alias::resolve( $src );
    }

    /**
     * Gets resolved srcset if available
     */
    public function srcset() {
        $srcset = $this->attr( 'srcset' );
        if ( $srcset === null ) {
            $srcset = $this->attr( 'data-src-set' );
        }
        return ( $srcset === null ) ? null : self::parse_srcset( $srcset );
    }

    /**
     * Determine if should be skipped
     */
    public function should_skip( $type ) {
        return ( $this->attr_from( 'data-twic-' . $type, 'data-twic-skip', 'data-twic-' . $type . '-skip' ) !== null );
    }

    /**
     * Get final transform
     */
    public function transform( $type, $transform, $final, $fit = null ) {
        static $R_FIT = '#(^|\/)\*(\/|$)#';
        static $R_SLASHES = '#/+#';
        static $R_TRIM = '#^/+|/+$#';
        $default = self::get_default_transform( $fit );
        $base = $this->attr_from( 'data-twic-' . $type . '-transform', 'data-twic-transform' );
        if ( $base === null ) {
            $base = $default;
        } else {
            $base = preg_replace( $R_FIT, "$1" . $default . "$2", $base );
        }
        $expression = $transform->as_string( $base );
        if ( $final ) {
            $expression = preg_replace( $R_FIT, '$1$2', $expression );
        }
        $expression = preg_replace( $R_SLASHES, '/', $expression );
        $expression = preg_replace( $R_TRIM, '', $expression );
        return $expression;
    }
}
