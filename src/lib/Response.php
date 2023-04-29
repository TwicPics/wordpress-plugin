<?php

namespace TwicPics;

/**
 * TwicPics plugin PHP response utilities.
 */
class Response {

    /**
     * Returns the first capture group for the regexp
     * if the capture group is empty but the regexp matches, this will be an empty string
     * if the regexp does not match, it will be null
     *
     * @param string $regexp The pattern to search for.
     * @param string $expression The input string.
     */
    static private function get_first_capture( $regexp, $expression ) {
        $matches = [];
        return preg_match( $regexp, $expression, $matches ) ? $matches[ 1 ] : null;
    }

    /**
     * Returns text encoding if response is actually HTML, returns null otherwise
     */
    static public function get_html_encoding() {

        // regexp that captures modifiers of an HTML content-type.
        static $R_CONTENT_TYPE = '/^\s*content-type\s*:\s*(text\/html|application\/xhtml\+xml)\s*(?:;(.+))$/i';

        // regexp to extract encoding from modifiers.
        static $R_ENCODING = '/(?:;|^)\s*encoding=\s*(\S+)\s*(?:;|$)/i';

        // searches for modifiers.
        $modifiers = null;
        foreach ( headers_list() as $header ) {
            $tmp = self::get_first_capture( $R_CONTENT_TYPE, $header );
            if ( null !== $tmp ) {
                $modifiers = $tmp;
            }
        }

        // if there is no modifier, then this is not HTML.
        if ( null === $modifiers ) {
            return null;
        }

        // tries and extracts encoding from modifiers.
        $encoding = self::get_first_capture( $R_ENCODING, $modifiers );

        // if there is no encoding, assume utf-8
        // else uppercase it.
        if ( null === $encoding ) {
            $encoding = 'UTF-8';
        } else {
            $encoding = strtoupper( $encoding );
        }

        // we have the encoding.
        return $encoding;
    }

    /**
     * Determines if we're supposed to handle this kind of response (based on status codes).
     */
    static public function status_code_supported() {

        $status_code = http_response_code();

        return ( 200 === $status_code ) || ( $status_code >= 400 );
    }
}
