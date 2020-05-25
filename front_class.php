<?php
defined( 'ABSPATH' ) || die( 'ERROR !' );

/**
 * The Class for TwicPics Front
 */
class TwicPics {
  function __construct() {
    $options = get_option( 'twicpics_options' );

    $this->_url = 'https://'. (
      defined('TWICPICS_URL')? TWICPICS_URL : ( ($options['url']?:'i.twic.it') )
    ) . '/v1' ;

    /* placeholder */
    $this->_lazyload = defined('TWICPICS_LAZYLOAD_TYPE')?TWICPICS_LAZYLOAD_TYPE:'placeholder';

    /* Conf (colors or percent) depending on lazyload type */
    $this->_lazyload_conf = defined('TWICPICS_LAZYLOAD_CONF')?TWICPICS_LAZYLOAD_CONF:$this->get_LazyLoad_conf();


    $this->add_action('wp_enqueue_scripts','enqueue_scripts',1);
    $this->add_action('wp_enqueue_scripts','enqueue_styles',1);
    $this->add_filter('wp_get_attachment_image_attributes','image_attr',99);
    $this->add_filter('the_content','content',99);

    if( in_array('js_composer/js_composer.php', apply_filters('active_plugins', get_option('active_plugins') ) ) ){
      $this->add_filter( 'get_post_metadata', 'js_composer', 10, 3 );
    }

    load_plugin_textdomain('twicpics',false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
  }

  /**
   * Provide the abilty to replace our add_action(s) with the prefix of "twicpics_" using the same params as the original add_action
   */
  private function add_action(string $tag, $function_to_add, int $priority = 10, int $accepted_args = 1){
    if( function_exists('twicpics_'.$function_to_add) ) add_action($tag, 'twicpics_'.$function_to_add,$priority,$accepted_args);
    else add_action($tag, array($this,$function_to_add),$priority,$accepted_args);
  }

  /**
   * Provide the abilty to replace our add_filter(s) with the prefix of "twicpics_" using the same params as the original add_filter
   */
  private function add_filter(string $tag, $function_to_add, int $priority = 10, int $accepted_args = 1){
    if( function_exists('twicpics_'.$function_to_add) ) add_filter($tag, 'twicpics_'.$function_to_add,$priority,$accepted_args);
    else add_filter($tag, array($this,$function_to_add),$priority,$accepted_args);
  }

  /**
   * Check if an elem has already been treated
   *
   * @param     string $class the element's class value
   * @return    boolean true if already treated, false otherwise
   */
  private function is_treated($class){
    return
      in_array('twic',explode( " ", $class ) ) ||
      in_array('notwic',explode( " ", $class ) );
  }

  /**
   * Check if an url is on the same domain
   *
   * @param     string $url_to_check the url to check
   * @return    boolean true if on same domain, false otherwise
   */
  private function is_on_same_domain($url_to_check){
    preg_match('/:\/\/([^\/?#]*)/', get_bloginfo('url'), $domain);
    preg_match('/:\/\/([^\/?#]*)/', $url_to_check, $url);
    return $domain[1] === $url[1];
  }

  /**
   * Get the lazyload placeholder configuration
   *
   * @return    string the config
   */
  private function get_LazyLoad_conf(){
    $options = get_option( 'twicpics_options' );
    switch( $this->_lazyload ):
      case 'placeholder' : return "transparent"; break;
    endswitch;

    return false;
  }

  /**
   * Get the replacement src depending of the type of lazyload configured
   *
   * @param     string $src the original (cropped or not) src of the image
   * @param     int|string $width if know the width of the image
   * @param     int|string $height if know the height of the image
   * @return    string the replacement src
   */
  private function get_twic_src($src,$width='',$height=''){
    switch( $this->_lazyload ):
      case 'placeholder' :
        $src = $this->_url.'/placeholder:'.((!empty($width)&&!empty($height))? ($width*2).'x'.($height*2) : '10000x10000').':'.$this->_lazyload_conf;
      break;
    endswitch;
    return $src;
  }

  /**
   * Get the full src of a potential cropped image
   *
   * The method simply removes the -{width}x{height} added by WordPress
   *
   * @param     string $url the original (maybe cropped) url of the image
   * @return    string the full src image url
   */
  private function get_full_src($url){
    global $wpdb;
    $base_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $url );
    $image = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s' OR guid='%s';", $base_url, $url ));

    if(!empty($image)) return wp_get_attachment_image_src( (int) $image[0], 'full' )[0];
    else return preg_replace('/(.+)\-\d+x\d+(.+)/', '$1$2', $url);
  }

  /**
   * Enqueue the TwicPics js script
   */
  public function enqueue_scripts(){ wp_enqueue_script('twicpics',$this->_url.'/script'); }

  /**
   * Echo inline style depending on lazyload type configured
   */
  public function enqueue_styles(){
    switch( $this->_lazyload ):
      case 'placeholder' : echo '<style>.twic{opacity:0;will-change:opacity;transition:opacity .2s linear;}.twic-done,.twic-background-done{opacity:1;}</style>'; break;
  	endswitch;
  }

  /**
   * Treats image attributes returned by WordPress functions
   *
   * @param     array $attr The image attributes
   * @return    array treated image attributes
   */
  public function image_attr($attr){
    /* already treated */
    if( $this->is_treated( $attr['class']?:"" ) ) return $attr;

    $url = $attr['src']; if( strpos($url,'http') === false ) $url = home_url($url);
    $attr['data-src'] = $this->get_full_src($url);

    if( !$attr['class'] ) $attr['class'] = 'twic'; else $attr['class'] .= ' twic';

    unset($attr['srcset']); unset($attr['sizes']);

    $width = $height = "";
    /* Get sizing */
    if( $attr['width'] && $attr['height'] ){
      /* treat only if both width & height */
      $width = $attr['width'];
      $height = $attr['height'];
    }else{
      /* check by filename */
      preg_match('/.+\-(\d+)x(\d+)\..+/', $url, $sizes);
      if( isset($sizes[1]) && isset($sizes[2]) ){
        $width = $sizes[1];
        $height = $sizes[2];
      }
    }
    if( $width && $height ){
      $attr['data-src-transform'] = "cover={$width}x{$height}/auto";
    }
    /* Speed load */
    $attr['src'] = $this->get_twic_src($url,$attr['width'],$attr['height']);

  	return $attr;
  }

  /**
   * Treats WordPress content
   *
   * @param     string $original_content The original content
   * @return    string the treated content
   */
  public function content($original_content){
    if ( empty( $original_content ) ) return $original_content;
    $modified_content = $original_content;
    $dom = new DOMDocument();
    libxml_use_internal_errors( true );
    $dom->loadHTML( mb_convert_encoding($original_content, 'HTML-ENTITIES', 'UTF-8') );
    libxml_clear_errors();

    /* Treat img tags */
    foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
      /* not already treated */
      if( !$this->is_treated($img->getAttribute( 'class' )) ) $this->treat_imgtag($img);
    }

    /* Treat div background style attributes and visual composer class .vc_custom */
    foreach ( $dom->getElementsByTagName( 'div' ) as $div ) {
      /* already treated */
      if( $this->is_treated($div->getAttribute( 'class' )) ) continue;

      /* check class for vc_custom and add style for treatment */
      if( strpos( $div->getAttribute( 'class' ), 'vc_custom_' ) !== false ){
        global $vc_bg;
        $classes = explode(" ",$div->getAttribute( 'class' ));
        foreach($classes as $class){
          if( strpos( $class, 'vc_custom_' ) === false ) continue;
          $style = $div->getAttribute('style');
          /* if no background image (others styles) */
          if( !isset($vc_bg[$class]) ) continue;
          if( empty($style) ) $style = 'background-image:url('.$vc_bg[$class].')'; else $style.= ';background-image:url('.$vc_bg[$class].')';
          $div->setAttribute('style',$style);
        }
      }

      $this->treat_tag_for_bg($div);
    }

    /* Treat figure background style attributes */
    foreach ( $dom->getElementsByTagName( 'figure' ) as $fig ) {
      /* not already treated */
      if( !$this->is_treated($fig->getAttribute( 'class' )) ) $this->treat_tag_for_bg($fig);
    }

    /* Return data without doctype and html/body */
    return apply_filters('twicpics_the_content_return',substr($dom->saveHTML($dom->getElementsByTagName('body')->item(0)), 6, -7),$original_content);
	}

  /**
   * Treat dom node img tag
   *
   * @param     DOMNode $img The img tag node
   * @return    void
   */
  private function treat_imgtag(&$img){
    $url = $img->getAttribute( 'src' );

    /* relative path */
    if( strpos($url,'/') === 0 ) $url = home_url($url);
    if( strpos($url,'http') === false ) return;
    if( !$this->is_on_same_domain($url) ) return;

    $img->setAttribute('data-src', $this->get_full_src($url) );

    $img->setAttribute('class', $img->getAttribute( 'class' ) . " twic" );

    $img->removeAttribute('srcset'); $img->removeAttribute('sizes');

    $width = $height = "";
    /* Get sizing */
    if( $img->getAttribute( 'width' ) && $img->getAttribute( 'height' )){
      /* treat only if both width & height */
      $width = $img->getAttribute( 'width' );
      $height = $img->getAttribute( 'height' );
    }else{
      /* check by filename */
      preg_match('/.+\-(\d+)x(\d+)\..+/', $url, $sizes);
      if( isset($sizes[1]) && isset($sizes[2]) ){
        $width = $sizes[1];
        $height = $sizes[2];
      }
    }
    if( $width && $height ){
      $img->setAttribute( 'data-src-transform', "cover={$width}x{$height}/auto" );
    }

    /* Speed load */
    $img->setAttribute( 'src', $this->get_twic_src($url, $width, $height) );
  }

  /**
   * Treat dom node for background
   *
   * @param     DOMNode $div The tag node
   * @return    void
   */
  private function treat_tag_for_bg(&$tag){
    $style_attr = $tag->getAttribute('style'); if( empty($style_attr) || strpos($style_attr,'background') === false ) return;
    $styles = explode(";",$style_attr);
    $new_style_attr = "";

    foreach($styles as $rule){ if( empty(trim($rule)) ) return;
      list($property,$value) = explode(":",$rule,2);

      switch( $property ){
        case "background" :
          if( strpos($value,'url(') === false ){ $new_style_attr.= $property.':'.$value.';'; break; }
          if( strpos($value,',') === false ){
            $value = trim($value);
            $bg_urls = array(substr($value, strpos($value,'url(')+4, strpos($value,')',strpos($value,'url('))-4 ));
            $new_style_attr.= $property.':'.str_replace($bg_urls[0], $this->get_twic_src($bg_urls[0]),$value).';';
          }
        break;

        case "background-image" :
          if( strpos($value,'url(') === false ){ $new_style_attr.= $property.':'.$value.';'; break; }
          if( strpos($value,',') === false ){
            $value = trim($value);

            /* remove "url(" and ")" */
            $bg_urls = array(substr($value,4,-1));

            $new_style_attr.= $property.':url('.$this->get_twic_src($bg_urls[0]).');';
          }else{
            /* multiple background not yet implemented */
          }
        break;

        case "background-position":
          $coordinates = explode( " ", $value );
          $x = str_replace( '%', '', $coordinates[0] );
          $y = str_replace( '%', '', $coordinates[1] );
          break;

        default : $new_style_attr.= $property.':'.$value.';';
      }
    }

    if( isset($bg_urls) && is_array($bg_urls) && $this->is_on_same_domain($bg_urls[0]) ){
      $tag->setAttribute('style',$new_style_attr);
      $tag->setAttribute('class', $tag->getAttribute( 'class' ) . " twic" );
      $tag->setAttribute('data-background', 'url('.$bg_urls[0].')' );

      if ( isset( $x ) && isset( $y ) ) {
        if ( $x != 50 || $y != 50 )
          $tag->setAttribute('data-background-transform', "focus={$x}px{$y}p/auto");
      }

      // foreach ( $tag->getElementsByTagName( 'img' ) as $img ) {
      //   $tag->removeChild( $img );
      // }
    }
  }

  /**
   * For Visual Composer, parse the css metadata to extract image urls associated with vc_custom_id and fill an global array
   *
   * @param     string $metadata the metadata value
   * @param     int $object_id (not used) the post_id
   * @param     string $meta_key the metakey associated to the metadata value
   * @return    string the unmodified metadata
   */
  public function js_composer($metadata, $object_id, $meta_key){
    if( ($meta_key != '_wpb_shortcodes_custom_css' && $meta_key != "_wpb_post_custom_css") || empty($metadata) ) return $metadata;
    switch( $meta_key ){
      case "_wpb_post_custom_css" : break;
      case "_wpb_shortcodes_custom_css" :
        global $vc_bg; if( !is_array($vc_bg) ) $vc_bg = array();
        preg_match_all('/\.(vc_custom_\d+)\{background-image:\s?url\((.*)\)/', $metadata, $output_array);
        if( !empty($output_array[1] ) ) $vc_bg = array_merge($vc_bg,array_combine ( $output_array[1] , $output_array[2] ));
      break;
    }
    return $metadata;
  }
}
