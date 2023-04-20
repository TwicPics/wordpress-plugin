<?php
/**
 * TwicPics plugin Script => real-time image optimization
 *
 * @package TwicPics
 */

defined( 'ABSPATH' ) || die( 'ERROR !' );

class TwicPicsScript {
	/**
	 * Set required member variables, functions, actions and filters
	 */
	public function __construct( $options ) {

		/* TwicPics domain */
		if ( isset( $options['user_domain'] ) && ! empty( $options['user_domain'] ) ) {
			$this->_twicpics_user_domain = 'https://' . ( $options['user_domain'] );
		} else {
			return;
		}

		/* TwicPics intrinsic max width of images */
		if ( isset( $options['max_width'] ) && ! empty( $options['max_width'] ) ) {
			$this->_twicpics_max_width = $options['max_width'];
		} else {
			$this->_twicpics_max_width = '2000';
		}

		/* TwicPics resize step */
		if ( isset( $options['step'] ) && ! empty( $options['step'] ) ) {
			$this->_twicpics_step = $options['step'];
		} else {
			$this->_twicpics_max_width = '10';
		}

		/* TwicPics placeholder type */
		if ( isset( $options['placeholder_type'] ) ) {
			$this->_twicpics_placeholder = '/output=' . $options['placeholder_type'];
		} else {
			$this->_twicpics_placeholder = '/output=blank';
		}

		/* Plugins Script blacklist */
		include 'blacklist.php';
		$this->_plugins_blacklist = $plugins_blacklist;

		/* Plugins checklist for placeholders URL dimensions */
		include 'checklist-for-placeholders-url-dimensions.php';
		$this->_plugins_checklist_for_placeholders_url_dimensions = $plugins_checklist_for_placeholders_url_dimensions;

		/* Image attributes to remove */
		$this->_img_attributes_to_remove = array(
			'srcset',
			'sizes',
			'data-object-fit',
			'data-object-position',
		);

		/* URLs management */
		include 'class-twicpics-urls-manager.php';
		$this->_urls_manager = new TwicPicsUrlsManager();
		
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_scripts', 1 );
		$this->add_filter( 'wp_lazy_loading_enabled', 'return_false', 1 );
		$this->add_filter( 'script_loader_tag', 'add_async_defer_to_twicpics_script', 10, 3 );
		$this->add_filter( 'wp_get_attachment_image_attributes', 'image_attributes', PHP_INT_MAX );
		$this->add_filter( 'post_thumbnail_html', 'append_noscript_tag', PHP_INT_MAX );
		$this->add_filter( 'the_content', 'content', PHP_INT_MAX );

		if ( in_array( 'js_composer/js_composer.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			$this->add_filter( 'get_post_metadata', 'js_composer', 10, 3 );
		}
	}

	/**
	 * Provides the ability to replace add_action(s) with "twicpics_" prefix, using the same params as the original add_action() function.
	 * For more information, see: https://developer.wordpress.org/reference/functions/add_action/
	 *
	 * @param      string   $tag             The name of the action to which the $function_to_add is hooked.
	 * @param      function $function_to_add The name of the function you wish to be called.
	 * @param      int      $priority        Used to specify the order in which the functions associated with a particular action are executed.
	 * @param      int      $accepted_args   The number of arguments the function accepts.
	 */
	private function add_action( string $tag, $function_to_add, int $priority = 10, int $accepted_args = 1 ) {
		if ( function_exists( 'twicpics_' . $function_to_add ) ) {
			add_action( $tag, 'twicpics_' . $function_to_add, $priority, $accepted_args );
		} else {
			add_action( $tag, array( $this, $function_to_add ), $priority, $accepted_args );
		}
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
	 * Enqueues the TwicPics JS script
	 */
	public function enqueue_scripts() {
		if ( isset( $this->_twicpics_step ) ) {
			wp_enqueue_script( 'twicpics', $this->_twicpics_user_domain . '/?v1&step=' . $this->_twicpics_step, array(), $ver = null, false );
		} else {
			wp_enqueue_script( 'twicpics', $this->_twicpics_user_domain . '/?v1', array(), $ver = null, false );
		}
	}

	/**
	 * Returns false.
	 *
	 * Useful for returning false to filters easily.
	 *
	 * @return false False.
	 */
	public function return_false() {
		return false;
	}

	/**
	 * Allows TwicPics Script to be loaded in async/defer mode
	 *
	 * @param     string $tag    the script tag.
	 * @param     string $handle the actual handle from enqueued scripts.
	 * @return string the full script src, with async defer attributes
	 */
	public function add_async_defer_to_twicpics_script( $tag, $handle ) {
		if ( 'twicpics' === $handle ) {
			if ( false === stripos( $tag, 'defer' ) ) {
				$tag = str_replace( ' src', ' defer src', $tag );
			}

			if ( false === stripos( $tag, 'async' ) ) {
				$tag = str_replace( '<script ', '<script async ', $tag );
			}
		}
		return $tag;
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
	 * Gets the replacement src depending of the type of lazyload configured
	 *
	 * @param     string     $src    the original (cropped or not) src of the image.
	 * @param     int|string $width  if the width of the image is known.
	 * @param     int|string $height if the height of the image is known.
	 * @return string the replacement src
	 */
	private function get_twicpics_placeholder( $src, $width = '', $height = '' ) {
		$twicpics_base_url  = $this->_twicpics_user_domain . '/' . $src . '?twic=v1';
		$twicpics_max_width = '/max=' . $this->_twicpics_max_width;

		if ( ! empty( $width ) && ! empty( $height ) ) {
			$src = $twicpics_base_url . '/cover=' . $width . ':' . $height . $twicpics_max_width . $this->_twicpics_placeholder;
		} else {
			$src = $twicpics_base_url . $twicpics_max_width . $this->_twicpics_placeholder;
		}

		return $src;
	}

	/**
	 * Checks if the dimensions of the placeholder can be removed from its URL to load the same origin image (placeholder) as the definitive one
	 * 
	 * @param     string $tag The image (placeholder).
	 * @return boolean false if the plugin that displays the placeholder needs the width and the height from the URL to size the definitive image at expected dimensions
	 */
	private function should_placeholder_dimensions_be_removed_from_url( $img_classes ) {
		foreach ( $img_classes as $class ) {
			if ( in_array( $class, $this->_plugins_checklist_for_placeholders_url_dimensions, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Treats image attributes returned by WordPress functions
	 *
	 * @param      array $attributes The image attributes.
	 * @return array treated image attributes
	 */
	public function image_attributes( $attributes ) {

		if ( isset( $attributes['src'] ) ) {
			$img_url = $this->_urls_manager->get_absolute_url( $this->_urls_manager->_website_url, $attributes['src'] );
		}

		if ( ! $this->is_on_same_domain( $img_url ) ) {
			return;
		}

		$attributes['data-twic-src'] = $this->_urls_manager->get_original_size_url( $img_url );

		$width  = '';
		$height = $width;

		/* Get sizing */
		if ( isset( $attributes['width'] ) && isset( $attributes['height'] ) ) {
			if ( 'auto' !== $attributes['width'] && 'auto' !== $attributes['height'] ) {
				/* treat only if both width & height */
				$width  = $attributes['width'];
				$height = $attributes['height'];
			}
		} else {
			/* check by filename */
			preg_match( '/.+\-(\d+)x(\d+)\..+/', $img_url, $sizes );

			if ( isset( $sizes[1] ) && isset( $sizes[2] ) ) {
				$width  = $sizes[1];
				$height = $sizes[2];
			} else {
				$file = str_replace( content_url(), WP_CONTENT_DIR, $img_url );

				if ( file_exists( $file ) ) {
					$sizes = getimagesize( $file );

					if ( isset( $sizes[0] ) && isset( $sizes[1] ) ) {
						$width  = $sizes[0];
						$height = $sizes[1];
					}
				}
			}
		}

		if ( $width && $height ) {
			$attributes['data-twic-src-transform'] = "cover={$width}:{$height}/*/max={$this->_twicpics_max_width}";
		} else {
			$attributes['data-twic-src-transform'] = "*/max={$this->_twicpics_max_width}";
		}

		if ( isset( $attributes['data-object-position'] ) ) {
			$focus_coordinates = $this->treat_focus_coordinates( $attributes['data-object-position'] );

			if ( '' !== $focus_coordinates ) {
				$attributes['data-twic-focus'] = $focus_coordinates;
			}
		}

		foreach ( $this->_img_attributes_to_remove as $attr ) {
			unset( $attributes[ $attr ] );
		}

		$img_classes = explode( ' ', $attributes['class'] );

		/* WooCommerce product image preview */
		if ( in_array( 'attachment-266x266', $img_classes, true ) ) {
			$attributes['src'] = $this->_user_domain . $img_url;
		} else {
			/* LQIP */
			$attributes['src'] = $this->get_twicpics_placeholder(
				$this->should_placeholder_dimensions_be_removed_from_url( $img_classes ) ? $this->_urls_manager->get_original_size_url( $img_url ) : $img_url,
				$width,
				$height
			);
		}

		return $attributes;
	}

	/**
	 * Treats get_post_thumbnail html return to append a noscript tag
	 *
	 * @param      string $html The img tag html content.
	 * @return string the $imgtag with noscript appended
	 */
	public function append_noscript_tag( $html ) {
		if ( empty( $html ) ) {
			return $html;
		}
		$dom      = new DOMDocument();
		$noscript = '';
		libxml_use_internal_errors( true );
		$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
		libxml_clear_errors();

		$img = $dom->getElementsByTagName( 'img' )->item( 0 );
		if ( $img ) {
			$noscript = '<noscript><img src="' . $img->getAttribute( 'data-twic-src' ) . '" alt="' . $img->getAttribute( 'alt' ) . '" ></noscript>';
		}
		return $html . $noscript;
	}

	/**
	 * Treats WordPress content
	 *
	 * @param      string $original_content The original content.
	 * @return string the treated content
	 */
	public function content( $original_content ) {
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
	 * Adds noscript for image tags
	 *
	 * @param      DOMNode     $img The img tag node.
	 * @param      DOMDocument $dom The dom document.
	 */
	private function add_noscript_tag( &$img, &$dom ) {
		$noscript   = $dom->createElement( 'noscript' );
		$img_cloned = $dom->createElement( 'img' );
		$img_src    = explode( '?', $img->getAttribute( 'src' ) )[0];
		$img_cloned->setAttribute( 'src', ( str_replace( ( $this->_twicpics_user_domain . '/' ), '', $img_src ) ) );
		$img_cloned->setAttribute( 'alt', $img->getAttribute( 'alt' ) );
		$noscript->appendChild( $img_cloned );
		// phpcs:ignore
		$img->parentNode->appendChild( $noscript );
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

		$img_url = $this->_urls_manager->get_absolute_url( $this->_urls_manager->_website_url, $img->getAttribute( 'src' ) );

		if ( ! $this->is_on_same_domain( $img_url ) ) {
			return;
		}

		// phpcs:ignore
		if ( 'noscript' === $img->parentNode->tagName ) {
			return;
		}

		/* TwicPics Script 'data-twic-src' attribute */
		$img->setAttribute( 'data-twic-src', preg_replace( '/^https?:\/\/[^\/]+/', '', $this->_urls_manager->get_original_size_url( $img_url ) ) );

		$width  = '';
		$height = $width;

		/* Get sizing */
		if ( $img->getAttribute( 'width' ) && $img->getAttribute( 'height' ) ) {
			if ( 'auto' !== $img->getAttribute( 'width' ) && 'auto' !== $img->getAttribute( 'height' ) ) {
				/* with both width & height */
				$width  = $img->getAttribute( 'width' );
				$height = $img->getAttribute( 'height' );
			}
		} else {
			/* with filename */
			preg_match( '/.+\-(\d+)x(\d+)\..+/', $img_url, $sizes );

			if ( isset( $sizes[1] ) && isset( $sizes[2] ) ) {
				$width  = $sizes[1];
				$height = $sizes[2];
			} else {
				$file = str_replace( content_url(), WP_CONTENT_DIR, $img_url );
				if ( file_exists( $file ) ) {
					$sizes = getimagesize( $file );
					if ( isset( $sizes[0] ) && isset( $sizes[1] ) ) {
						$width  = $sizes[0];
						$height = $sizes[1];
					}
				}
			}
		}

		if ( $width && $height ) {
			$img->setAttribute( 'data-twic-src-transform', "cover={$width}:{$height}/*/max={$this->_twicpics_max_width}" );
		} else {
			$img->setAttribute( 'data-twic-src-transform', "*/max={$this->_twicpics_max_width}" );
		}

		$img_object_position = $img->getAttribute( 'data-object-position' );
		$focus_coordinates   = $this->treat_focus_coordinates( $img_object_position );

		if ( '' !== $focus_coordinates ) {
				$img->setAttribute( 'data-twic-focus', "{$focus_coordinates}" );
		}

		foreach ( $this->_img_attributes_to_remove as $attr ) {
			$img->removeAttribute( $attr );
		}

		$img_classes = explode( ' ', $img->getAttribute( 'class' ) );

		/* LQIP */
		$img->setAttribute( 'src', $this->get_twicpics_placeholder(
			$this->should_placeholder_dimensions_be_removed_from_url( $img_classes ) ? $this->_urls_manager->get_original_size_url( $img_url ) : $img_url,
			$width,
			$height
		) );

		/* noscript for SEO */
		$this->add_noscript_tag( $img, $dom );
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
		$styles         = explode( ';', $style_attr );
		$new_style_attr = '';

		foreach ( $styles as $rule ) {
			if ( empty( trim( $rule ) ) ) {
				continue;
			}

			list( $property, $value ) = explode( ':', $rule, 2 );

			switch ( $property ) {
				case 'background':
					if ( strpos( $value, 'url(' ) === false ) {
						$new_style_attr .= $property . ':' . $value . ';';
						break;
					}
					if ( strpos( $value, ',' ) === false ) {
						$value           = trim( $value );
						$bg_urls         = array( substr( $value, strpos( $value, 'url(' ) + 4, strpos( $value, ')', strpos( $value, 'url(' ) ) - 4 ) );
						$bg_placeholder  = $this->_twicpics_user_domain . '/' . $bg_urls[0];
						$new_style_attr .= $property . ':' . str_replace( $bg_urls[0], $bg_placeholder, $value ) . ';';
					}
					break;

				case 'background-image':
					if ( strpos( $value, 'url(' ) === false ) {
						$new_style_attr .= $property . ':' . $value . ';';
						break;
					}
					if ( strpos( $value, ',' ) === false ) {
						$value = trim( $value );

						if ( "'" === substr( $value, 4, 1 ) || '"' === substr( $value, 4, 1 ) ) {
							if ( "'" === substr( $value, -2, -1 ) || '"' === substr( $value, -2, -1 ) ) {
								/* Removes "url('" and "')". */
								$bg_urls = array( substr( $value, 5, -2 ) );
							} else {
								/* Removes "url('" and ")". */
								$bg_urls = array( substr( $value, 5, -1 ) ); 
							}
						} else {
							/* Removes "url(" and ")". */
							$bg_urls = array( substr( $value, 4, -1 ) ); 
						}

						$bg_url          = $this->_urls_manager->get_original_size_url( $bg_urls[0] ); // removes width and height from the URL
						$bg_placeholder  = $this->_twicpics_user_domain . '/' . $bg_url;
						$new_style_attr .= $property . ':url(' . $bg_placeholder . '?twic=v1/max=' . $this->_twicpics_max_width . $this->_twicpics_placeholder;
					}
					/* else { multiple backgrounds } */
					break;

				case 'background-position':
					$coordinates = explode( ' ', $value );
					$x           = str_replace( '%', '', $coordinates[0] );
					$y           = str_replace( '%', '', $coordinates[1] );
					break;

				default:
					$new_style_attr .= $property . ':' . $value . ';';
			}
		}

		if ( isset( $bg_urls ) && is_array( $bg_urls ) && $this->is_on_same_domain( $bg_urls[0] ) ) {
			$tag->setAttribute( 'style', $new_style_attr );
			$tag->setAttribute( 'data-twic-background', 'url(' . preg_replace( '/^https?:\/\/[^\/]+/', '', $bg_url ) . ')' );
			$tag->setAttribute( 'data-twic-transform', '*/max=' . $this->_twicpics_max_width );

			if ( isset( $x ) && isset( $y ) ) {
				if ( '' !== $x && '' !== $y ) {
					if ( 50 !== $x || 50 !== $y ) {
						$tag->setAttribute( 'data-twic-focus', "{$x}px{$y}p" );
					}
				}
			}
		}
	}

	/**
	 * For Visual Composer, parse the css metadata to extract image urls associated with vc_custom_id and fill an global array
	 *
	 * @param     string $metadata  the metadata value.
	 * @param     int    $object_id (not used) the post_id.
	 * @param     string $meta_key  the metakey associated to the metadata value.
	 * @return string the unmodified metadata
	 */
	public function js_composer( $metadata, $object_id, $meta_key ) {
		if ( ( '_wpb_shortcodes_custom_css' !== $meta_key && '_wpb_post_custom_css' !== $meta_key ) || empty( $metadata ) ) {
			return $metadata;
		}
		switch ( $meta_key ) {
			case '_wpb_post_custom_css':
				break;
			case '_wpb_shortcodes_custom_css':
				global $vc_bg;
				if ( ! is_array( $vc_bg ) ) {
					$vc_bg = array();
				}

				preg_match_all( '/\.(vc_custom_\d+)\{background-image:\s?url\((.*)\)/', $metadata, $output_array );

				if ( ! empty( $output_array[1] ) ) {
					$vc_bg = array_merge( $vc_bg, array_combine( $output_array[1], $output_array[2] ) );
				}
				break;
		}
		return $metadata;
	}
}
