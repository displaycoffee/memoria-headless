<?php
/**
* Functions to render fields
*
* @package memoria
* @since 1.0.0
*/

// Exit if accessed directly
if (!defined('ABSPATH')) { exit; }

// Render a text input that reads/writes a core WP option directly
function memoria_render_core_text_field(array $args): void {
	$key = $args['key'];
	$description = $args['description'] ?? '';
	$value = get_option($key, '');
	printf('<input class="regular-text" type="text" name="%s" value="%s" />', esc_attr($key), esc_attr($value));
	echo($description ? '<p class="description">' . $description . '</p>' : '');
}

// Render the media picker field
function memoria_render_media_picker_field(array $args): void {
		$key = $args['key'];
		$description = $args['description'] ?? '';
		$is_icon = $args['is_icon'];
		$blog_name = (string) get_option('blogname');

		// Icon pickers post directly to their own core option; everything else needs
		// to be nested under memoria_theme_options[...] so it lands in the serialized array
		$name = $is_icon ? $key : MEMORIA_OPTIONS . '[' . $key . ']';

		// Setup image details — icon pickers are stored as their own core option,
		// everything else lives inside the serialized memoria_theme_options array
		$image_id = $is_icon
			? (int) get_option($key, 0)
			: (int) (get_option(MEMORIA_OPTIONS, [])[$key] ?? 0);
		$image_url= $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
		$image_file_path = $image_id ? get_attached_file($image_id) : '';
		$image_file_name = $image_id ? wp_basename($image_file_path) : '';
		$image_alt_text = $image_id ? get_post_meta($image_id, '_wp_attachment_image_alt', true) : '';

		// Create alt text
		$alt_text = '';
		if ($image_id) {
			$context = $is_icon ? 'icon' : 'image';
			$preview_text = $image_alt_text ? $context . ' preview: Current image: ' . $image_alt_text . '.' : $context . ' preview: The current image has no alternative text.';
			$file_text = 'The file name is: ' . $image_file_name . '.';
			$alt_text = $preview_text . ' ' . $file_text;
		}

		// Button labels differ between icon and generic image pickers
		$labels = $is_icon
			? ['choose' => 'Choose a Site Icon', 'change' => 'Change Site Icon', 'remove' => 'Remove Site Icon']
			: ['choose' => 'Choose an Image', 'change' => 'Change Image', 'remove' => 'Remove Image'];
	?>
		<div class="media-picker">
			<?php if ($is_icon) : ?>
				<div class="media-picker-preview site-icon-preview <?php echo $image_id ? 'has-media' : 'hidden'; ?>">
					<style>
						:root {
							--site-icon-url: url( '<?php echo esc_url($image_url); ?>' );
						}
					</style>
					<div class="media-picker-wrap direction-wrap">
						<?php if ($image_url) : ?>
							<img class="app-media-picker-preview app-icon-preview" src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr('App ' . $alt_text); ?>" />
						<?php else: ?>
							<img class="app-media-picker-preview app-icon-preview" src alt />
						<?php endif; ?>

						<div class="media-picker-preview-browser site-icon-preview-browser">
							<svg role="img" aria-hidden="true" fill="none" xmlns="http://www.w3.org/2000/svg" class="browser-buttons">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M0 20a6 6 0 1 1 12 0 6 6 0 0 1-12 0Zm18 0a6 6 0 1 1 12 0 6 6 0 0 1-12 0Zm24-6a6 6 0 1 0 0 12 6 6 0 0 0 0-12Z"></path>
							</svg>

							<div class="media-picker-preview-tab site-icon-preview-tab">
								<?php if ($image_url) : ?>
									<img class="browser-media-picker-preview browser-icon-preview" src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr('Browser ' . $alt_text); ?>">
								<?php else: ?>
									<img class="browser-media-picker-preview browser-icon-preview" src alt />
								<?php endif; ?>
								<div class="media-picker-preview-site-title site-icon-preview-site-title" aria-hidden="true">
									<?php echo esc_html($blog_name); ?>
								</div>
								<svg role="img" aria-hidden="true" fill="none" xmlns="http://www.w3.org/2000/svg" class="close-button">
									<path d="M12 13.0607L15.7123 16.773L16.773 15.7123L13.0607 12L16.773 8.28772L15.7123 7.22706L12 10.9394L8.28771 7.22705L7.22705 8.28771L10.9394 12L7.22706 15.7123L8.28772 16.773L12 13.0607Z"></path>
								</svg>
							</div>
						</div>
					</div>
				</div>
			<?php else: ?>
				<div class="media-picker-preview <?php echo $image_id ? 'has-media' : 'hidden'; ?>">
					<div class="media-picker-wrap direction-wrap">
						<?php if ($image_url) : ?>
							<img class="app-media-picker-preview" src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr('App ' . $alt_text); ?>" />
						<?php else: ?>
							<img class="app-media-picker-preview" src alt />
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<input type="hidden" class="media-picker-input" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($image_id ?: ''); ?>" />

			<div class="media-picker-action-buttons">
				<button type="button" class="media-picker-select button">
					<?php echo $image_id ? $labels['change'] : $labels['choose']; ?>
				</button>
				<button type="button" class="media-picker-remove button button-secondary reset<?php echo $image_id ? '' : ' hidden'; ?>">
					<?php echo $labels['remove']; ?>
				</button>
			</div>
		</div>

		<?php echo($description ? '<p class="description">' . $description . '</p>' : ''); ?>
	<?php
}

// Render a text input
function memoria_render_text_field(array $args): void {
	$options = get_option(MEMORIA_OPTIONS, []);
	$key = $args['key'];
	$type = $args['type'];
	$description = $args['description'] ?? '';
	$value = $options[$key] ?? $args['default'];
	printf('<input class="regular-text" type="%s" name="' . MEMORIA_OPTIONS .'[%s]" value="%s" />', esc_attr($type), esc_attr($key), esc_attr($value));
	echo($description ? '<p class="description">' . $description . '</p>' : '');
}

// Render a textarea
function memoria_render_textarea_field(array $args): void {
	$options = get_option(MEMORIA_OPTIONS, []);
	$key = $args['key'];
	$description = $args['description'] ?? '';
	$value = $options[$key] ?? $args['default'];
	$maxlength = $args['maxlength'] ?? null;
	$maxlength_attr = $maxlength ? sprintf(' maxlength="%d"', $maxlength) : '';
	printf('<textarea name="' . MEMORIA_OPTIONS .'[%s]" rows="5"%s>%s</textarea>', esc_attr($key), $maxlength_attr, esc_textarea($value));
	echo($description ? '<p class="description">' . $description . '</p>' : '');
}