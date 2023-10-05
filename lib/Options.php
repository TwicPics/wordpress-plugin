<?php

namespace TwicPics;

/**
 * TwicPics plugin options handling trait.
 */
class Options {

    /**
     * Default values
     *
     * @var array $default_option_values Default option values
     */
    static private $default_option_values = [
        'alias'              => '/ <host>',
        'max_width'          => 2000,
        'optimization_level' => 'script',
        'placeholder_type'   => 'blank',
        'quality'            => 70,
        'step'               => 10,
        'user_domain'        => '',
    ];

    /**
     * Actual values
     *
     * @var array $_options Options
     */
    static private $_options;

    /**
     * Get values (requests them if needed)
     */
    static private function get_options() {
        if ( !isset( self::$_options ) ) {
            $options        = get_option( 'twicpics_options' );
            self::$_options = array();

            foreach ( self::$default_option_values as $name => $default_value ) {
                $value = null;
                if ( isset( $options[ $name ] ) ) {
                    $value = $options[ $name ];
                    switch ( gettype( $default_value ) ) {
                        case 'boolean': {
                            $value = is_string( $value ) ? ( strtolower( $value ) === 'true' ) : ( ( boolean ) $value );
                            break;
                        }
                        case 'integer': {
                            $value = ( integer ) $value;
                            if ( $value <= 0 ) {
                                $value = null;
                            }
                            break;
                        }
                        case 'string': {
                            $value = trim( ( string ) $value );
                            if ( $value === '' ) {
                                $value = null;
                            }
                            break;
                        }
                        default: {
                            $value = null;
                        }
                    }
                }
                self::$_options[ $name ] = ( $value === null ) ? $default_value : $value;
            }
        }
        // fixes user domain with too much information
        if ( self::$_options[ 'user_domain' ] ) {
            self::$_options[ 'user_domain' ] = preg_replace(
                '#^https?:/+|/+$#',
                '',
                self::$_options[ 'user_domain' ]
            );
        }
        return self::$_options;
    }

    /**
     * Get a value
     *
     * @param string $name           Option name
     * @param mixed  $default_value  Value for when option is set to default
     */
    static public function get( ...$args ) {
        $name = $args[ 0 ];
        $options = self::get_options();
        if ( isset( $options[ $name ] ) ) {
            $value = $options[ $name ];
            if ( ( count( $args ) === 1 ) || ( $value !== self::get_default( $name ) ) ) {
                return $value;
            }
            return $args[ 1 ];
        }
        return null;
    }

    /**
     * Utility to get default values
     *
     * @param string $name Option name.
     */
    static public function get_default( $name ) {
        return isset( self::$default_option_values[ $name ] ) ? self::$default_option_values[ $name ] : null;
    }

    /**
     * Utility to get everything as an object
     */
    static public function get_object() {
        return ( object ) self::get_options();
    }

    /**
     * Determine if an option is set to its default value
     *
     * @param string $name Option name.
     */
    static public function is_default( $name ) {
        return self::get( $name ) === self::get_default( $name );
    }
}
