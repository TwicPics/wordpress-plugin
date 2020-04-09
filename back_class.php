<?php
defined( 'ABSPATH' ) || die( 'ERROR !' );

/**
 * The Class for TwicPics Admin Settings
 */
class TwicPics_admin {
  function __construct() {
    add_action( 'admin_init', array($this,'settings_init') );
    add_action( 'admin_menu', array($this,'options_page') );
    add_action( 'admin_print_scripts-twicpics', array($this, 'enqueue_admin_scripts'));

  }

  /**
   * Add the settings's menu under the upload section
   */
  public function options_page() {
   add_submenu_page( 'upload.php', __('TwicPics Options','twicpics'), 'TwicPics', 'manage_options', 'twicpics', array($this,'render_config_page'));
  }

  /**
   * Add scripts in config page header
   */
  public function enqueue_admin_scripts(){
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
  }

  /**
   * The callback for the settings page
   */
  public function render_config_page(){
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) return;

    // add error/update messages

    // check if the user have submitted the settings
    // wordpress will add the "settings-updated" $_GET parameter to the url
    if ( isset( $_GET['settings-updated'] ) ) {
      // add settings saved message with the class of "updated"
      add_settings_error( 'twicpics_messages', 'twicpics_message', __( 'Settings Saved', 'twicpics' ), 'updated' );
    }

    // show error/update messages
    settings_errors( 'twicpics_messages' );
  ?>
    <div class="wrap">
      <?php include 'assets/logo.svg.php'; ?>
      <form action="options.php" method="post">
        <?php
        settings_fields( 'twicpics' );
        do_settings_sections( 'twicpics' );
        submit_button( __( 'Save Settings', 'twicpics' ) );
      ?>
      </form>
    </div>
  <?php
  }

  /**
   * The settings configuration
   */
  public function settings_init() {
   register_setting( 'twicpics', 'twicpics_options' );

   add_settings_section( 'twicpics_section_identity', __( 'Identity', 'twicppics' ), array($this,'section_identity'), 'twicpics' );
    add_settings_field('twicpics_field_url', __('Custom Url','twicpics'), array($this,'field_textinput'), 'twicpics', 'twicpics_section_identity', array(
      'label_for' => 'url',
      'description'  => esc_html( __('Fill your twicpics url in this field', 'twicpics' ) )
    ));

   add_settings_section( 'twicpics_section_lazyload', __( 'LazyLoading', 'twicppics' ), array($this,'section_lazyload'), 'twicpics' );
    add_settings_field('twicpics_field_lazyload_type', __('Type of lazyload','twicpics'), array($this,'field_lazyload_type'), 'twicpics', 'twicpics_section_lazyload', array(
      'label_for' => 'lazyload_type',
      'description'  => esc_html( __('Select the type of lazyload you want', 'twicpics' ) )
    ));
    // add_settings_field('twicpics_field_lazyload_placeholder_foreground', " ", array($this,'field_blank'), 'twicpics', 'twicpics_section_lazyload', array( 'label_for' => 'lazyload_placeholder_foreground'));
    // add_settings_field('twicpics_field_lazyload_placeholder_background', " ", array($this,'field_blank'), 'twicpics', 'twicpics_section_lazyload', array( 'label_for' => 'lazyload_placeholder_background'));
  }

  /**
   * Callback for the lazyload section
   *
   * @param     array $args Display arguments.
   */
  public function section_identity($args){ ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php echo sprintf(
      __( 'In this section, you can set your registred TwicPics url. You can find it in your <a href="%1$s" target="_blank">account</a>', 'twicpics' ), "https://account.twicpics.com/login"
    ); ?></p>
  <?php
  }

  /**
   * Callback for the lazyload section
   *
   * @param     array $args Display arguments.
   */
  public function section_lazyload($args){ ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'In this section, you can set the type of lazyloading you want before the TwicPics script loads.', 'twicpics' ); ?></p>
  <?php
  }

  /**
   * Callback for the text input type fields
   *
   * @param     array $args Display/Values argurments
   */
  public function field_textinput($args){
    $options = get_option( 'twicpics_options' );
    echo '<input type="text" id="', esc_attr( $args['label_for'] ) ,'" name="twicpics_options[', esc_attr( $args['label_for'] ), ']" value="', ($options[ $args['label_for'] ]?:''),'" class="regular-text" />';
    ?>
    <p class="description"><?php echo $args['description']; ?></p>
    <?php
  }

  /**
   * Callback for the radios type fields
   *
   * @param     array $args Display/Values argurments
   */
  public function field_radios($args){
    $options = get_option( 'twicpics_options' );
    $types = $args['values'];
    foreach( $types as $label => $value ):
      echo '<label>
        <input type="radio" id="', esc_attr( $args['label_for'] ) ,'" name="twicpics_options[', esc_attr( $args['label_for'] ), ']" value="', $value,'" ',( checked( $options[ $args['label_for'] ]?:'placeholder', $value, false ) ),'>',
        $label,
      '</label><br/><br/>';
    endforeach;
    ?>
    <p class="description"><?php echo $args['description']; ?></p>
    <?php
  }

  /**
   * Callback for blank field (treated elsewhere)
   *
   * @param     array $args Display/Values argurments
   */
  public function field_blank($args){}

  /**
   * Callback for the lazyload_type fields
   *
   * @param     array $args Display/Values argurments
   */
  public function field_lazyload_type($args){
    $options = get_option( 'twicpics_options' );
    $current_val = $options[ $args['label_for'] ]?:'placeholder';
    $name = esc_attr( $args['label_for'] );?>
    <input type="radio" id="<?php echo $name ?>" name="twicpics_options[<?php echo $name ?>]" value="placeholder" <?php checked($current_val, 'placeholder' ); ?>>
    <label for="<?php echo $name ?>">
        <?php _e("Placeholder (default): A grey placeholder replace your image during it loads",'twicpics'); ?>
    </label>
    <fieldset class="show_if_<?php echo $name ?>_checked">
      <legend><? _e('Placeholder colors','twicpics'); ?></legend>

      <p><?php _e('Choose the colors of the placeholder you want (leave both empty if you want a transparent placeholder)','twicpics'); ?></p><br/>

      <label for="lazyload_placeholder_foreground"><?php _e('Foreground placeholder color','twicpics'); ?></label><br/>
      <input id="lazyload_placeholder_foreground" name="twicpics_options[lazyload_placeholder_foreground]" value="<?php echo $options[ 'lazyload_placeholder_foreground' ]?:''; ?>" /><br/><br/>
      <label for="lazyload_placeholder_background"><?php _e('Background placeholder color','twicpics'); ?></label><br/>
      <input id="lazyload_placeholder_background" name="twicpics_options[lazyload_placeholder_background]" value="<?php echo $options[ 'lazyload_placeholder_background' ]?:''; ?>" />
    </fieldset>
    <br/><br/>
    <input type="radio" id="<?php echo $name ?>-lqip" name="twicpics_options[<?php echo $name ?>]" value="LQIP" <?php checked($current_val, 'LQIP' ); ?>>
    <label for="<?php echo $name ?>-lqip">
        <?php _e("Low Quality Image: It's your image, at 2 percent in lighter and low quality",'twicpics'); ?>
    </label>
    <fieldset class="show_if_<?php echo $name ?>-lqip_checked">
      <legend><? _e('Percent of image quality','twicpics'); ?></legend>

      <p><?php _e('Choose the image pourcent you want, the lowest the lighter','twicpics'); ?></p><br/>

      <label for="lazyload_lqip_percent"><?php _e('Percent','twicpics'); ?></label><br/>
      <input id="lazyload_lqip_percent" name="twicpics_options[lazyload_lqip_percent]" type="number" min="1" max="15" value="<?php echo $options[ 'lazyload_lqip_percent' ]?:'2'; ?>" />
    </fieldset>
    <br/><br/>
    <input type="radio" id="<?php echo $name ?>-nolazyload" name="twicpics_options[<?php echo $name ?>]" value="nolazyload" <?php checked($current_val, 'nolazyload' ); ?>>
    <label for="<?php echo $name ?>-nolazyload">
        <?php _e("No lazyload (not recommanded): We don't apply any image replacement before script loads",'twicpics'); ?>
    </label>
    <br/><br/>
    <p class="description"><?php echo $args['description']; ?></p>
    <style type="text/css">
      .show_if_<?php echo $name ?>_checked, .show_if_<?php echo $name ?>-lqip_checked{ display:none; }
      #<?php echo $name ?>:checked ~ .show_if_<?php echo $name ?>_checked,#<?php echo $name ?>-lqip:checked ~ .show_if_<?php echo $name ?>-lqip_checked{
        display:block;
        margin-left:1em; margin-top:1em;
        border:1px solid black;
        padding:1em;
      }
    </style>
    <script type="text/javascript"> (function($) { $(document).ready(function() { $('.show_if_<?php echo $name ?>_checked input').wpColorPicker(); }); })(jQuery); </script>
    <?php
  }
}
