<?php
/**
 * Handles plugin settings and configuration.
 *
 * @package GatherPress_AI_Assistant
 * @since 0.1.0
 */

namespace GatherPress_AI_Assistant;

/**
 * Class Settings.
 *
 * Manages plugin settings including OpenAI API key storage.
 *
 * @since 0.1.0
 */
class Settings {
	/**
	 * Singleton instance.
	 *
	 * @var Settings
	 */
	private static $instance = null;

	/**
	 * Option name for storing settings.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'gatherpress_ai_assistant_settings';

	/**
	 * Get singleton instance.
	 *
	 * @return Settings
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page to WordPress admin.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=gatherpress_event',
			__( 'AI Assistant Settings', 'gatherpress-ai-assistant' ),
			__( 'AI Settings', 'gatherpress-ai-assistant' ),
			'manage_options',
			'gatherpress-ai-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'gatherpress_ai_assistant_settings_group',
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Settings to sanitize.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( isset( $input['openai_api_key'] ) ) {
			$sanitized['openai_api_key'] = sanitize_text_field( $input['openai_api_key'] );
		}

		return $sanitized;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		$settings = get_option( self::OPTION_NAME, array() );
		$api_key  = $settings['openai_api_key'] ?? '';
		$has_key  = ! empty( $api_key );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'GatherPress AI Assistant Settings', 'gatherpress-ai-assistant' ); ?></h1>
			
			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'About API Costs:', 'gatherpress-ai-assistant' ); ?></strong>
					<?php esc_html_e( 'This plugin uses the OpenAI API. You need to provide your own API key and will be charged by OpenAI for usage. Typical costs are $0.01-0.10 per prompt.', 'gatherpress-ai-assistant' ); ?>
				</p>
				<p>
					<a href="https://platform.openai.com/api-keys" target="_blank"><?php esc_html_e( 'Get your API key from OpenAI →', 'gatherpress-ai-assistant' ); ?></a>
				</p>
			</div>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'gatherpress_ai_assistant_settings_group' );
				?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="openai_api_key">
								<?php esc_html_e( 'OpenAI API Key', 'gatherpress-ai-assistant' ); ?>
							</label>
						</th>
						<td>
							<input 
								type="password" 
								id="openai_api_key" 
								name="<?php echo esc_attr( self::OPTION_NAME ); ?>[openai_api_key]" 
								value="<?php echo esc_attr( $api_key ); ?>" 
								class="regular-text"
								placeholder="sk-proj-..."
							/>
							<?php if ( $has_key ) : ?>
								<p class="description" style="color: green;">
									✓ <?php esc_html_e( 'API key is configured', 'gatherpress-ai-assistant' ); ?>
								</p>
							<?php else : ?>
								<p class="description">
									<?php esc_html_e( 'Enter your OpenAI API key to enable AI features.', 'gatherpress-ai-assistant' ); ?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<?php if ( $has_key ) : ?>
				<hr>
				<h2><?php esc_html_e( 'Test Connection', 'gatherpress-ai-assistant' ); ?></h2>
				<p>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gatherpress_event&page=gatherpress-ai-assistant' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Go to AI Assistant →', 'gatherpress-ai-assistant' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get OpenAI API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		$settings = get_option( self::OPTION_NAME, array() );
		return $settings['openai_api_key'] ?? '';
	}

	/**
	 * Check if API key is configured.
	 *
	 * @return bool
	 */
	public function has_api_key() {
		return ! empty( $this->get_api_key() );
	}
}

