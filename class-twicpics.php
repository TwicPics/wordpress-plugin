<?php

defined( 'ABSPATH' ) || die( 'ERROR !' );

/**
 * The class for TwicPics plugin front-end
 */
class TwicPics {
	public function __construct() {

		$options = get_option( 'twicpics_options' );

		if ( defined( 'TWICPICS_URL' ) ) {
			$this->_user_domain = 'https://' . ( 'TWICPICS_URL' );
		} elseif ( ! empty( $options['user_domain'] ) ) {
			$this->_user_domain = 'https://' . ( $options['user_domain'] );
		}

		if ( empty( $this->_user_domain ) ) {
			return;
		}

		/* Plugins blacklist */
		include 'blacklist.php';
		$this->_plugins_blacklist = $plugins_blacklist;

		/* Images' max width */
		if ( ! empty( $options['max_width'] ) ) {
			$this->_max_width = $options['max_width'];
		} else {
			$this->_max_width = '2000';
		}

		var_dump( $this->_max_width );

		/* Placeholder config */
		$this->_lazyload = defined( 'TWICPICS_LAZYLOAD_TYPE' ) ? TWICPICS_LAZYLOAD_TYPE : 'preview_placeholder';

		/* Conf (colors or percent) depending on lazyload type */
		$this->_lazyload_conf = defined( 'TWICPICS_LAZYLOAD_CONF' ) ? TWICPICS_LAZYLOAD_CONF : $this->get_lazyload_conf();

		$this->add_action( 'wp_enqueue_scripts', 'enqueue_scripts', 1 );
		$this->add_filter( 'wp_lazy_loading_enabled', '__return_false', 1 );
		$this->add_filter( 'script_loader_tag', 'add_async_defer_to_twicpics_script', 10, 3 );
		$this->add_filter( 'wp_get_attachment_image_attributes', 'image_attributes', 99 );
		$this->add_filter( 'post_thumbnail_html', 'append_noscript_tag', 99 );
		$this->add_filter( 'the_content', 'content', 99 );

		if ( in_array( 'js_composer/js_composer.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			$this->add_filter( 'get_post_metadata', 'js_composer', 10, 3 );
		}

		load_plugin_textdomain( 'twicpics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
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
	 * Checks if the image (<img> or background type) is handled by an uncompatible plugin.
	 *
	 * @param      string $tag The image.
	 * @return boolean true if image's parent is marked with a blacklisted plugin class.
	 */
	private function is_blacklisted( $tag ) {
		$parent_node = $tag->parentNode;

		while ( 'html' !== $parent_node->tagName ) {
			$parent_node_classes = explode( ' ', $parent_node->getAttribute( 'class' ) );

			foreach ( $parent_node_classes as $class ) {
				if ( in_array( $class, $this->_plugins_blacklist, true ) ) {
					return true;
				}
			}

			$parent_node = $parent_node->parentNode;
		}
	}

	/**
	 * Checks if an elem has already been treated
	 *
	 * @param      string $class the element's class value.
	 * @return boolean true if already treated, false otherwise
	 */
	private function is_treated( $class ) {
		// return in_array( 'twic', explode( ' ', $class ), true ) || in_array( 'notwic', explode( ' ', $class ), true );
		return in_array( 'notwic', explode( ' ', $class ), true );
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
	 * Gets the lazyload placeholder configuration
	 *
	 * @return string the config
	 */
	private function get_lazyload_conf() {
		$options = get_option( 'twicpics_options' );

		switch ( $this->_lazyload ) :
			case 'preview_placeholder':
				return 'output=preview';
		endswitch;

		return false;
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
		switch ( $this->_lazyload ) :
			case 'preview_placeholder':
				if ( ! empty( $width ) && ! empty( $height ) ) {
					$src = $this->_user_domain . '/' . $src . '?twic=v1/cover=' . $width . ':' . $height . '/resize-max=' . $this->_max_width . '/' . $this->_lazyload_conf;
				}
				break;
		endswitch;

		return $src;
	}

	/**
	 * Gets the aspect-ratio of the image
	 *
	 * @param     string $img_url the URL of the image.
	 * @param     string $width   the width of the image.
	 * @param     string $height  the height of the image.
	 * @return array              the aspect-ratio of the image.
	 */
	private function get_aspect_ratio( $img_url, $width, $height ) {
		$aspect_ratio = array(
			'width'  => '',
			'height' => '',
		);

		if ( $width && $height ) {
			/* Fix for Divi builder plugin */
			if ( 'auto' !== $width && 'auto' !== $height ) {
				/* with both width & height */
				$aspect_ratio['width']  = $width;
				$aspect_ratio['height'] = $height;
			}
		} else {
			/* with filename */
			preg_match( '/.+\-(\d+)x(\d+)\..+/', $img_url, $sizes );

			if ( isset( $sizes[1] ) && isset( $sizes[2] ) ) {
				$aspect_ratio['width']  = $sizes[1];
				$aspect_ratio['height'] = $sizes[2];
			} else {
				$file = str_replace( content_url(), WP_CONTENT_DIR, $img_url );

				if ( file_exists( $file ) ) {
					$sizes = getimagesize( $file );

					if ( isset( $sizes[0] ) && isset( $sizes[1] ) ) {
						$aspect_ratio['width']  = $sizes[0];
						$aspect_ratio['height'] = $sizes[1];
					}
				}
			}
		}

		return $aspect_ratio;
	}

	/**
	 * Gets the full src of a potential cropped image
	 *
	 * The method simply removes the -{width}x{height} added by WordPress
	 *
	 * @param      string $img_url the original (maybe cropped) url of the image.
	 * @return string the full src image url
	 */
	private function get_full_src( $img_url ) {
		global $wpdb;
		$base_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $img_url );
		$image    = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s OR guid=%s;", $base_url, $img_url ) );

		if ( ! empty( $image ) ) {
			return wp_get_attachment_image_src( (int) $image[0], 'full' )[0];
		} else {
			return preg_replace( '/(.+)\-\d+x\d+.*(\..+)/', '$1$2', $img_url );
		}
	}

	/**
	 * Enqueues the TwicPics JS script
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'twicpics', $this->_user_domain . '/?v1', array(), $ver = null, false );
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
	 * Treats image attributes returned by WordPress functions
	 *
	 * @param      array $attributes The image attributes.
	 * @return array treated image attributes
	 */
	public function image_attributes( $attributes ) {
		/* already treated */
		if ( $this->is_treated( $attributes['class'] ? $attributes['class'] : '' ) ) {
			return $attributes;
		}

		$img_url = $attributes['src'];

		if ( strpos( $img_url, 'http' ) === false ) {
			$img_url = home_url( $img_url );
		}

		$attributes['data-twic-src'] = $this->get_full_src( $img_url );

		unset( $attributes['srcset'] );
		unset( $attributes['sizes'] );

		// $width  = '';
		// $height = $width;

		// /* Get sizing */
		// if ( $attributes['width'] && $attributes['height'] ) {
		// 	if ( 'auto' !== $attributes['width'] && 'auto' !== $$attributes['height'] ) {
		// 		/* treat only if both width & height */
		// 		$width  = $attributes['width'];
		// 		$height = $attributes['height'];
		// 	}
		// } else {
		// 	/* check by filename */
		// 	preg_match( '/.+\-(\d+)x(\d+)\..+/', $img_url, $sizes );

		// 	if ( isset( $sizes[1] ) && isset( $sizes[2] ) ) {
		// 		$width  = $sizes[1];
		// 		$height = $sizes[2];
		// 	} else {
		// 		$file = str_replace( content_url(), WP_CONTENT_DIR, $img_url );

		// 		if ( file_exists( $file ) ) {
		// 			$sizes = getimagesize( $file );

		// 			if ( isset( $sizes[0] ) && isset( $sizes[1] ) ) {
		// 				$width  = $sizes[0];
		// 				$height = $sizes[1];
		// 			}
		// 		}
		// 	}
		// }

		// if ( $width && $height ) {
		// 	$attributes['data-twic-src-transform'] = "cover={$width}:{$height}/auto/resize-max={$this->_max_width}";
		// }
		// /* Speed load */
		// $attributes['src'] = $this->get_twicpics_placeholder( $img_url, $attributes['width'], $attributes['height'] );

		$aspect_ratio = $this->get_aspect_ratio( $img_url, $attributes['width'], $attributes['height'] );

		if ( $aspect_ratio['width'] && $aspect_ratio['height'] ) {
			$attributes['data-twic-src-transform'] = 'cover=' . $aspect_ratio['width'] . ':' . $aspect_ratio['height'] . '/auto/resize-max=' . $this->_max_width;
		}

		/* LQIP */
		$attributes['src'] = $this->get_twicpics_placeholder( $img_url, $aspect_ratio['width'], $aspect_ratio['height'] );

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
		$modified_content = $original_content;

		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( mb_convert_encoding( $original_content, 'HTML-ENTITIES', 'UTF-8' ) );
		libxml_clear_errors();

		/* Treat img tags */
		foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
			/* not already treated */
			if ( ! $this->is_treated( $img->getAttribute( 'class' ) ) ) {
				$this->treat_imgtag( $img, $dom );
			}
		}

		/* Treat div background style attributes and visual composer class .vc_custom */
		foreach ( $dom->getElementsByTagName( 'div' ) as $div ) {
			/* already treated */
			if ( $this->is_treated( $div->getAttribute( 'class' ) ) ) {
				continue;
			}

			/* check class for vc_custom and add style for treatment */
			if ( strpos( $div->getAttribute( 'class' ), 'vc_custom_' ) !== false ) {
				global $vc_bg;
				$classes = explode( '', $div->getAttribute( 'class' ) );

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
			/* not already treated */
			if ( ! $this->is_treated( $fig->getAttribute( 'class' ) ) ) {
				$this->treat_tag_for_bg( $fig );
			}
		}

		/* Return data without doctype and html/body */
		return apply_filters( 'twicpics_the_content_return', substr( $dom->saveHTML( $dom->getElementsByTagName( 'body' )->item( 0 ) ), 6, -7 ), $original_content );
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
		$img_cloned->setAttribute( 'src', ( str_replace( ( $this->_user_domain . '/' ), '', $img_src ) ) );
		$img_cloned->setAttribute( 'alt', $img->getAttribute( 'alt' ) );
		$noscript->appendChild( $img_cloned );
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

		$img_url = $img->getAttribute( 'src' );

		/* relative path */
		if ( strpos( $img_url, '/' ) === 0 ) {
			$img_url = home_url( $img_url );
		}
		if ( strpos( $img_url, 'http' ) === false ) {
			return;
		}
		if ( ! $this->is_on_same_domain( $img_url ) ) {
			return;
		}
		if ( 'noscript' === $img->parentNode->tagName ) {
			return;
		}

		/* TwicPics Script 'data-twic-src' attribute */
		$img->setAttribute( 'data-twic-src', str_replace( get_site_url(), '', $this->get_full_src( $img_url ) ) );
		$img->removeAttribute( 'srcset' );
		$img->removeAttribute( 'sizes' );

		// $width  = '';
		// $height = $width;

		// /* Get sizing */
		// if ( $img->getAttribute( 'width' ) && $img->getAttribute( 'height' ) ) {
		// 	if ( 'auto' !== $img->getAttribute( 'width' ) && 'auto' !== $img->getAttribute( 'height' ) ) {
		// 		/* with both width & height */
		// 		$width  = $img->getAttribute( 'width' );
		// 		$height = $img->getAttribute( 'height' );
		// 	}
		// } else {
		// 	/* with filename */
		// 	preg_match( '/.+\-(\d+)x(\d+)\..+/', $img_url, $sizes );

		// 	if ( isset( $sizes[1] ) && isset( $sizes[2] ) ) {
		// 		$width  = $sizes[1];
		// 		$height = $sizes[2];
		// 	} else {
		// 		$file = str_replace( content_url(), WP_CONTENT_DIR, $img_url );
		// 		if ( file_exists( $file ) ) {
		// 			$sizes = getimagesize( $file );
		// 			if ( isset( $sizes[0] ) && isset( $sizes[1] ) ) {
		// 				$width  = $sizes[0];
		// 				$height = $sizes[1];
		// 			}
		// 		}
		// 	}
		// }

		// if ( $width && $height ) {
		// 	$img->setAttribute( 'data-twic-src-transform', "cover={$width}:{$height}/auto/resize-max={$this->_max_width}" );
		// }

		// /* LQIP */
		// $img->setAttribute( 'src', $this->get_twicpics_placeholder( $img_url, $width, $height ) );

		$aspect_ratio = $this->get_aspect_ratio( $img_url, $img->getAttribute( 'width' ), $img->getAttribute( 'height' ) );

		if ( $aspect_ratio['width'] && $aspect_ratio['height'] ) {
			$img->setAttribute( 'data-twic-src-transform', 'cover=' . $aspect_ratio['width'] . ':' . $aspect_ratio['height'] . '/auto/resize-max=' . $this->_max_width );
		}

		/* LQIP */
		$img->setAttribute( 'src', $this->get_twicpics_placeholder( $img_url, $aspect_ratio['width'], $aspect_ratio['height'] ) );

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
				return;
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
						$bg_placeholder  = $this->_user_domain . '/' . $bg_urls[0];
						$new_style_attr .= $property . ':' . str_replace( $bg_urls[0], $bg_placeholder, $value ) . ';';
					}
					break;

				case 'background-image':
					if ( strpos( $value, 'url(' ) === false ) {
						$new_style_attr .= $property . ':' . $value . ';';
						break;
					}
					if ( strpos( $value, ',' ) === false ) {
						$value          = trim( $value );
						$bg_urls        = array( substr( $value, 4, -1 ) ); // removes 'url(' and ')'.
						$bg_placeholder = $this->_user_domain . '/' . $bg_urls[0];

						// $new_style_attr .= $property . ':url(' . $this->get_twicpics_placeholder( $bg_urls[0] ) . ');'; // utile d'appeler ici get_twicpics_placeholder() sachant qu'on ne passe ni largeur ni hauteur ?
						$new_style_attr .= $property . ':url(' . $bg_placeholder . '?twic=v1/output=preview);';
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
			$tag->setAttribute( 'data-twic-background', 'url(' . str_replace( get_site_url(), '', $bg_urls[0] ) . ')' );

			if ( isset( $x ) && isset( $y ) ) {
				if ( 50 !== $x || 50 !== $y ) {
					$tag->setAttribute( 'data-twic-background-transform', "focus={$x}px{$y}p/auto" );
				}
			}

			$tag_img_children = $tag->getElementsByTagName( 'img' );

			if ( ! empty( $tag_img_children ) ) {

				foreach ( $tag_img_children as $img ) {
					$img_url = $img->getAttribute( 'src' );
					$img_url = explode( '?', $img_url );

					/* Compares preview placeholders of <figure> and its <img> children */
					if ( $bg_placeholder === $img_url[0] ) {
						$tag->removeChild( $img );
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
