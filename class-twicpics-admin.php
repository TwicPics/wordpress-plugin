<?php
/**
 * TwicPics plugin admin-specific functionality.
 *
 * @package TwicPics
 */

defined( 'ABSPATH' ) || die( 'ERROR !' );

/**
 * The class for TwicPics admin settings
 */
class TwicPics_Admin {
	/**
	 * Set required actions
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_action( 'admin_menu', array( $this, 'options_page' ) );
		add_action( 'admin_print_scripts-twicpics', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add the settings's menu under the upload section
	 */
	public function options_page() {
		add_menu_page( __( 'TwicPics Options', 'twicpics' ), 'TwicPics', 'manage_options', 'twicpics', array( $this, 'render_config_page' ), '', null );
		add_submenu_page( 'upload.php', __( 'TwicPics Options', 'twicpics' ), 'TwicPics', 'manage_options', 'twicpics', array( $this, 'render_config_page' ) );
	}

	/**
	 * Add scripts in config page header
	 */
	public function enqueue_admin_scripts() {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	/**
	 * The callback for the settings page
	 */
	public function render_config_page() {
		/* Checks user capabilities. */
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Adds error/update messages.
		// Checks if the user has submitted the settings.
		// WordPress will add the "settings-updated" $_GET parameter to the URL.
		if ( isset( $_GET['settings-updated'] ) ) {
			/* Adds settings saved message with the class of "updated". */
			add_settings_error( 'twicpics_messages', 'twicpics_message', __( 'Settings Saved', 'twicpics' ), 'updated' );
		}

		/* Shows error/update messages. */
		settings_errors( 'twicpics_messages' );
		?>
	<div class="wrap">
		<?php include 'assets/logo.svg.php'; ?>
			<br/><br/>
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

		add_settings_section( 'twicpics_section_account_settings', __( 'Account Settings', 'twicpics' ), array( $this, 'section_account_settings' ), 'twicpics' );

		add_settings_field(
			'twicpics_field_user_domain',
			__( 'TwicPics domain', 'twicpics' ),
			array( $this, 'field_textinput' ),
			'twicpics',
			'twicpics_section_account_settings',
			array(
				'label_for'   => 'user_domain',
				'description' => esc_html( __( 'You can find your TwicPics domain in your TwicPics dashboard.', 'twicpics' ) ),
			)
		);

		add_settings_field(
			'twicpics_field_max_width',
			__( 'Max width of images', 'twicpics' ),
			array( $this, 'field_textinput' ),
			'twicpics',
			'twicpics_section_account_settings',
			array(
				'label_for'   => 'max_width',
				'description' => esc_html( __( 'The maximum intrinsic width for images (in pixels). Default: 2000.' ) ),
				'doc'         => array(
					'text' => 'max documentation',
					'link' => 'https://www.twicpics.com/docs/reference/transformations#max',
				),
			)
		);

		add_settings_field(
			'twicpics_field_step',
			__( 'Step for images resizing', 'twicpics' ),
			array( $this, 'field_textinput' ),
			'twicpics',
			'twicpics_section_account_settings',
			array(
				'label_for'   => 'step',
				'description' => esc_html( __( 'Step for images resizing (in pixels). Default: 10.' ) ),
				'doc'         => array(
					'text' => 'step documentation',
					'link' => 'https://www.twicpics.com/docs/integrations/wordpress-plugin#step-for-image-resizing',
				),
			)
		);

		add_settings_field(
			'twicpics_field_placeholder_type',
			__( 'Placeholder type', 'twicpics' ),
			array( $this, 'field_select' ),
			'twicpics',
			'twicpics_section_account_settings',
			array(
				'label_for'   => 'placeholder_type',
				'options'     => array( 
					'blank'     => array(
						'value' => 'blank',
						'text'  => 'blank',
					),
					'maincolor' => array(
						'value' => 'maincolor',
						'text'  => 'main color',
					),
					'meancolor' => array(
						'value' => 'meancolor',
						'text'  => 'mean color',
					),
					'preview'   => array(
						'value' => 'preview',
						'text'  => 'preview',
					),
				),
				'description' => esc_html( __( 'Type of the image preview displayed when image is loading (LQIP).' ) ),
				'doc'         => array(
					'text' => 'ouput preview types documentation',
					'link' => 'https://www.twicpics.com/docs/reference/transformations#output',
				),
			)
		);
	}

	/**
	 * Callback for the lazyload section
	 *
	 * @param     array $args Displays arguments.
	 */
	public function section_account_settings( $args ) {
		?>
	<div id="<?php echo esc_attr( $args['id'] ); ?>">
		<?php
		echo sprintf(
			'<p>
					Configure your TwicPics domain to start optimizing images. You can create a domain for free on your <a href="%1$s" target="_blank" style="color: #8f00ff;">TwicPics dashboard</a>.

					<span style="display: block; font-style: italic;">
						Learn more about domains in the <a href="%2$s" target="_blank" rel="noopener noreferrer" style="color: #8f00ff;">documentation</a>.
					</span>
			</p>',
			'https://account.twicpics.com/signin/?utm_campaign=wordpress-plugin&utm_source=wp-admin&utm_medium=plugins&utm_content=' . esc_attr( preg_replace( '#^https?://#', '', get_site_url() ) ),
			'https://www.twicpics.com/documentation/subdomain/'
		);
		?>
	</div><br/><br/>
		<?php
	}

	/**
	 * Callback for the text input type fields
	 *
	 * @param     array $args Displays values arguments.
	 */
	public function field_textinput( $args ) {
		$options = get_option( 'twicpics_options' );

		echo '
			<input
				type="text"
				id="', esc_attr( $args['label_for'] ) ,'"
				name="twicpics_options[', esc_attr( $args['label_for'] ), ']"
				value="', ( esc_attr( $options[ $args['label_for'] ] ) ? esc_attr( $options[ $args['label_for'] ] ) : '' ),'" class="regular-text"
			/>';
		?>
		<p class="description">
			<?php
			echo esc_html( $args['description'] );
			if ( isset( $args['doc'] ) ) {
				echo ' See <a href="' . esc_html( $args['doc']['link'] ) . '" target="_blank" rel="noopener noreferrer" style="color: #8f00ff;">' . esc_html( $args['doc']['text'] ) . '</a>.';
			}
			?>
		</p>
		<?php
	}

	/**
	 * Callback for the select type fields
	 *
	 * @param     array $args Displays values arguments.
	 */
	public function field_select( $args ) {
		$options        = get_option( 'twicpics_options' );
		$select_options = $args['options'];

		echo '
			<select
				id="', esc_attr( $args['label_for'] ) ,'"
				name="twicpics_options[', esc_attr( $args['label_for'] ), ']"
			>
				<option value="" disabled>
					Choose a placeholder type
				</option>';

		foreach ( $select_options as $option ) :
			echo '
				<option
					value="', esc_attr( $option['value'] ),'"',
					( esc_attr( $option['value'] ) === esc_attr( $options['placeholder_type'] ) ? 'selected' : '' ),
				'>',
					esc_attr( $option['text'] ),
				'</option>';
		endforeach;

		echo '</select>'
		?>
		<p class="description">
			<?php
			echo esc_html( $args['description'] );
			if ( isset( $args['doc'] ) ) {
				echo ' See <a href="' . esc_html( $args['doc']['link'] ) . '" target="_blank" rel="noopener noreferrer" style="color: #8f00ff;">' . esc_html( $args['doc']['text'] ) . '</a>.';
			}
			?>
		</p>
		<?php
	}

	/**
	 * Callback for the radios type fields
	 *
	 * @param     array $args Displays values arguments.
	 */
	public function field_radios( $args ) {
		$options = get_option( 'twicpics_options' );
		$types   = $args['values'];
		foreach ( $types as $label => $value ) :
			echo '
				<label>
					<input
						type="radio"
						id="', esc_attr( $args['label_for'] ) ,'"
						name="twicpics_options[', esc_attr( $args['label_for'] ), ']"
						value="', esc_attr( $value ),'"',
						( checked( $options[ $args['label_for'] ] ? $options[ $args['label_for'] ] : 'placeholder',
							$value,
						false ) ),
					'>',
					esc_html( $label ),
				'</label>
				<br/><br/>';
		endforeach;
		?>
	<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	/**
	 * Callback for blank field (treated elsewhere)
	 *
	 * @param     array $args Displays values arguments.
	 */
	public function field_blank( $args ) {}
}
