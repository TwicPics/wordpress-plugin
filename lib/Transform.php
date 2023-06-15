<?php

namespace TwicPics;

/**
 * Class representing a transformation
 */
class Transform {
    /**
     * transformations after
     */
    private $_after = [];
    /**
     * transformations before
     */
    private $_before = [];
    /**
     * builds a single transformation
     */
    static public function build( $data ) {
        if ( count( $data ) > 1 ) {
            return $data[ 0 ] . '=' . $data[ 1 ];
        }
        return $data[ 0 ];
    }
    /**
     * Adds transformation after
     */
    public function after( ...$data ) {
        array_push( $this->_after, self::build( $data ) );
        return $this;
    }
    /**
     * Adds transformation before
     */
    public function before( ...$data ) {
        array_push( $this->_before, self::build( $data ) );
        return $this;
    }
    /**
     * Is this transform empty?
     */
    public function is_empty() {
        return !count( $this->_after ) && !count( $this->_before );
    }
    /**
     * Gets as a string
     */
    public function as_string( ...$inside ) {
        return implode( '/', array_merge( $this->_before, $inside, $this->_after ) );
    }
}
