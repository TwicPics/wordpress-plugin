<?php

new class {


    /**
     * Selectors that should be modified for script compliance
     *
     * @var array $SCRIPT_CLASS_COMPLIANCE Blacklisted plugins
     */
    static private $SCRIPT_CLASS_COMPLIANCE = [
        '*.wp-block-image > img',
        '*.wp-block-media-text__media > img',
    ];

    /**
     * tests position data, creates focus attribute and returns it properly formatted if found
     */
    static private function get_focus( $position ) {
        static $R_OBJECT_POSITION = '/^\s*(\d+(?:\.\d+)?)%(?:\s+(\d+(?:\.\d+)?)%)?\s*$/';
        if ( $position !== null ) {
            $matches = [];
            if ( preg_match( $R_OBJECT_POSITION, $position, $matches ) ) {
                $x = $matches[ 1 ];
                $y = empty( $matches[ 2 ] ) ? '0' : $matches[ 2 ];
                if ( ( $x !== '50' ) || ( $y !== '50' ) ) {
                    return ( $x . 'px' . $y . 'p' );
                }
            }
        }
        return null;
    }

    /**
     * transforms srcset for api
     */
    private function get_api_srcset( $set ) {
        return implode( ',', array_map(
            function ( $part ) {
                if ( is_string( $part ) ) {
                    return $part;
                }
                $transform = $part->item->get_default_transformation();
                if ( ( $transform === null ) && ( $part->width !== null ) ) {
                    $transform = "resize=" . $part->width;
                }
                return $part->item->get_url( $this->domain, $transform ) . ' ' . $part->modifier;
            },
            $set
        ) );
    }

    /**
     * user domain
     */
    private $domain;

    /**
     * placeholder type
     */
    private $max_width;

    /**
     * placeholder type
     */
    private $placeholder;

    /**
     * quality level (null if default)
     */
    private $quality;

    /**
     * Constructor
     */
    public function __construct() {

        $this->domain = \TwicPics\Options::get( 'user_domain' );

        // if we don't have a domain, we stop right away.
        if ( empty( $this->domain ) ) {
            return;
        }

        // get other options
        $this->max_width   = \TwicPics\Options::get( 'max_width' );
        $this->placeholder = \TwicPics\Options::get( 'placeholder_type' );
        $this->quality     = \TwicPics\Options::get( 'quality', null );

        // enables output buffering.
        ob_start( [ $this, 'handle_content' ] );
    }

    /**
     *  Handles backgrounds
     *
     * @param     DOMNode     $element ff.
     * @param     DOMDocument $dom ff.
     */
    private function handle_background( $element, $dom ) {

        static $R_QUOTES           = [ '/"/', '/\'/' ];
        static $R_QUOTES_REPLACERS = [ '\'', '"' ];

        // gets the background object
        $background = $element->background();

        if ( $background === null ) {
            return;
        }

        // determines which approach to use
        if ( $element->optimization_level === 'script' ) {
            $this->handle_background_with_script( $background, $element, $dom );
        } else {
            $this->handle_background_with_api( $background, $element, $dom );
        }
    }

    /**
     * Blabla
     *
     * @param string      $src Blabla.
     * @param DOMNode     $img Blabla.
     * @param DOMDocument $dom Blabla.
     */
    private function handle_background_with_api( $background, $element, $dom ) {

        // do we have data-src and data-src-set?
        $has_data_src    = ( $element->attr( 'data-src' ) !== null );
        $has_data_srcset = ( $element->attr( 'data-src-set' ) !== null );

        // removes unnecessary attributes
        foreach( [
            'data-src',
            'data-src-set',
        ] as $name ) {
            $element->attr( $name, null );
        }

        // compute and set new background
        if ( $background ) {
            $background_transform = $background->item->get_default_transformation();
            $new_background = $background->item->get_url( $this->domain, $background_transform );
            if ( $has_data_src ) {
                $element->background( $background->item->get_url( $this->domain, 'output=' . $this->placeholder ) );
                $element->attr( 'data-src', $new_background );
            } else {
                $element->background( $new_background );
            }
        }

        // compute and set new srcset
        if ( $has_data_srcset && $set && count( $set ) ) {
            $element->attr( 'data-src-set', $this->get_api_srcset( $set ) );
        }
    }

    /**
     * Blabla
     *
     * @param string      $src Blabla.
     * @param DOMNode     $img Blabla.
     * @param DOMDocument $dom Blabla.
     */
    private function handle_background_with_script( $background, $element, $dom ) {

        // gets item
        $item = $background->item;

        // puts data-twic-background
        $element->attr( 'data-twic-background', 'url(' . wp_json_encode( $background->item->get_path() ) . ')' );

        // gets focus
        $focus = self::get_focus( $background->position );

        // creates preview
        $p_transform = new \TwicPics\Transform();
        if ( $focus !== null ) {
            $p_transform->before( 'focus', $focus );
            if ( $item->height !== null ) {
                $p_transform->before( 'cover', $item->width . 'x' . $item->height );
            }
        }
        $p_transform->after( 'output', $this->placeholder );
        $element->background(
            $item->get_url( $this->domain, $element->transform( 'background', $p_transform, true ) )
        );

        // handles data-twic-background-transform
        $transform = new \TwicPics\Transform();
        if ( $focus !== null ) {
            $transform->before( 'focus', $focus );
        }
        if ( $this->max_width > 0 ) {
            $transform->after( 'max', $this->max_width );
        }
        if ( $this->quality !== null ) {
            $transform->after( 'quality', $this->quality );
        }
        $element->apply_transform( 'background', $transform );
    }

    /**
     * Handles content.
     *
     * @param string $content Blabla.
     */
    private function handle_content( $content ) {

        // skips responses we shouldn't handle.
        if ( !\TwicPics\Response::status_code_supported() ) {
            return false;
        }

        // gets HTML encoding (if this is actually HTML).
        $encoding = \TwicPics\Response::get_html_encoding();

        // skips non-HTML content.
        if ( $encoding === null ) {
            return false;
        }

        // parses HTML.
        $dom = new \TwicPics\DOM( $content, $encoding );

        // skips if non-parseable.
        if ( !$dom->is_ok() ) {
            return false;
        }

        try {

            $use_script = false;

            // gets body wrapper
            $body = new \TwicPics\Element( $dom->get_body(), \TwicPics\Options::get( 'optimization_level' ) );

            // inspect the whole body
            foreach ( $body->all_descendants() as $element ) {
                // handles img elements
                if ( $element->is( 'img' ) ) {
                    if ( !$element->should_skip( 'src' ) ) {
                        $this->handle_image( $element, $dom );
                    }
                // handles backgrounds
                } else if ( !$element->should_skip( 'background' ) ) {
                    $this->handle_background( $element, $dom );
                }
                if ( !$use_script ) {
                    $use_script =
                        ( $element->attr( 'data-twic-src' ) !== null ) ||
                        ( $element->attr( 'data-twic-background') !== null );
                }
            }

            // installs script if needed.
            if ( $use_script ) {
                $step   = \TwicPics\Options::get( 'step', null );
                $script = $dom->create(
                    'script',
                    [
                        'async' => '',
                        'defer' => '',
                        'src'   =>
                            'https://' .
                            $this->domain .
                            '/?v1' .
                            ( ( $step === null ) ? '' : ( '&step=' . $step ) ),
                    ]
                );

                // register script
                $head = $dom->get_head();
                $head->insertBefore( $script, $head->firstChild );

                $style  = $dom->create(
                    'style',
                    '.wp-block-image .twic-img{display:block;width:100%;}'.
                    '.wp-block-image.alignfull .twic-img{width:100% !important;}'.
                    '.wp-block-image.alignwide .twic-img{width:100% !important;}'.
                    '.wp-block-media-text__media .twic-img{max-width:100%}'
                );

                // register style
                $dom->get_head()->appendChild( $style );
            }

        } catch ( \Exception $e ) {
            \TwicPics\Log::error( '' . $e );
        } catch ( \Throwable $e ) {
            \TwicPics\Log::error( '' . $e );
        }

        // adds additional script if needed
        foreach ( \TwicPics\Script::list() as $additional_script )
        {
            $script = $dom->create(
                'script',
                htmlspecialchars( file_get_contents( $additional_script, true) )
            );
            $dom->get_head()->appendChild( $script );
        }

        // adds logs if needed.
        $logs_code = \TwicPics\Log::code();
        if ( null !== $logs_code ) {
            $script = $dom->create( 'script', htmlspecialchars( $logs_code ) );
            $dom->get_head()->appendChild( $script );
        }
        // returns HTML.
        return $dom->to_html();
    }

    /**
     * Handles images
     *
     * @param DOMNode     $img Blabla.
     * @param DOMDocument $dom Blabla.
     */
    private function handle_image( $img, $dom ) {

        $set = $img->srcset();
        $src = $img->src();

        // no src, no deal!
        if ( empty( $src ) && empty( $set ) ) {
            return;
        }

        // determines which approach to use
        if ( $img->optimization_level === 'script' ) {
            $this->handle_image_with_script( $src, $set, $img, $dom );
        } else {
            $this->handle_image_with_api( $src, $set, $img, $dom );
        }

        // do we have to add a special script for this case ?
        \TwicPics\Script::handles_special_script( $img );
    }

    /**
     * Blabla
     *
     * @param string      $src Blabla.
     * @param DOMNode     $img Blabla.
     * @param DOMDocument $dom Blabla.
     */
    private function handle_image_with_api( $src, $set, $img, $dom ) {

        // do we have data-src and data-src-set?
        $has_data_src    = ( $img->attr( 'data-src' ) !== null );
        $has_data_srcset = ( $img->attr( 'data-src-set' ) !== null );

        // removes unnecessary attributes
        foreach( [
            'data-src',
            'data-src-set',
            'src',
            'srcset',
        ] as $name ) {
            $img->attr( $name, null );
        }

        // compute and set new src
        if ( $src ) {
            $src_transform = $src->get_default_transformation();
            $new_src = $src->get_url( $this->domain, $src_transform );
            if ( $has_data_src ) {
                $img->attr( 'src', $src->get_url( $this->domain, 'output=' . $this->placeholder ) );
                $img->attr( 'data-src', $new_src );
            } else {
                $img->attr( 'src', $new_src );
            }
        }

        // compute and set new srcset
        if ( $set && count( $set ) ) {
            $img->attr( $has_data_srcset ? 'data-src-set' : 'srcset', $this->get_api_srcset( $set ) );
        }
    }

    /**
     * Blabla
     *
     * @param \TwicPics\Item $src
     * @param object         $srcset
     * @param DOMElement     $img
     * @param DOMDocument    $dom
     */
    private function handle_image_with_script( $src, $set, $img, $dom ) {

        // handles guthenberg fake images
        if ( $img->is( '*.wp-block-media-text.is-image-fill *.wp-block-media-text__media > img' ) ) {
            $img->remove();
            return;
        }

        // gets actual item
        $item = $src;

        if ( $set && count( $set ) ) {
            $best_width = ( ( $src === null ) || ( $src->width === null ) ) ? 0 : $src->width;
            foreach ( $set as $part ) {
                if ( !is_string( $part ) ) {
                    $width = max(
                        ( $part->item->width === null ) ? 0 : $part->item->width,
                        ( $part->width === null ) ? 0 : $part->width
                    );
                    if ( $width > $best_width ) {
                        $item = $part->item;
                        $best_width = $width;
                    }
                }
            }
        }

        // gets fit and focus
        $fit        = $img->attr( 'data-object-fit', null );
        $focus      = self::get_focus( $img->attr( 'data-object-position', null ) );

        // creates preview
        $p_transform = new \TwicPics\Transform();
        if ( $focus !== null ) {
            $p_transform->before( 'focus', $focus );
            if ( $fit === 'cover' ) {
                $size = ( $item->height === null ) ? $img->size() : $item;
                if ( $size->height !== null ) {
                    $p_transform->before( 'cover', $width . ':' . $height );
                }
            }
        }
        $p_transform->after( 'output', $this->placeholder );
        $img->attr( 'src', $item->get_url( $this->domain, $img->transform( 'src', $p_transform, true ) ) );

        // creates transform instance

        // removes loading, sizes and srcset
        foreach( [
            'data-object-fit',
            'data-object-position',
            'data-src',
            'data-src-set',
            'decoding',
            'loading',
            'sizes',
            'srcset',
        ] as $name ) {
            $img->attr( $name, null );
        }

        // puts data-twic-src
        $img->attr( 'data-twic-src', $item->get_path() );

        // adds twic-img class
        $img->class('twic-img');

        // makes image suitable for TwicPics's Script if needed
        if ( $img->is( implode( ',', self::$SCRIPT_CLASS_COMPLIANCE ) ) ) {
            if ( $src->has_size() && !$img->width() ) {
                $_aspect_ratio_from_style = $img->aspect_ratio();
                $_height_from_style = $img->height();
                if ( $_aspect_ratio_from_style ) {
                    // sets actual image width
                    $img->width( $_height_from_style ?
                        $_aspect_ratio_from_style * $_height_from_style:
                        $src->width
                    );
                } else {
                    $img->aspect_ratio( $src->width.'/'.$src->height );
                    $img->object_fit( 'cover ');
                    $img->width( $src->width );
                }
            }
        }

        // handles data-twic-src-transform
        $transform = new \TwicPics\Transform();
        if ( $focus !== null ) {
            $transform->before( 'focus', $focus );
        }
        if ( $this->max_width > 0 ) {
            $transform->after( 'max', $this->max_width );
        }
        if ( $this->quality !== null ) {
            $transform->after( 'quality', $this->quality );
        }
        $img->apply_transform( 'src', $transform, $fit );

    }
};
