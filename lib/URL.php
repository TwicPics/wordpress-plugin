<?php

namespace TwicPics;

/**
 * TwicPics URL manipulation trait.
 */

require_once 'http_build_url.php';

trait URL {

    /**
     * Current domain
     *
     * @var string $domain Website domain
     */
    private $domain;

    /**
     * Current request URL
     *
     * @var string $url
     */
    private $url;

    /**
     * Get domain on demand
     */
    private function get_domain() {
        if ( !isset( $this->_domain ) ) {
            $this->_domain =
                ( isset( $_SERVER[ 'HTTPS' ] ) && ( 'on' === $_SERVER[ 'HTTPS' ] ) ? 'https' : 'http' ) .
                    '://' .
                    $_SERVER[ 'HTTP_HOST' ];
        }
        return $this->_domain;
    }

    /**
     * Get url on demand
     */
    private function get_url() {
        if ( !isset( $this->url ) ) {
            $this->url = $this->get_domain() . explode( '?', $_SERVER[ 'REQUEST_URI' ] )[ 0 ];
        }
        return $this->url;
    }

    /**
     * Determines if given url is on the same domain
     *
     * @param string $url Image URL.
     */
    private function is_on_same_domain( $url ) {
        $domain = $this->get_domain();
        return substr( $url, 0, strlen( $domain ) ) === $domain;
    }

    /**
     * Resolves URL
     *
     * @param string $url Image URL (relative or absolute).
     */
    private function resolve_url( $url ) {
        return http_build_url( $this->get_url(), wp_parse_url( $url ) );
    }
}
