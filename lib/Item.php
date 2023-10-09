<?php

namespace TwicPics;

/**
 * Path Item
 */
class Item {
    /**
     * height
     */
    public $height;

    /**
     * path
     */
    public $raw_path;

    /**
     * width
     */
    public $width;

    /**
     * constructor
     */
    public function __construct( $path, $width, $height ) {
        $this->raw_path  = $path;
        $this->height    = $height;
        $this->width     = $width;
    }

    /**
     * Get default transformation
     */
    public function get_default_transformation() {
        if ( $this->width ) {
            if ( $this->height ) {
                return 'cover=' . $this->width . 'x' . $this->height;
            }
            return 'resize=' . $this->width;
        }
        return null;
    }

    /**
     * Get path as a usable HTML attribute
     */
    public function get_path() {
        static $R_HTTP = '/^https?:\/\//';
        return preg_match( $R_HTTP, $this->raw_path ) ? $this->raw_path : ( 'media:' . $this->raw_path );
    }

    /**
     * Get a TwicPics URL for a given domain with an eventual transformation (as a string)
     */
    public function get_url( $domain, $transformation ) {
        return 'https://' . $domain . '/' . $this->raw_path . (
            empty( $transformation ) ?
                '' :
                ( '?twic=v1/' . $transformation )
        );
    }

    public function has_size() {
        return $this->width !== null || $this->height !== null;
    }
}
