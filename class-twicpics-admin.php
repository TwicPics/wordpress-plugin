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
			<script type="text/JavaScript">
				let optimizationLevel = '<?php echo esc_html( get_option( 'twicpics_options' )['optimization_level'] ); ?>';

				const updateOptionsDisplay = ( twicpicsOptions, twicpicsDocItems ) => {
					for ( i = 0; i < twicpicsOptions.length; i++ ) { 
						if ( 'api' === optimizationLevel ) {
							twicpicsOptions[i].setAttribute("disabled", true);
						} else {
							twicpicsOptions[i].removeAttribute("disabled", false);
						}
					}

					for ( i = 0; i < twicpicsDocItems.length; i++ ) { 
						if ( 'api' === optimizationLevel ) {
							twicpicsDocItems[i].setAttribute("style", "display: none;");
						} else {
							twicpicsDocItems[i].setAttribute("style", "display: block; list-style: inside; font-size: 13px;");
						}
					}
				}

				const handleHighCompatibilityChange = ( twicpicsOptions, twicpicsDocItems ) => {
					if ( 'api' === optimizationLevel ) {
						optimizationLevel = 'script';
					} else {
						optimizationLevel = 'api';
					}
					updateOptionsDisplay( twicpicsOptions, twicpicsDocItems );
				}

				const callHandleHighCompatibilityChange = ( callback, twicpicsOptions, twicpicsDocItems ) => {
					callback( twicpicsOptions, twicpicsDocItems );
				}

				window.onload = () => {
					const optimizationLevelSelectElt = document.getElementById( "optimization_level" );
					const stepInputElt = document.getElementById( "step" );
					const placeholderTypeInputElt = document.getElementById( "placeholder_type" );
					const twicpicsOptions = [ stepInputElt, placeholderTypeInputElt ];
					const placeholderTypeOptionsDescriptionElt = document.getElementById( "placeholder-type_options-description" );
					const twicpicsDocItems = [ placeholderTypeOptionsDescriptionElt ];

					updateOptionsDisplay( twicpicsOptions, twicpicsDocItems );

					optimizationLevelSelectElt.addEventListener( "change", () => {
						callHandleHighCompatibilityChange( handleHighCompatibilityChange, twicpicsOptions, twicpicsDocItems );
					}
				);
			};
			</script>

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
				'label_for'         => 'user_domain',
				'field_description' => 'You can find your TwicPics domain in your ',
			)
		);

		add_settings_field(
			'twicpics_field_optimization_level',
			__( 'Optimization level', 'twicpics' ),
			array( $this, 'field_select' ),
			'twicpics',
			'twicpics_section_account_settings',
			array(
				'label_for'         => 'optimization_level',
				'field_description' => 'How the plugin will optimize images.',
				'options'           => array( 
					'script' => array(
						'value'       => 'script',
						'text'        => 'Pixel perfect',
						'isDefault'   => true,
						'description' => 'JavaScript based, pixel-perfect, lazy loaded image replacement.',
					),
					'api'    => array(
						'value'       => 'api',
						'text'        => 'Maximum compatibility',
						'description' => 'static, purely HTML based image replacement.',
					),
				),
				'feature_doc_items' => array(
					'The default approach should work 90% of the time but some plugins and/or themes, especially JavaScript-heavy ones, may clash with it. Use "Maximum compatibility" if and when you witness weird image distortions and/or flickering.',
				),
			)
		);

		add_settings_field(
			'twicpics_field_max_width',
			__( 'Max width of images', 'twicpics' ),
			array( $this, 'field_textinput' ),
			'twicpics',
			'twicpics_section_account_settings',
			array(
				'label_for'         => 'max_width',
				'field_description' => 'Maximum width of images in pixels. This prevents generating insanely large images on very wide screens. Default: 2000.',
			)
		);

		add_settings_field(
			'twicpics_field_step',
			__( 'Resize step', 'twicpics' ),
			array( $this, 'field_textinput' ),
			'twicpics',
			'twicpics_section_account_settings',
			array(
				'label_for'         => 'step',
				'field_description' => 'Numbers of pixels image width is rounded by. Default: 10.',
				'feature_doc_items' => array(
					'This will reduce the number of variants generated and help CDN performance.',
					'With the default of 10, a 342 pixel-wide image will be rounded down to 340 pixels.',
					'The higher the step, the less pixel perfect the result, so use with caution.',
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
				'label_for'         => 'placeholder_type',
				'field_description' => 'Image placeholder (LQIP) displayed while image is loading.',
				'options'           => array( 
					'blank'     => array(
						'value'       => 'blank',
						'text'        => 'Blank',
						'isDefault'   => true,
						'description' => 'nothing.',
					),
					'maincolor' => array(
						'value'       => 'maincolor',
						'text'        => 'Main color',
						'description' => 'the most represented color in the image',
						'example'     => 'https://assets.twic.pics/demo/anchor.jpeg?twic=v1/cover=100x100/output=maincolor',
					),
					'meancolor' => array(
						'value'       => 'meancolor',
						'text'        => 'Mean color',
						'description' => 'the average color of the image',
						'example'     => 'https://assets.twic.pics/demo/anchor.jpeg?twic=v1/cover=100x100/output=meancolor',
					),
					'preview'   => array(
						'value'       => 'preview',
						'text'        => 'Preview',
						'description' => 'a blurry preview of the image',
						'example'     => 'https://assets.twic.pics/demo/anchor.jpeg?twic=v1/cover=100x100/output=preview',
					),
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
					You need to create a domain on your <a href="%1$s" target="_blank" style="color: #8f00ff;">TwicPics dashboard</a> to optimize your website images.
					
					<br/>
					<a href="%2$s" target="_blank" style="color: #8f00ff;">Read this guide</a> to get started.
			</p>',
			'https://account.twicpics.com/signin/?utm_campaign=wordpress-plugin&utm_source=wp-admin&utm_medium=plugins&utm_content=' . esc_attr( preg_replace( '#^https?://#', '', get_site_url() ) ),
			'https://www.twicpics.com/docs/integrations/wordpress-plugin'
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

		if ( ! isset( $options['user_domain'] ) ) {
			$options['user_domain'] = '';
		}

		if ( 'max_width' === $args['label_for'] ) {
			if ( ! isset( $options['max_width'] ) || empty( $options['max_width'] ) ) {
				$options['max_width'] = 2000;
			}
		}

		if ( 'step' === $args['label_for'] ) {
			if ( ! isset( $options['step'] ) || empty( $options['step'] ) ) {
				$options['step'] = 10;
			}
		}

		echo '
			<input
				type="text"
				id="', esc_attr( $args['label_for'] ) ,'"
				name="twicpics_options[', esc_attr( $args['label_for'] ), ']"
				value="', ( esc_attr( $options[ $args['label_for'] ] ) ? esc_attr( $options[ $args['label_for'] ] ) : '' ),'" class="regular-text"
			/>
		';

		?>
		<p class="description">
			<?php
			echo esc_html( $args['field_description'] );
			if ( 'user_domain' === $args['label_for'] ) {
				echo '<a href="https://account.twicpics.com/signin" target="_blank" style="color: #8f00ff;">TwicPics dashboard</a>.';
			}
			?>
		</p>

		<?php
		if ( isset( $args['feature_doc_items'] ) ) {
			$this->add_feature_doc_items( $args['feature_doc_items'] );
		}
	}

	/**
	 * Callback for the select type fields
	 *
	 * @param     array $args Displays values arguments.
	 */
	public function field_select( $args ) {
		$options        = get_option( 'twicpics_options' );
		$select_options = $args['options'];

		if ( 'optimization_level' === $args['label_for'] ) {
			if ( ! isset( $options['optimization_level'] ) ) {
				$options['optimization_level'] = 'script';
			}
		}

		if ( 'placeholder_type' === $args['label_for'] ) {
			if ( ! isset( $options['placeholder_type'] ) ) {
				$options['placeholder_type'] = 'blank';
			}
		}

		echo '
			<select
				id="', esc_attr( $args['label_for'] ) ,'"
				name="twicpics_options[', esc_attr( $args['label_for'] ), ']"
			>
		';

		foreach ( $select_options as $option ) {
			if ( ( $args['label_for'] ) === 'optimization_level' ) {
				echo '
					<option
						value="', esc_attr( $option['value'] ),'"',
						( esc_attr( $option['value'] ) === esc_attr( $options['optimization_level'] ) ? 'selected' : '' ),
					'>',
						esc_attr( $option['text'] ),
					'</option>
				';
			};

			if ( ( $args['label_for'] ) === 'placeholder_type' ) {
				echo '
					<option
						value="', esc_attr( $option['value'] ),'"',
						( esc_attr( $option['value'] ) === esc_attr( $options['placeholder_type'] ) ? 'selected' : '' ),
					'>',
						esc_attr( $option['text'] ),
					'</option>
				';
			};
		}
		echo '</select>';
		?>

		<p class="description">
			<?php
			echo esc_html( $args['field_description'] );
			?>
		</p>

		<?php
		$this->add_options_description( $args['label_for'], $select_options );

		if ( isset( $args['feature_doc_items'] ) ) {
			$this->add_feature_doc_items( $args['feature_doc_items'] );
		}
	}

	/**
	 * Callback for the checkbox input type fields
	 *
	 * @param     array $args Displays values arguments.
	 */
	public function field_checkbox( $args ) {
		$options = get_option( 'twicpics_options' );
		echo '
			<input
				type="checkbox"
				id="', esc_attr( $args['label_for'] ) ,'"
				class="regular-text"
				name="twicpics_options[', esc_attr( $args['label_for'] ), ']"',
				( isset( $options[ $args['label_for'] ] ) ? 'checked' : '' ), 
			'/>';
		?>
		<p class="description">
			<?php
			echo esc_html( $args['field_description'] );
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
	<p class="description">
		<?php echo esc_html( $args['field_description'] ); ?>
	</p>
		<?php
	}

	/**
	 * Callback for blank field (treated elsewhere)
	 *
	 * @param     array $args Displays values arguments.
	 */
	public function field_blank( $args ) {}

	/**
	 * Adds select options description to the admin interface
	 * 
	 * @param     string $label Label of the field.
	 * @param     array $select_options Options of the <select> HTML element.
	 */
	private function add_options_description( $label, $select_options ) {
		if ( 'placeholder_type' === $label ) {
			echo '<ul id="placeholder-type_options-description" style="display: none;">';
		} else {
			echo '<ul style="list-style: inside; font-size: 13px;">';
		}
		foreach ( $select_options as $option ) {
			echo '
				<li>
					<span style="font-weight: bold;">' . esc_attr( $option['text'] ) . '</span>' . 
					( isset( $option['isDefault'] ) && $option['isDefault'] 
						? ' (default): ' 
						: ': ' 
					) .
					esc_attr( $option['description'] );

			if ( 'placeholder_type' === $label ) {
				if ( isset( $option['example'] ) ) {
					echo '
						<div style="padding-top: 4px; padding-left: 18px; width: 50px; height: 50px;">
							<img src="' . esc_attr( $option['example'] ) . '" style="width: 100%; height: 100%; object-fit: cover; border: 1px solid #d1d5db;" alt="LQIP image" />
						</div>';
				}
			}
			echo '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Adds documentation items to the admin interface
	 * 
	 * @param     array $feature_doc_items
	 */
	private function add_feature_doc_items( $feature_doc_items ) {
		$feature_doc_items_length = count( $feature_doc_items );

		echo '<p style="font-size: 13px; font-style: italic;">';
		foreach ( $feature_doc_items as $key => $doc_item ) {
			if ( ( $feature_doc_items_length - 1 ) !== $key ) {
				echo esc_attr( $doc_item ) . '<br/>';
			} else {
				echo esc_attr( $doc_item );
			};
		}
		unset( $doc_item );
		echo '</p>';
	}
}
?>
