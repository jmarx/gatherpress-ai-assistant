<?php
/**
 * Handles the AI Assistant admin page.
 *
 * @package GatherPress_AI_Assistant
 * @since 0.1.0
 */

namespace GatherPress_AI_Assistant;

/**
 * Class Admin_Page.
 *
 * Manages the AI Assistant admin interface.
 *
 * @since 0.1.0
 */
class Admin_Page {
	/**
	 * Singleton instance.
	 *
	 * @var Admin_Page
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Admin_Page
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
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_gatherpress_ai_process_prompt', array( $this, 'process_prompt_ajax' ) );
	}

	/**
	 * Add admin page to WordPress menu.
	 *
	 * @return void
	 */
	public function add_admin_page() {
		add_submenu_page(
			'edit.php?post_type=gatherpress_event',
			__( 'AI Assistant', 'gatherpress-ai-assistant' ),
			__( 'AI Assistant', 'gatherpress-ai-assistant' ),
			'edit_posts',
			'gatherpress-ai-assistant',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue scripts and styles for admin page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'gatherpress_event_page_gatherpress-ai-assistant' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'gatherpress-ai-assistant',
			GATHERPRESS_AI_ASSISTANT_URL . 'assets/css/admin.css',
			array(),
			GATHERPRESS_AI_ASSISTANT_VERSION
		);

		wp_enqueue_script(
			'gatherpress-ai-assistant',
			GATHERPRESS_AI_ASSISTANT_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			GATHERPRESS_AI_ASSISTANT_VERSION,
			true
		);

		wp_localize_script(
			'gatherpress-ai-assistant',
			'gatherpressAI',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'gatherpress_ai_nonce' ),
			)
		);
	}

	/**
	 * Render the AI Assistant admin page.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		$settings = Settings::get_instance();
		
		if ( ! $settings->has_api_key() ) {
			?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'GatherPress AI Assistant', 'gatherpress-ai-assistant' ); ?></h1>
				<div class="notice notice-warning">
					<p>
						<strong><?php esc_html_e( 'API Key Required', 'gatherpress-ai-assistant' ); ?></strong><br>
						<?php esc_html_e( 'Please configure your OpenAI API key to use the AI Assistant.', 'gatherpress-ai-assistant' ); ?>
					</p>
					<p>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gatherpress_event&page=gatherpress-ai-settings' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Configure API Key →', 'gatherpress-ai-assistant' ); ?>
						</a>
					</p>
				</div>
			</div>
			<?php
			return;
		}
		?>
		<div class="wrap gp-ai-assistant">
			<h1><?php echo esc_html__( 'GatherPress AI Assistant', 'gatherpress-ai-assistant' ); ?></h1>
			
			<div class="gp-ai-container">
				<div class="gp-ai-intro">
					<h2><?php esc_html_e( 'Create and Manage Events with AI', 'gatherpress-ai-assistant' ); ?></h2>
					<p><?php esc_html_e( 'Tell me what you want to do in plain English, and I\'ll help you create and manage your GatherPress events.', 'gatherpress-ai-assistant' ); ?></p>
					
					<div class="gp-ai-examples">
						<p><strong><?php esc_html_e( 'Example prompts:', 'gatherpress-ai-assistant' ); ?></strong></p>
						<ul>
							<li>"Create a book club event on the 3rd Tuesday of each month for 6 months at Downtown Library, 7pm"</li>
							<li>"Change all Book Club events from 7pm to 8pm"</li>
							<li>"Create a 5-day conference from May 1-5 at the Convention Center"</li>
							<li>"List all my venues"</li>
						</ul>
					</div>
				</div>

				<div class="gp-ai-chat">
					<div id="gp-ai-messages" class="gp-ai-messages">
						<!-- Messages will appear here -->
					</div>
					
					<div class="gp-ai-input-container">
						<textarea 
							id="gp-ai-prompt" 
							class="gp-ai-prompt" 
							placeholder="<?php esc_attr_e( 'What would you like me to do? (e.g., Create monthly book club events...)', 'gatherpress-ai-assistant' ); ?>"
							rows="3"
						></textarea>
						<button id="gp-ai-submit" class="button button-primary button-large">
							<?php esc_html_e( 'Send', 'gatherpress-ai-assistant' ); ?>
						</button>
					</div>
				</div>

				<div class="gp-ai-status" id="gp-ai-status" style="display:none;">
					<p class="gp-ai-processing">
						<span class="spinner is-active"></span>
						<?php esc_html_e( 'Processing...', 'gatherpress-ai-assistant' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle AJAX request to process AI prompt.
	 *
	 * @return void
	 */
	public function process_prompt_ajax() {
		check_ajax_referer( 'gatherpress_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';

		if ( empty( $prompt ) ) {
			wp_send_json_error( array( 'message' => 'Prompt is required' ) );
		}

		// Process with OpenAI.
		$handler = new OpenAI_Handler();
		$result  = $handler->process_prompt( $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}
}

