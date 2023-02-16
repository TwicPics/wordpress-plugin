<?php
/**
 * TwicPics plugin API
 *
 * @package TwicPics
 */

defined( 'ABSPATH' ) || die( 'ERROR !' );

class TwicPicsApi {
	/**
	 * Set required member variables, functions, actions and filters
	 */
	public function __construct( $options ) {

		/* TwicPics domain */
		if ( ! empty( $options['user_domain'] ) ) {
			$this->_twicpics_user_domain = 'https://' . ( $options['user_domain'] );
		} else {
			return;
		}

		/* Intrinsic max width of images */
		if ( ! empty( $options['max_width'] ) ) {
			$this->_twicpics_max_width = $options['max_width'];
		} else {
			$this->_twicpics_max_width = '2000';
		}

		/* Plugins API blacklist */
		include 'blacklist-api.php';
		$this->_plugins_blacklist = $plugins_blacklist;

		/* URLs management */
		include 'class-twicpics-urls-manager.php';
		$this->_urls_manager = new TwicPicsUrlsManager();

		$this->add_filter( 'wp_get_attachment_image_attributes', 'image_attributes', PHP_INT_MAX );
		$this->add_filter( 'the_content', 'page_content', PHP_INT_MAX );
	}

	/**
	 * Provides the ability to replace add_filter(s) with "twicpics_" prefix, using the same params as the original add_filter() function.
	 * For more information, see: https://developer.wordpress.org/reference/functions/add_action/
	 *
	 * @param      string   $tag             The name of the filter to hook the $function_to_add callback to.
	 * @param      function $function_to_add The callback to be run when the filter is applied.
	 * @param      int      $priority        Used to specify the order in which the functions associated with a particular action are executed.
	 * @param      int      $accepted_args   The number of arguments the function accepts.
	 */
	private function add_filter( string $tag, $function_to_add, int $priority = 10, int $accepted_args = 1 ) {
		if ( function_exists( 'twicpics_' . $function_to_add ) ) {
			add_filter( $tag, 'twicpics_' . $function_to_add, $priority, $accepted_args );
		} else {
			add_filter( $tag, array( $this, $function_to_add ), $priority, $accepted_args );
		}
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
	 * Checks if the image (<img> or background type) is handled by an uncompatible plugin.
	 *
	 * @param      string $tag The image.
	 * @return boolean true if image's parent is marked with a blacklisted plugin class.
	 */
	private function is_blacklisted( $tag ) {
		// phpcs:ignore
		$parent_node = $tag->parentNode;

		// phpcs:ignore
		while ( 'html' !== $parent_node->tagName ) {
			$parent_node_classes = explode( ' ', $parent_node->getAttribute( 'class' ) );

			foreach ( $parent_node_classes as $class ) {
				if ( in_array( $class, $this->_plugins_blacklist, true ) ) {
					return true;
				}
			}

			// phpcs:ignore
			$parent_node = $parent_node->parentNode;
		}
	}

	/**
	 * Checks if an URL is on the same domain
	 *
	 * @param     string $url_to_check the url to check.
	 * @return boolean true if on same domain, false otherwise.
	 */
	private function is_on_same_domain( $url_to_check ) {
		preg_match( '/:\/\/([^\/?#]*)/', get_bloginfo( 'url' ), $domain );
		preg_match( '/:\/\/([^\/?#]*)/', $url_to_check, $url );
		return $domain[1] === $url[1];
	}

	/**
	 * Gets image dimensions <width>x<height> from its URL
	 * 
	 * @param string $img_url the URL of the image, defined in src or srcset attributes.
	 */
	private function get_img_dimensions_from_url( $img_url ) {
		preg_match( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', $img_url, $matches );

		if ( ! empty( $matches ) ) {
			return substr( $matches[0], 1 );
		} else {
			return;
		}
	}

	/**
	 * Check if 'data-object-fit' image's attribute is defined to get the coordinates of its focus point
	 *
	 * @param      DOMNode     $img The img tag node.
	 * @return string the TwicPics 'focus' transformation
	 */
	private function treat_focus_coordinates( $img_object_position ) {
		if ( '' !== $img_object_position ) {
			$coordinates = explode( ' ', $img_object_position );
			$x           = str_replace( '%', '', $coordinates[0] );
			$y           = str_replace( '%', '', $coordinates[1] );

			if ( '' !== $x && '' !== $y ) {
				return "{$x}px{$y}p";
			}

			return '';
		}

		return '';
	}

	/**
	 * Treats image attributes returned by WordPress functions
	 *
	 * @param      array $attributes The image attributes.
	 * @return array treated image attributes
	 */
	public function image_attributes( $attributes ) {

		if ( isset( $attributes['src'] ) ) {
			$img_src = $this->_urls_manager->get_absolute_url( $this->_urls_manager->_website_url, $attributes['src'] );
		}

		if ( ! $this->is_on_same_domain( $img_src ) ) {
			return;
		}

		/* TwicPics src*/
		if ( isset( $attributes['width'] ) ) {
			$img_width = $attributes['width'];
		}
		if ( isset( $attributes['height'] ) ) {
			$img_height = $attributes['height'];
		}

		if ( ! empty( $img_width ) && ! empty( $img_height ) ) {
				$twicpics_src =
					$this->_twicpics_user_domain . '/' . 
					$this->_urls_manager->get_original_size_url( $img_src ) . 
					'?twic=v1/cover=' . $img_width . ':' . $img_height . '/max=' . $this->_twicpics_max_width;
		} else {
			$img_dimensions_from_url = $this->get_img_dimensions_from_url( $img_src );

			if ( ! empty( $img_dimensions_from_url ) ) {
				list( $img_width_from_url, $img_height_from_url ) = explode( 'x', $img_dimensions_from_url, 2 );

				$twicpics_src =
					$this->_twicpics_user_domain . '/' . 
					$this->_urls_manager->get_original_size_url( $img_src ) . 
					'?twic=v1/cover=' . $img_width_from_url . ':' . $img_height_from_url . 
					'/max=' . $this->_twicpics_max_width;
			} else {
				$twicpics_src = $this->_twicpics_user_domain . '/' . $img_src;
			}
		}

		$attributes['src'] = $twicpics_src;
		
		/* TwicPics srcset */
		$img_srcset = $attributes['srcset'];

		if ( ! empty( $img_srcset ) ) {
			$twicpics_srcset = '';
			$img_variants    = explode( ', ', $img_srcset );

			foreach ( $img_variants as $img_variant ) {
				$img_variant_items = explode( ' ', $img_variant );

				if ( ! empty( $img_width ) && ! empty( $img_height ) ) {
					$twicpics_srcset =
						$twicpics_srcset . $this->_twicpics_user_domain . '/' . 
						$this->_urls_manager->get_original_size_url( $img_variant_items[0] ) . 
						'?twic=v1/cover=' . $img_width . ':' . $img_height . '/resize=' . substr( $img_variant_items[1], 0, -1 ) . '/max=' . $this->_twicpics_max_width .
						' ' . $img_variant_items[1] . ', ';
				} else {
					$img_dimensions_from_url = $this->get_img_dimensions_from_url( $img_variant_items[0] );

					if ( ! empty( $img_dimensions_from_url ) ) {
						list( $img_width_from_url, $img_height_from_url ) = explode( 'x', $img_dimensions_from_url, 2 );

						$twicpics_srcset =
							$twicpics_srcset . $this->_twicpics_user_domain . '/' . 
							$this->_urls_manager->get_original_size_url( $img_variant_items[0] ) . 
							'?twic=v1/cover=' . $img_width_from_url . ':' . $img_height_from_url . 
							'/resize=' . $img_width_from_url . '/max=' . $this->_twicpics_max_width .
							' ' . $img_variant_items[1] . ', ';
					} else {
						$twicpics_srcset = 
							$twicpics_srcset . $this->_twicpics_user_domain . '/' . $img_variant_items[0] . 
							'?twic=v1/resize=' . substr( $img_variant_items[1], 0, -1 ) . '/max=' . $this->_twicpics_max_width .
							' ' . $img_variant_items[1] . ', ';
					}
				}
			}

			$attributes['srcset'] = $twicpics_srcset;
		}

		return $attributes;
	}

	/**
	 * Treat dom node img tag
	 *
	 * @param      DOMNode     $img The img tag node.
	 * @param      DOMDocument $dom The dom document.
	 * @return void
	 */
	private function treat_imgtag( &$img, &$dom ) {
		if ( $this->is_blacklisted( $img ) ) {
			return;
		};

		$img_src = $this->_urls_manager->get_absolute_url( $this->_urls_manager->_website_url, $img->getAttribute( 'src' ) );

		/* relative path */
		if ( strpos( $img_src, '/' ) === 0 ) {
			$img_src = home_url( $img_src );
		}
		if ( strpos( $img_src, 'http' ) === false ) {
			return;
		}
		if ( ! $this->is_on_same_domain( $img_src ) ) {
			return;
		}

		$img_width  = $img->getAttribute( 'width' );
		$img_height = $img->getAttribute( 'height' );

		/* TwicPics src */
		if ( ! empty( $img_width ) && ! empty( $img_height ) ) {
				$twicpics_src =
					$this->_twicpics_user_domain . '/' . 
					$this->_urls_manager->get_original_size_url( $img_src ) . 
					'?twic=v1/cover=' . $img_width . ':' . $img_height . '/max=' . $this->_twicpics_max_width;
		} else {
			$img_dimensions_from_url = $this->get_img_dimensions_from_url( $img_src );

			if ( ! empty( $img_dimensions_from_url ) ) {
				list( $img_width_from_url, $img_height_from_url ) = explode( 'x', $img_dimensions_from_url, 2 );

				$twicpics_src =
					$this->_twicpics_user_domain . '/' . 
					$this->_urls_manager->get_original_size_url( $img_src ) . 
					'?twic=v1/cover=' . $img_width_from_url . ':' . $img_height_from_url . 
					'/max=' . $this->_twicpics_max_width;
			} else {
				$twicpics_src = $this->_twicpics_user_domain . '/' . $img_src;
			}
		}

		$img->setAttribute( 'src', $twicpics_src );

		$img_srcset = $img->getAttribute( 'srcset' );
		
		/* TwicPics srcset */ 
		if ( ! empty( $img_srcset ) ) {
			$twicpics_srcset = '';
			$img_variants    = explode( ', ', $img_srcset );

			foreach ( $img_variants as $img_variant ) {
				$img_variant_items = explode( ' ', $img_variant );

				if ( ! empty( $img_width ) && ! empty( $img_height ) ) {
					$twicpics_srcset =
						$twicpics_srcset . $this->_twicpics_user_domain . '/' . 
						$this->_urls_manager->get_original_size_url( $img_variant_items[0] ) . 
						'?twic=v1/cover=' . $img_width . ':' . $img_height . '/resize=' . substr( $img_variant_items[1], 0, -1 ) . '/max=' . $this->_twicpics_max_width .
						' ' . $img_variant_items[1] . ', ';
				} else {
					$img_dimensions_from_url = $this->get_img_dimensions_from_url( $img_variant_items[0] );

					if ( ! empty( $img_dimensions_from_url ) ) {
						list( $img_width_from_url, $img_height_from_url ) = explode( 'x', $img_dimensions_from_url, 2 );

						$twicpics_srcset =
							$twicpics_srcset . $this->_twicpics_user_domain . '/' . 
							$this->_urls_manager->get_original_size_url( $img_variant_items[0] ) . 
							'?twic=v1/cover=' . $img_width_from_url . ':' . $img_height_from_url . 
							'/resize=' . $img_width_from_url . '/max=' . $this->_twicpics_max_width .
							' ' . $img_variant_items[1] . ', ';
					} else {
						$twicpics_srcset = 
							$twicpics_srcset . $this->_twicpics_user_domain . '/' . $img_variant_items[0] . 
							'?twic=v1/resize=' . substr( $img_variant_items[1], 0, -1 ) . '/max=' . $this->_twicpics_max_width .
							' ' . $img_variant_items[1] . ', ';
					}
				}
			}

			$img->setAttribute( 'srcset', $twicpics_srcset );
		}
	}

	/**
	 * Treats dom node for background
	 *
	 * @param      DOMNode $tag The tag node.
	 * @return void
	 */
	private function treat_tag_for_bg( &$tag ) {
		if ( $this->is_blacklisted( $tag ) ) {
			return;
		}

		$style_attr = $tag->getAttribute( 'style' );

		if ( empty( $style_attr ) || strpos( $style_attr, 'background' ) === false ) {
			return;
		}

		$styles              = explode( ';', $style_attr );
		$new_style_attribute = '';

		foreach ( $styles as $rule ) {
			if ( empty( trim( $rule ) ) ) {
				continue;
			}

			list( $property, $value ) = explode( ':', $rule, 2 );

			switch ( $property ) {
				case 'background':
					if ( strpos( $value, 'url(' ) === false ) {
						$new_style_attribute .= $property . ':' . $value . ';';
						break;
					}
					if ( strpos( $value, ',' ) === false ) {
						$value   = trim( $value );
						$bg_urls = array( substr( $value, strpos( $value, 'url(' ) + 4, strpos( $value, ')', strpos( $value, 'url(' ) ) - 4 ) );
						
						$twicpics_bg_img_url  = $this->_twicpics_user_domain . '/' . $bg_urls[0];
						$new_style_attribute .= $property . ':' . str_replace( $bg_urls[0], $twicpics_bg_img_url, $value ) . ';';
					}
					break;

				case 'background-image':
					if ( strpos( $value, 'url(' ) === false ) {
						$new_style_attribute .= $property . ':' . $value . ';';
						break;
					}
					if ( strpos( $value, ',' ) === false ) {
						$value = trim( $value );

						if ( "'" === substr( $value, 4, 1 ) || '"' === substr( $value, 4, 1 ) ) {
							if ( "'" === substr( $value, -2, -1 ) || '"' === substr( $value, -2, -1 ) ) {
								/* Removes "url('" and "')". */
								$bg_img_url = substr( $value, 5, -2 );
							} else {
								/* Removes "url('" and ")". */
								$bg_img_url = substr( $value, 5, -1 ); 
							}
						} else {
							/* Removes "url(" and ")". */
							$bg_img_url = substr( $value, 4, -1 ); 
						}

						$bg_img_dimensions_from_url = $this->get_img_dimensions_from_url( $bg_img_url );

						if ( ! empty( $bg_img_dimensions_from_url ) ) {
							list( $bg_img_width_from_url, $bg_img_height_from_url ) = explode( 'x', $bg_img_dimensions_from_url, 2 );

							$twicpics_bg_img_url =
								$this->_twicpics_user_domain . '/' . $this->_urls_manager->get_original_size_url( $bg_img_url ) .
								'?twic=v1/cover=' . $bg_img_width_from_url . ':' . $bg_img_height_from_url . 
								'/max=' . $this->_twicpics_max_width;
						} else {
							$twicpics_bg_img_url
								= $this->_twicpics_user_domain . '/' . $bg_img_url;
						}
						
						$new_style_attribute = $property . ':url(' . $twicpics_bg_img_url . ');';
					}
					/* else { multiple backgrounds } */
					break;

				default:
					$new_style_attribute .= $property . ':' . $value . ';';
			}
		}

		if ( isset( $bg_img_url ) && $this->is_on_same_domain( $bg_img_url ) ) {
			$tag->setAttribute( 'style', $new_style_attribute );
		}
	}

	/**
	 * Treats WordPress content
	 *
	 * @param      string $original_content The original content.
	 * @return string the treated content
	 */
	public function page_content( $original_content ) {
		if ( empty( $original_content ) ) {
			return $original_content;
		}

		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( mb_convert_encoding( $original_content, 'HTML-ENTITIES', 'UTF-8' ) );
		libxml_clear_errors();

		/* Treat img tags */
		foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
			$this->treat_imgtag( $img, $dom );
		}

		/* Treat div background style attributes and visual composer class .vc_custom */
		foreach ( $dom->getElementsByTagName( 'div' ) as $div ) {
			/* check class for vc_custom and add style for treatment */
			if ( strpos( $div->getAttribute( 'class' ), 'vc_custom_' ) !== false ) {
				global $vc_bg;
				$classes = explode( ' ', $div->getAttribute( 'class' ) );

				foreach ( $classes as $class ) {
					if ( strpos( $class, 'vc_custom_' ) === false ) {
						continue;
					}
					$style = $div->getAttribute( 'style' );
					/* if no background image (others styles) */
					if ( ! isset( $vc_bg[ $class ] ) ) {
						continue;
					}
					if ( empty( $style ) ) {
						$style = 'background-image:url(' . $vc_bg[ $class ] . ')';
					} else {
						$style .= ';background-image:url(' . $vc_bg[ $class ] . ')';
					}
					$div->setAttribute( 'style', $style );
				}
			}

			$this->treat_tag_for_bg( $div );
		}

		/* Treat figure background style attributes */
		foreach ( $dom->getElementsByTagName( 'figure' ) as $fig ) {
			$this->treat_tag_for_bg( $fig );
		}

		/* Treat span background style attributes */
		foreach ( $dom->getElementsByTagName( 'span' ) as $span ) {
			$this->treat_tag_for_bg( $span );
		}

		/* Return data without doctype and html/body */
		return apply_filters( 'twicpics_the_content_return', substr( $dom->saveHTML( $dom->getElementsByTagName( 'body' )->item( 0 ) ), 6, -7 ), $original_content );
	}
}
