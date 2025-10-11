<?php
/**
 * Plugin Name:       GatherPress AI Assistant
 * Plugin URI:        https://github.com/GatherPress/gatherpress-ai-assistant
 * Description:       AI-powered assistant for managing GatherPress events using natural language prompts.
 * Author:            The GatherPress Community
 * Author URI:        https://gatherpress.org/
 * Version:           0.1.0
 * Requires PHP:      7.4
 * Requires at least: 6.7
 * Requires Plugins:  gatherpress, abilities-api
 * Text Domain:       gatherpress-ai-assistant
 * License:           GNU General Public License v2.0 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package GatherPress_AI_Assistant
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'GATHERPRESS_AI_ASSISTANT_VERSION', '0.1.0' );
define( 'GATHERPRESS_AI_ASSISTANT_FILE', __FILE__ );
define( 'GATHERPRESS_AI_ASSISTANT_PATH', plugin_dir_path( __FILE__ ) );
define( 'GATHERPRESS_AI_ASSISTANT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if required plugins are active.
 *
 * @return void
 */
function gatherpress_ai_assistant_check_dependencies() {
	$missing = array();

	if ( ! function_exists( 'wp_register_ability' ) ) {
		$missing[] = 'WordPress Abilities API';
	}

	if ( ! defined( 'GATHERPRESS_VERSION' ) ) {
		$missing[] = 'GatherPress';
	}

	if ( ! empty( $missing ) ) {
		add_action( 'admin_notices', function() use ( $missing ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong>GatherPress AI Assistant</strong> requires the following plugins to be installed and activated:
				</p>
				<ul>
					<?php foreach ( $missing as $plugin ) : ?>
						<li><?php echo esc_html( $plugin ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		} );
		return;
	}

	// Load plugin files.
	require_once GATHERPRESS_AI_ASSISTANT_PATH . 'includes/class-settings.php';
	require_once GATHERPRESS_AI_ASSISTANT_PATH . 'includes/class-admin-page.php';
	require_once GATHERPRESS_AI_ASSISTANT_PATH . 'includes/class-openai-handler.php';

	// Initialize plugin.
	GatherPress_AI_Assistant\Settings::get_instance();
	GatherPress_AI_Assistant\Admin_Page::get_instance();
}

add_action( 'plugins_loaded', 'gatherpress_ai_assistant_check_dependencies' );

