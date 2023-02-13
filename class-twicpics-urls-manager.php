<?php
/**
 * TwicPics URLs Manager
 *
 * @package TwicPics
 */

defined( 'ABSPATH' ) || die( 'ERROR !' );

/**
 * URL constants as defined in the PHP Manual under "Constants usable with
 * http_build_url()".
 *
 * @see http://us2.php.net/manual/en/http.constants.php#http.constants.url
 */
if ( ! defined( 'HTTP_URL_REPLACE' ) ) {
	define( 'HTTP_URL_REPLACE', 1 );
}
if ( ! defined( 'HTTP_URL_JOIN_PATH' ) ) {
	define( 'HTTP_URL_JOIN_PATH', 2 );
}
if ( ! defined( 'HTTP_URL_JOIN_QUERY' ) ) {
	define( 'HTTP_URL_JOIN_QUERY', 4 );
}
if ( ! defined( 'HTTP_URL_STRIP_USER' ) ) {
	define( 'HTTP_URL_STRIP_USER', 8 );
}
if ( ! defined( 'HTTP_URL_STRIP_PASS' ) ) {
	define( 'HTTP_URL_STRIP_PASS', 16 );
}
if ( ! defined( 'HTTP_URL_STRIP_AUTH' ) ) {
	define( 'HTTP_URL_STRIP_AUTH', 32 );
}
if ( ! defined( 'HTTP_URL_STRIP_PORT' ) ) {
	define( 'HTTP_URL_STRIP_PORT', 64 );
}
if ( ! defined( 'HTTP_URL_STRIP_PATH' ) ) {
	define( 'HTTP_URL_STRIP_PATH', 128 );
}
if ( ! defined( 'HTTP_URL_STRIP_QUERY' ) ) {
	define( 'HTTP_URL_STRIP_QUERY', 256 );
}
if ( ! defined( 'HTTP_URL_STRIP_FRAGMENT' ) ) {
	define( 'HTTP_URL_STRIP_FRAGMENT', 512 );
}
if ( ! defined( 'HTTP_URL_STRIP_ALL' ) ) {
	define( 'HTTP_URL_STRIP_ALL', 1024 );
}

class TwicPicsUrlsManager {

	public function __construct() {
		$this->_website_url = ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	}

	/**
	 * Provides the ability to write logs in JavaScript.
	 * 
	 * @param      string $log The log to write
	 */
	private function write_logs( $log ) {
		echo '<script type="text/javascript">console.log( "twicpics",' . wp_json_encode( $log ) . ');</script>';
	}

	/**
	 * Gets the original size URL of the image by simply removing the -{width}x{height} added by WordPress
	 *
	 * @param      string $img_url the URL of the originaly requested image (maybe cropped).
	 * @return string the full size URL of the image
	 */
	public function get_original_size_url( $img_url ) {
		return preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $img_url );

		// global $wpdb;
		// $base_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $img_url );
		// $image    = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s OR guid=%s;", $base_url, $img_url ) );

		// if ( ! empty( $image ) ) {
		// 	return wp_get_attachment_image_src( (int) $image[0], 'full' )[0];
		// } else {
		// 	return preg_replace( '/(.+)\-\d+x\d+.*(\..+)/', '$1$2', $img_url );
		// }
	}

	/**
	 * Handle relative paths.
	 * 
	 * @param      $website_url The URL of the website
	 * @param      $img_url The URL of the image
	 */
	public function get_absolute_url( $website_url, $img_url ) {
		$url_parts = wp_parse_url( $img_url );
		return $this->http_build_url( explode( '?', $website_url )[0], $url_parts );
	}

	/**
	 * Build a URL.
	 *
	 * The parts of the second URL will be merged into the first according to
	 * the flags argument.
	 *
	 * @param mixed $url     (part(s) of) an URL in form of a string or
	 *                       associative array like parse_url() returns
	 * @param mixed $parts   same as the first argument
	 * @param int   $flags   a bitmask of binary or'ed HTTP_URL constants;
	 *                       HTTP_URL_REPLACE is the default
	 * @param array $new_url if set, it will be filled with the parts of the
	 *                       composed url like parse_url() would return
	 * @return string
	 */
	private function http_build_url( $url, $parts = array(), $flags = HTTP_URL_REPLACE, &$new_url = array() ) {
		// Initialization
		static $all_keys = array( 'scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment' );
		static $all_keys_flipped;

		static $server_https;
		static $default_host;
		static $request_uri;
		static $request_uri_no_query;
		static $request_uri_path;

		if ( ! isset( $all_keys_flipped ) ) {
				$all_keys_flipped = array_flip( $all_keys );
		}

		if ( ! isset( $server_https ) ) {
				$server_https = ! empty( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) === 'on';
		}

		if ( ! isset( $default_host ) ) {
			// Avoid this autodetection, it is copy-exact from C code, but $_SERVER['HTTP_HOST'] is vulnerable.
			$default_host =
					isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST']
					: ( isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME']: '' );

			if ( '' === $default_host ) {
					$default_host = function_exists( 'gethostname' ) ? gethostname() : php_uname( 'n' );
			}
		}

		if ( ! isset( $request_uri ) ) {
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$request_uri = $_SERVER['REQUEST_URI'];
			} else {
				$request_uri = '/';
			}
		}

		if ( ! isset( $request_uri_no_query ) ) {
				$request_uri_no_query = preg_replace( '~^([^\?]*).*$~', '$1', $request_uri );
		}

		if ( ! isset( $request_uri_path ) ) {
				$request_uri_path = substr( $request_uri_no_query, 0, strrpos( $request_uri_no_query, '/' ) + 1 );
		}

		// Translate the flags from the single input parameter.
		$JOIN_PATH      = ( ( $flags | HTTP_URL_JOIN_PATH ) === $flags );
		$JOIN_QUERY     = ( ( $flags | HTTP_URL_JOIN_QUERY ) === $flags );
		$STRIP_USER     = ( ( $flags | HTTP_URL_STRIP_USER ) === $flags );
		$STRIP_PASS     = ( ( $flags | HTTP_URL_STRIP_PASS ) === $flags );
		$STRIP_PORT     = ( ( $flags | HTTP_URL_STRIP_PORT ) === $flags );
		$STRIP_PATH     = ( ( $flags | HTTP_URL_STRIP_PATH ) === $flags );
		$STRIP_QUERY    = ( ( $flags | HTTP_URL_STRIP_QUERY ) === $flags );
		$STRIP_FRAGMENT = ( ( $flags | HTTP_URL_STRIP_FRAGMENT ) === $flags );

		// Parse and validate the input URLs.
		if ( ! is_array( $url ) ) {
				$url = wp_parse_url( $url );
		}
		if ( ! is_array( $parts ) ) {
				$parts = wp_parse_url( $parts );
		}

		$url   = array_intersect_key( $url, $all_keys_flipped );
		$parts = array_intersect_key( $parts, $all_keys_flipped );

		// Unfortunately the 'query' part can not be an array or object type.
		if ( isset( $url['query'] ) && ! is_string( $url['query'] ) ) {
				unset( $url['query'] );
		}

		// Unfortunately the 'query' part can not be an array or object type.
		if ( isset( $parts['query'] ) && ! is_string( $parts['query'] ) ) {
				unset( $parts['query'] );
		}

		foreach ( $all_keys as $key ) {

			if ( 'port' === $key ) {
				if ( isset( $url[ $key ] ) ) {
					$url[ $key ] = (int) $url[ $key ];

					if ( $url[ $key ] <= 0 || $url[ $key ] >= 65535 ) {
						unset( $url[ $key ] );
					}
				}

				if ( isset( $parts[ $key ] ) ) {
					$parts[ $key ] = (int) $parts[ $key ];

					if ( $parts[ $key ] <= 0 || $parts[ $key ] >= 65535 ) {
						unset( $parts[ $key ] );
					}
				}
			} else {
				if ( isset( $url[ $key ] ) ) {

					if ( is_array( $url[ $key ] ) ) {
						if ( empty( $url[ $key ] ) ) {
							unset( $url[ $key ] );
						}
					} else {
							$url[ $key ] = (string) $url[ $key ];

							if ( $url[ $key ] == '' ) {
									unset( $url[ $key ] );
							}
					}
				}

				if ( isset( $parts[ $key ] ) ) {
					if ( is_array( $parts[ $key ] ) ) {
						if ( empty( $parts[ $key ] ) ) {
							unset( $parts[ $key ] );
						}
					} else {
						$parts[ $key ] = (string) $parts[ $key ];

						if ( '' === $parts[ $key ] ) {
							unset( $parts[ $key ] );
						}
					}
				}
			}
		}

		// Start building the result.

		// Port

		if ( $STRIP_PORT ) {
			if ( isset( $url['port'] ) ) {
					unset( $url['port'] );
			}
		} else {
			if ( isset( $parts['port'] ) ) {
				$url['port'] = $parts['port'];
			}
		}

		// User
		if ( $STRIP_USER ) {
			if ( isset( $url['user'] ) ) {
				unset( $url['user'] );
			}
		} else {
			if ( isset( $parts['user'] ) ) {
				$url['user'] = $parts['user'];
			}
		}

		// Password
		if ( $STRIP_PASS || ! isset( $url['user'] ) ) {
			if ( isset( $url['pass'] ) ) {
					unset( $url['pass'] );
			}
		} else {
			if ( isset( $parts['pass'] ) ) {
					$url['pass'] = $parts['pass'];
			}
		}

		// Scheme
		if ( isset( $parts['scheme'] ) ) {
			$url['scheme'] = $parts['scheme'];
		}

		// Host
		if ( isset( $parts['host'] ) ) {
			$url['host'] = $parts['host'];
		}

		// Path
		if ( $STRIP_PATH ) {
			if ( isset( $url['path'] ) ) {
				unset( $url['path'] );
			}
		} else {
			if ( $JOIN_PATH && isset( $parts['path'] ) && isset( $url['path'] ) && substr( $parts['path'], 0, 1 ) !== '/' ) {

				if ( substr( $url['path'], -1, 1 ) != '/' ) {
					$base_path = str_replace( '\\', '/', dirname( $url['path'] ) );
				} else {
					$base_path = $url['path'];
				}

				if ( substr( $base_path, -1, 1 ) != '/' ) {
					$base_path .= '/';
				}

				$url['path'] = $base_path . $parts['path'];
			} else {
				if ( isset( $parts['path'] ) ) {
					$url['path'] = $parts['path'];
				}
			}
		}

		// Query
		if ( $STRIP_QUERY ) {
			if ( isset( $url['query'] ) ) {
					unset( $url['query'] );
			}
		} else {
			if ( $JOIN_QUERY && isset( $url['query'] ) && isset( $parts['query'] ) ) {
				$u_query = $url['query'];
				$p_query = $parts['query'];

				if ( ! is_array( $u_query ) ) {
					parse_str( $u_query, $u_query );
				}
				if ( ! is_array( $p_query ) ) {
					parse_str( $p_query, $p_query );
				}

				$u_query = http_build_str( $u_query );
				$p_query = http_build_str( $p_query );

				$u_query = str_replace( array( '[', '%5B' ), '{{{', $u_query );
				$u_query = str_replace( array( ']', '%5D' ), '}}}', $u_query );

				$p_query = str_replace( array( '[', '%5B' ), '{{{', $p_query );
				$p_query = str_replace( array( ']', '%5D' ), '}}}', $p_query );

				parse_str( $u_query, $u_query );
				parse_str( $p_query, $p_query );

				$query = http_build_str( array_merge( $u_query, $p_query ) );
				$query = str_replace( array( '{{{', '%7B%7B%7B' ), '%5B', $query );
				$query = str_replace( array( '}}}', '%7D%7D%7D' ), '%5D', $query );

				parse_str( $query, $query );

			} else {
				if ( isset( $parts['query'] ) ) {
					$query = $parts['query'];
				}
			}

			if ( isset( $query ) ) {
				if ( is_array( $query ) ) {
					$query = http_build_str( $query );
				}
				$url['query'] = $query;
			}
		}

		if ( isset( $url['query'] ) && is_array( $url['query'] ) ) {
				$url['query'] = http_build_str( $url['query'] );
		}

		// Fragment
		if ( $STRIP_FRAGMENT ) {
			if ( isset( $url['fragment'] ) ) {
				unset( $url['fragment'] );
			}
		} else {
			if ( isset( $parts['fragment'] ) ) {
					$url['fragment'] = $parts['fragment'];
			}
		}

		// Ensure scheme presence.
		if ( ! isset( $url['scheme'] ) ) {
			if ( $server_https ) {
				$url['scheme'] = 'https';
			} elseif ( isset( $url['port'] ) ) {
				if ( getservbyport( $url['port'], 'tcp' ) === $scheme ) {
					$url['scheme'] = $scheme;
				} else {
					$url['scheme'] = 'http';
				}
			} else {
				$url['scheme'] = 'http';
			}
		}

		// Ensure host presence.
		if ( ! isset( $url['host'] ) ) {
			$url['host'] = $default_host;
		}

		// Hide standard ports.
		// http://www.iana.org/assignments/port-numbers
		if ( isset( $url['port'] ) ) {
			if ( (int) getservbyname( $url['scheme'], 'tcp' ) === $url['port'] ) {
				unset( $url['port'] );
			}
		}

		// Ensure path presence.
		if ( $STRIP_PATH ) {
			$url['path'] = '';
		} else {
			if ( ! isset( $url['path'] ) ) {
					$url['path'] = $request_uri_no_query;
			} elseif ( substr( $url['path'], 0, 1 ) != '/' ) {
					// A relative path, deal with it.
					$url['path'] = $request_uri_path . $url['path'];
			}
		}

		// Canonize the result path.
		if ( strpos( $url['path'], './' ) !== false ) {
			$path = explode( '/', $url['path'] );

			$k_stack = array();

			foreach ( $path as $k => $v ) {
				if ( '..' === $v ) {
					if ( $k_stack ) {
						$k_parent = array_pop( $k_stack );
						unset( $path[ $k_parent ] );
					}
					unset( $path[ $k ] );
				} elseif ( '.' === $v ) {
					unset( $path[ $k ] );
				} else {
					$k_stack[] = $k;
				}
			}

			$url['path'] = implode( '/', $path );
		}

		$url['path'] = '/' . ltrim( $url['path'], '/' );

		// The result as an array type is ready.
		$new_url = $url;

		// Build the result string.
		$result = $url['scheme'] . '://';

		if ( isset( $url['user'] ) ) {
			$result .= $url['user'] . ( isset( $url['pass'] ) ? ':' . $url['pass'] : '' ) . '@';
		}

		$result .= $url['host'];

		if ( isset( $url['port'] ) ) {
			$result .= ':' . $url['port'];
		}

		$result .= $url['path'];

		if ( isset( $url['query'] ) ) {
			$result .= '?' . $url['query'];
		}

		if ( isset( $new_url['fragment'] ) ) {
			$result .= '#' . $url['fragment'];
		}

		return $result;
	}
}
