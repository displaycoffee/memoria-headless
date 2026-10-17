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

// Expose search pagination to WPGraphQL, since its cursor pagination has no total or page offset
function memoria_register_graphql_search_fields(): void {
	foreach (['RootQueryToPostConnectionWhereArgs', 'RootQueryToPageConnectionWhereArgs'] as $where_args) {
		register_graphql_field($where_args, 'offset', [
			'type'        => 'Int',
			'description' => 'Number of items to skip, for page-number pagination',
		]);
	}

	register_graphql_field('RootQuery', 'searchTotal', [
		'type'        => 'Int',
		'description' => 'Total number of published posts or pages matching a search',
		'args'        => [
			'search'   => ['type' => ['non_null' => 'String']],
			'postType' => ['type' => 'String', 'description' => 'Either "post" (default) or "page"'],
		],
		'resolve'     => function ($root, array $args) {
			$post_type = ($args['postType'] ?? 'post') === 'page' ? 'page' : 'post';
			$query = new WP_Query([
				'fields'         => 'ids',
				'post_status'    => 'publish',
				'post_type'      => $post_type,
				'posts_per_page' => 1,
				's'              => $args['search'],
			]);

			return (int) $query->found_posts;
		},
	]);
}
add_action('graphql_register_types', 'memoria_register_graphql_search_fields');

// Pass the offset where arg through to WP_Query
function memoria_map_graphql_offset(array $query_args, array $where_args): array {
	if (isset($where_args['offset'])) {
		$query_args['offset'] = max(0, (int) $where_args['offset']);
	}

	return $query_args;
}
add_filter('graphql_map_input_fields_to_wp_query', 'memoria_map_graphql_offset', 10, 2);

// Point author links at the frontend's /authors/ route (WordPress nests them under the /posts/ permalink base)
function memoria_author_link(string $link, int $author_id, string $author_nicename): string {
	return home_url('/authors/' . $author_nicename . '/');
}
add_filter('author_link', 'memoria_author_link', 10, 3);

// Use flat category links to match the frontend (slugs are unique, so child categories don't need their parent in the path)
function memoria_category_link(string $link, WP_Term $term, string $taxonomy): string {
	return $taxonomy === 'category' ? home_url('/categories/' . $term->slug . '/') : $link;
}
add_filter('term_link', 'memoria_category_link', 10, 3);

// Make dropdown blocks work on the frontend: each option's value is its archive path, and WordPress's inline navigation script is removed (the frontend navigates on change instead)
function memoria_render_dropdown_block(string $block_content, array $block): string {
	if (empty($block['attrs']['displayAsDropdown'])) {
		return $block_content;
	}

	// Category and tag options hold term slugs, so swap them for term links (archive options are already links)
	$taxonomy = $block['blockName'] === 'core/categories' ? ($block['attrs']['taxonomy'] ?? 'category') : '';
	$block_content = preg_replace('#<script\b[^>]*>.*?</script>#s', '', $block_content);
	$processor = new WP_HTML_Tag_Processor($block_content);

	while ($processor->next_tag()) {
		if ($processor->get_tag() === 'SELECT') {
			$processor->set_attribute('data-navigation-select', true);
		} elseif ($processor->get_tag() === 'OPTION') {
			$value = (string) $processor->get_attribute('value');

			// Placeholder option ("Select Category", etc.)
			if ($value === '' || $value === '-1') {
				$processor->set_attribute('value', '');
				continue;
			}

			if ($taxonomy) {
				$link = get_term_link($value, $taxonomy);
				$value = is_wp_error($link) ? '' : $link;
			}

			$processor->set_attribute('value', $value ? wp_make_link_relative($value) : '');
		}
	}

	return $processor->get_updated_html();
}
add_filter('render_block_core/archives', 'memoria_render_dropdown_block', 10, 2);
add_filter('render_block_core/categories', 'memoria_render_dropdown_block', 10, 2);

// Send search blocks to the frontend's search page, which reads the query from "q"
function memoria_render_search_block(string $block_content): string {
	$processor = new WP_HTML_Tag_Processor($block_content);

	while ($processor->next_tag()) {
		if ($processor->get_tag() === 'FORM') {
			$processor->set_attribute('action', '/search');
		} elseif ($processor->get_tag() === 'INPUT' && $processor->get_attribute('name') === 's') {
			$processor->set_attribute('name', 'q');
		}
	}

	return $processor->get_updated_html();
}
add_filter('render_block_core/search', 'memoria_render_search_block');

// Hide blocks the static frontend can't support from the inserter (date archives, comments, login)
// Note: they stay registered, so any already in content still render (e.g. the block preview posts used for styling)
function memoria_allowed_block_types(bool|array $allowed_block_types): bool|array {
	$hidden = [
		'core/archives',
		'core/calendar',
		'core/comment-author-name',
		'core/comment-content',
		'core/comment-date',
		'core/comment-edit-link',
		'core/comment-reply-link',
		'core/comment-template',
		'core/comments',
		'core/comments-pagination',
		'core/comments-pagination-next',
		'core/comments-pagination-numbers',
		'core/comments-pagination-previous',
		'core/comments-title',
		'core/latest-comments',
		'core/loginout',
		'core/post-comments-count',
		'core/post-comments-form',
		'core/post-comments-link',
	];

	// true means every registered block is allowed, so start from the full list
	if ($allowed_block_types === true) {
		$allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
	}

	return is_array($allowed_block_types) ? array_values(array_diff($allowed_block_types, $hidden)) : $allowed_block_types;
}
add_filter('allowed_block_types_all', 'memoria_allowed_block_types');

// Remove block styles the frontend doesn't style from the editor's style picker
// Note: existing blocks keep their is-style-* class, they just can't be picked anymore
function memoria_block_styles(array $metadata): array {
	$removed = [
		'core/button' => ['outline'],
	];

	if (isset($removed[$metadata['name']], $metadata['styles'])) {
		$metadata['styles'] = array_values(array_filter($metadata['styles'], fn(array $style): bool => !in_array($style['name'], $removed[$metadata['name']], true)));
	}

	return $metadata;
}
add_filter('block_type_metadata', 'memoria_block_styles');
