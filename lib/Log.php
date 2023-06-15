<?php

namespace TwicPics;

/**
 * TwicPics plugin in-browser logging trait.
 */
class Log {

    /**
     * Log lines
     *
     * @var array $logs List of logs
     */
    static private $_logs = [];

    /**
     * Print logs to the console
     */
    static public function code() {
        return count( self::$_logs ) ? implode( ';', self::$_logs ) : null;
    }

    /**
     * Push log with eventual level
     */
    static public function log( $data, $level = 'log' ) {
        array_push( self::$_logs, 'console.' . $level . '("TwicPics",' . wp_json_encode( $data ) . ')' );
    }

    /**
     * Push error logs to $logs array
     *
     * @param string $data Data.
     */
    static public function error( $data ) {
        self::log( $data, 'error' );
    }

    /**
     * Push error logs to $logs array
     *
     * @param string $data Data.
     */
    static public function warning( $data ) {
        self::log( $data, 'warn' );
    }
}
