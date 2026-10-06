<?php
/**
* Post and page meta and GraphQL field registration
*
* @package memoria
* @since 1.0.0
*/

// Variables
define('MEMORIA_HIDE_SIDEBAR', 'memoria_headless_hide_sidebar');

// Add meta box to the post edit screen
function memoria_add_post_meta_boxes(): void {
	add_meta_box(
		'memoria_post_options',
		'Options',
		'memoria_render_post_options_meta_box',
		['post', 'page'],
		'side',
		'default'
	);
}
add_action('add_meta_boxes', 'memoria_add_post_meta_boxes');

// Render the meta box
function memoria_render_post_options_meta_box(WP_Post $post): void {
		$id = esc_attr(MEMORIA_HIDE_SIDEBAR);
		$hide_sidebar = (bool) get_post_meta($post->ID, $id, true);
		wp_nonce_field('memoria_post_options_nonce', 'memoria_post_options_nonce');
	?>
		<div class="components-base-control__field">
			<div class="components-flex components-h-stack">
				<span class="components-checkbox-control__input-container">
					<input id="<?php echo $id; ?>" class="components-checkbox-control__input" type="checkbox" name="<?php echo $id; ?>" value="1" <?php checked($hide_sidebar, true); ?> />
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false">
						<path d="M16.5 7.5 10 13.9l-2.5-2.4-1 1 3.5 3.6 7.5-7.6z"></path>
					</svg>
				</span>

				<label class="components-checkbox-control__label" for="<?php echo $id; ?>">
					<?php esc_html_e('Hide sidebar'); ?>
				</label>
			</div>
		</div>
	<?php
}

// Save meta box data
function memoria_save_post_meta(int $post_id): void {
	if (!isset($_POST['memoria_post_options_nonce']) || !wp_verify_nonce($_POST['memoria_post_options_nonce'], 'memoria_post_options_nonce')) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}
	update_post_meta($post_id, MEMORIA_HIDE_SIDEBAR, isset($_POST[MEMORIA_HIDE_SIDEBAR]));
}
add_action('save_post', 'memoria_save_post_meta');

// Register post meta fields
function memoria_register_post_meta(): void {
	foreach (['post', 'page'] as $post_type) {
		register_post_meta($post_type, MEMORIA_HIDE_SIDEBAR, [
			'type'          => 'boolean',
			'single'        => true,
			'default'       => false,
			'show_in_rest'  => false,
			'auth_callback' => '__return_true',
		]);
	}
}
add_action('init', 'memoria_register_post_meta');

// Expose post meta to WPGraphQL
function memoria_register_graphql_fields(): void {
	$args = [
		'type'        => 'Boolean',
		'description' => 'Whether to hide the sidebar',
		'resolve'     => function ($post) {
			return (bool) get_post_meta($post->databaseId, MEMORIA_HIDE_SIDEBAR, true);
		},
	];
	register_graphql_field('Post', 'hideSidebar', $args);
	register_graphql_field('Page', 'hideSidebar', $args);
}
add_action('graphql_register_types', 'memoria_register_graphql_fields');
