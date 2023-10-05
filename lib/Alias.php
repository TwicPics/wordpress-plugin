<?php

namespace TwicPics;

require_once 'http_build_url.php';

define(
    "TWICPICS_PAGE_DOMAIN",
    ( isset( $_SERVER[ 'HTTPS' ] ) && ( 'on' === $_SERVER[ 'HTTPS' ] ) ? 'https' : 'http' ) . '://' .
        $_SERVER[ 'HTTP_HOST' ]
);

/**
 * TwicPics alias class
 */
class Alias {

    /**
     * Expression normalizer
     */
    static private function trim_slashes( $expression ) {
        return preg_replace( '/^\/+|\/+$/', '', $expression );
    }

    /**
     * Alias list
     *
     * @var array( string ) $_alias_list the list of aliases
     */
    static private $_alias_list;

    /**
     * Gets / Creates Alias list
     */
    static private function get_alias_list() {
        if ( !isset( self::$_alias_list ) ) {
            self::$_alias_list = [];
            foreach( explode(
                "\n",
                preg_replace( '/<host>/', TWICPICS_PAGE_DOMAIN, \TwicPics\Options::get( 'alias' ) )
            ) as $line ) {
                if ( !empty( ( $line = trim( $line ) ) ) ) {
                    $items = preg_split( '/\s+/', trim( $line ) );
                    $external = ( strtolower( $items[ 0 ] ) === 'x' );
                    if ( $external ) {
                        $items = array_slice( $items, 1 );
                    }
                    if ( count( $items ) === 2 ) {
                        array_push( self::$_alias_list, [
                            'external' => $external,
                            'match'    =>
                                '/^' . preg_quote(
                                    self::trim_slashes(
                                        http_build_url(
                                            $external ? array() : TWICPICS_PAGE_DOMAIN,
                                            wp_parse_url( $items[ 0 ] )
                                        ) ),
                                    '/'
                                ) . '(?=\/)/',
                            'target'   => self::trim_slashes( $items[ 1 ] ),
                        ] );
                    }
                }
            }
        }
        return self::$_alias_list;
    }

    /**
     * Finds the proper alias
     * returns null if no alias found
     */
    static private function get_alias_item( $url ) {
        foreach ( self::get_alias_list() as $alias ) {
            if ( preg_match( $alias[ 'match' ], $url ) ) {
                return $alias;
            }
        }
        return null;
    }

    /**
     * Determines if we have no alias
     */
    static public function is_empty() {
        return count( self::get_alias_list() ) === 0;
    }

    /**
     * alias the string
     * returns null if none found
     */
    static public function resolve( $url ) {
        static $R_DIMENSIONS = '/-(\d+)x(\d+)(?=\.[a-z0-9]*$)/i';
        $url = http_build_url( $url );
        $alias_item = self::get_alias_item( $url );
        if ( empty( $alias_item ) ) {
            return null;
        }
        // if not external, finds and removes dimensions
        $height = null;
        $width = null;

        if ( !$alias_item[ 'external' ] ) {
            $url = explode( '?', $url );
            $matches = [];
            if ( preg_match( $R_DIMENSIONS, $url[ 0 ], $matches ) ) {
                $width = ( int ) $matches[ 1 ];
                $height = ( int ) $matches[ 2 ];
                $url[ 0 ] = preg_replace( $R_DIMENSIONS, '', $url[ 0 ] );
            }
            $url = implode( '?', $url );
        }
        $url = preg_replace( $alias_item[ 'match' ], $alias_item[ 'target' ], $url );
        return new \TwicPics\Item( self::trim_slashes( $url ), $width, $height );
    }
}
