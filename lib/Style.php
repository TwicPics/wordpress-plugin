<?php

namespace TwicPics;

/**
 * Style attribute wrapper
 */
class Style {

    /**
     * Get deep inside an array without breaking
     */
    static private function in_array( $array, ...$indices ) {
        foreach( $indices as $index ) {
            if ( empty( $array ) || ( count( $array ) <= $index ) ) {
                return null;
            }
            $array = $array[ $index ];
        }
        return $array;
    }

    /**
     * internal decl object
     */
    private $_background;

    /**
     * internal decl object
     */
    private $_decl;

    /**
     * Get declared value
     */
    private function get_value( $name ) {
        $rule = $this->_decl ? $this->_decl->getRulesAssoc()[ $name ] : null;
        return isset( $rule ) ? $rule->getValue() : null;
    }

    /**
     * Get declared value
     */
    private function set_value( $name, $value ) {
        if ( !isset( $this->_decl ) ) {
            return;
        }
        $rule = $this->_decl->getRulesAssoc()[ $name ];
        if ( isset( $rule ) ) {
            if ( $value === null ) {
                $this->_decl->removeRule( $name );
            }
            $rule->setValue( $value );
        } else if ( $value !== null ) {
            $rule = new \Sabberworm\CSS\Rule\Rule( $name, 0, 0 );
            $rule->setValue( $value );
            $this->_decl->addRule( $rule );
        }
    }

    /**
     * Constructor
     */
    public function __construct( $style, $encoding ) {
        static $R_BACKGROUND = '/\bbackground(?:-image)?\s*:/';
        static $R_URL = '/^url\((.+)\)$/';
        if ( ( $style !== null ) && preg_match( $R_BACKGROUND, $style ) ) {
                    $this->_decl = ( new \Sabberworm\CSS\Parser(
                'a{' . $style . '}',
                \Sabberworm\CSS\Settings::create()->withDefaultCharset( $encoding )
            ) )->parse()->getAllDeclarationBlocks()[ 0 ];
            $this->_decl->expandBorderShorthand();
            $image = $this->get_value( 'background-image' );
            if ( $image instanceof \Sabberworm\CSS\Value\URL ) {
                $tmp = [];
                if ( preg_match( $R_URL, ( string ) $image, $tmp ) ) {
                    $item = \TwicPics\Alias::resolve( json_decode( $tmp[ 1 ] ) );
                    if ( !empty( $item ) ) {
                        $position = $this->get_value( 'background-position' );
                        $this->_background = ( object ) [
                            'item'     => $item,
                            'position' => empty( $position ) ? null : ( ( string ) $position ),
                        ];
                    }
                }
            }
        }
    }

    /**
     * gets as string
     */
    public function as_string() {
        static $R_WRAPPER = '/^\s*a\s*{\s*|\s*}\s*$/';
        return preg_replace( $R_WRAPPER, '', ( string ) $this->_decl );
    }

    /**
     * gets background
     */
    public function get_background() {
        return $this->_background;
    }

    /**
     * sets background
     */
    public function set_background( $url, $position = null ) {
        $this->set_value( 'background-image', new \Sabberworm\CSS\Value\URL(
            new \Sabberworm\CSS\Value\CSSString( $url )
        ) );
        if ( $position !== null ) {
            $this->set_value(
                'background-position',
                $position === false ?
                    null :
                    new \Sabberworm\CSS\Value\CSSString( $position )
            );
        }
    }
}
