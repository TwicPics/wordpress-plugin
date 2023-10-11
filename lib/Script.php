<?php

namespace TwicPics;

/**
 * TwicPics Additional script management
 * eg: to handle issues when using Woo Commerce
 */
class Script {

    /**
     * Associative array between a match expression and a script to add
     */
    static private $ADDITIONAL_SCRIPT_MAP = array(
        '*.woocommerce-product-gallery__image a > img' => 'woocommerce.js',
    );

    /**
     * List of scripts to be added to header
     *
     * @var array $_scripts List of script
     */
    static private $_scripts = [];

    /**
     * Adds $value if it doesn't already exist in the list of scripts
     */
    static private function add( $value ) {
        if ( !in_array( $value, self::$_scripts ) ){
            array_push( self::$_scripts, $value );
        }
        return self::$_scripts;
    }

    /**
     * Adds a custom script to the list if $element has a match in $ADDITIONAL_SCRIPT_MAP
     *
     * @var array $element \TwicPics\Element
     */
    static public function handles_special_script( $element )
    {
        foreach (self::$ADDITIONAL_SCRIPT_MAP as $selector => $script) {
            if ( $element-> is( $selector ) ) {
                \TwicPics\Script::add( $script);
            }
        }
    }

    /**
     * Returns the list of scripts to be added to the header
     */
    static public function list() {
        return self::$_scripts;
    }

}
