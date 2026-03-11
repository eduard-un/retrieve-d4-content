<?php
/**
 * Plugin Name: DT Retrieve Divi 4 Content
 * Description: Retrieve the Divi 4 layout stored in postmeta (_et_pb_divi_4_content) by Post/Page ID.
 * Author: Eduard Ungureanu
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DT_Retrieve_D4_Content {
	private const MENU_SLUG = 'dt-retrieve-d4-content';
	private const META_KEY  = '_et_pb_divi_4_content';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function register_admin_page(): void {
		add_management_page(
			'Retrieve Divi 4 Content',
			'Divi 4 Content',
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_admin_page' )
		);
	}

	public static function register_metabox(): void {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'dt-d4-content-metabox',
				'Divi 4 Layout',
				array( __CLASS__, 'render_metabox' ),
				$post_type,
				'normal',
				'low'
			);
		}
	}

	private static function is_tools_page( string $hook_suffix ): bool {
		return 'tools_page_' . self::MENU_SLUG === $hook_suffix;
	}

	private static function is_post_edit_page( string $hook_suffix ): bool {
		return in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true );
	}

	public static function enqueue_assets( string $hook_suffix ): void {
		if ( ! self::is_tools_page( $hook_suffix ) && ! self::is_post_edit_page( $hook_suffix ) ) {
			return;
		}

		$plugin_url = plugin_dir_url( __FILE__ );
		if ( wp_script_is( 'code-editor', 'registered' ) && ! wp_script_is( 'wp-code-editor', 'registered' ) ) {
			wp_register_script( 'wp-code-editor', false, array( 'code-editor' ), '1.0.0', true );
		}
		$editor_settings = false;
		if ( function_exists( 'wp_enqueue_code_editor' ) ) {
			$editor_settings = wp_enqueue_code_editor(
				array(
					'type'       => 'text/html',
					'codemirror' => array(
						'readOnly'       => true,
						'lineWrapping'   => true,
						'lineNumbers'    => true,
						'scrollbarStyle' => 'simple',
					),
				)
			);
		}

		wp_enqueue_script( 'jquery' );
		$script_deps = array( 'jquery' );
		if ( $editor_settings ) {
			$script_deps[] = 'code-editor';
		}

		wp_enqueue_style(
			'dt-retrieve-d4-content-admin',
			$plugin_url . 'assets/admin.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'dt-retrieve-d4-content-admin',
			$plugin_url . 'assets/admin.js',
			$script_deps,
			'1.0.0',
			true
		);

		if ( $editor_settings ) {
			wp_add_inline_script(
				'dt-retrieve-d4-content-admin',
				'window.DT_D4_CODE_EDITOR_SETTINGS = ' . wp_json_encode( $editor_settings ) . ';',
				'before'
			);
		}
	}

	public static function render_metabox( WP_Post $post ): void {
		global $wpdb;

		$content_value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
				$post->ID,
				self::META_KEY
			)
		);

		if ( null === $content_value ) {
			echo '<p class="dt-d4-metabox-empty">No Divi 4 layout found for this post.</p>';
			return;
		}
		?>
		<div class="dt-d4-metabox-wrap dt-d4-result">
			<div class="dt-d4-result-header">
				<span class="dt-d4-result-title">Raw layout &mdash; <code><?php echo esc_html( self::META_KEY ); ?></code></span>
				<div class="dt-d4-result-actions">
					<button
						type="button"
						class="button dt-d4-save-json"
						data-source-target="dt_d4_content"
						data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>"
						data-meta-key="<?php echo esc_attr( self::META_KEY ); ?>"
					>
						Save As JSON
					</button>
					<button type="button" class="button dt-d4-copy" data-copy-target="dt_d4_content">Copy to clipboard</button>
				</div>
			</div>
			<textarea id="dt_d4_content" class="dt-d4-code" readonly><?php echo esc_textarea( (string) $content_value ); ?></textarea>
			<p class="dt-d4-hint">Tip: This is the exact <code>meta_value</code> stored in <code>wp_postmeta</code> for <code><?php echo esc_html( self::META_KEY ); ?></code>.</p>
		</div>
		<?php
	}

	public static function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$post_id_raw = isset( $_POST['dt_post_id'] ) ? wp_unslash( $_POST['dt_post_id'] ) : '';
		$post_id     = is_string( $post_id_raw ) ? absint( $post_id_raw ) : 0;

		$submitted = ( 'POST' === $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['dt_retrieve_d4_submit'] ) && isset( $_POST['dt_retrieve_d4_nonce'] );

		$content_value = null;
		$error_message = '';

		if ( $submitted ) {
			if ( ! check_admin_referer( 'dt_retrieve_d4_action', 'dt_retrieve_d4_nonce' ) ) {
				$error_message = 'Security check failed. Please try again.';
			} elseif ( $post_id <= 0 ) {
				$error_message = 'Please enter a valid numeric Post/Page ID.';
			} else {
				global $wpdb;

				$content_value = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
						$post_id,
						self::META_KEY
					)
				);

				if ( null === $content_value ) {
					$error_message = "The ID requested doesn't have any Divi 4 layout in DB";
				}
			}
		}
		?>
		<div class="wrap">
			<div class="dt-d4-wrap">
				<div class="dt-d4-header">
					<h1 class="dt-d4-title">Retrieve Divi 4 Layout</h1>
					<p class="dt-d4-subtitle">Enter a Post/Page ID to fetch the <code><?php echo esc_html( self::META_KEY ); ?></code> meta value from the database.</p>
				</div>

				<div class="dt-d4-card">
					<form method="post" action="<?php echo esc_url( admin_url( 'tools.php?page=' . self::MENU_SLUG ) ); ?>">
						<?php wp_nonce_field( 'dt_retrieve_d4_action', 'dt_retrieve_d4_nonce' ); ?>
						<div class="dt-d4-form-row">
							<label for="dt_post_id" class="dt-d4-label">Post/Page ID</label>
							<input
								type="number"
								min="1"
								step="1"
								id="dt_post_id"
								name="dt_post_id"
								class="dt-d4-input"
								value="<?php echo esc_attr( $post_id > 0 ? (string) $post_id : '' ); ?>"
								placeholder="e.g. 123"
								required
							/>
							<button type="submit" name="dt_retrieve_d4_submit" class="button button-primary dt-d4-button">Retrieve</button>
						</div>
					</form>
				</div>

				<?php if ( $submitted && '' !== $error_message ) : ?>
					<div class="dt-d4-notice dt-d4-notice--error" role="alert">
						<strong>Error:</strong> <?php echo esc_html( $error_message ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $submitted && null !== $content_value && '' === $error_message ) : ?>
					<div class="dt-d4-card dt-d4-result">
						<div class="dt-d4-result-header">
							<h2 class="dt-d4-result-title">Divi 4 Layout (raw)</h2>
							<div class="dt-d4-result-actions">
								<button
									type="button"
									class="button dt-d4-save-json"
									data-source-target="dt_d4_content"
									data-post-id="<?php echo esc_attr( (string) $post_id ); ?>"
									data-meta-key="<?php echo esc_attr( self::META_KEY ); ?>"
								>
									Save As JSON
								</button>
								<button type="button" class="button dt-d4-copy" data-copy-target="dt_d4_content">Copy to clipboard</button>
							</div>
						</div>
						<textarea id="dt_d4_content" class="dt-d4-code" readonly><?php echo esc_textarea( (string) $content_value ); ?></textarea>
						<p class="dt-d4-hint">Tip: This is the exact <code>meta_value</code> stored in <code>wp_postmeta</code> for <code><?php echo esc_html( self::META_KEY ); ?></code>.</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

DT_Retrieve_D4_Content::init();
