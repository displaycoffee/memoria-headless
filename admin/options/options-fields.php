<?php
/**
* Helpers for building fields
*
* @package memoria
* @since 1.0.0
*/

// Exit if accessed directly
if (!defined('ABSPATH')) { exit; }

// Loop through fields and add settings
function memoria_add_fields(object $fields, string $section): void {
	foreach ($fields as $key => $label) {
		add_settings_field($key, $label->label, $label->render, MEMORIA_SLUG, $section, [
			'key' => $key,
			'type' => $label->type ?? 'text',
			'description' => $label->description ?? '',
			'default' => $label->default ?? '',
			'maxlength' => $label->maxlength ?? null,
			'is_icon' => $label->is_icon ?? false
		]);
	}
}

// Build GraphQL type map from a fields object
function memoria_add_types(object $fields, array &$types): void {
	foreach ($fields as $key => $label) {
		// Image fields store an attachment ID — expose them as MediaItem so the
		// frontend can query sourceUrl, altText, mediaDetails, etc. directly
		$types[$label->graphQL] = ['type' => !empty($label->is_image) ? 'MediaItem' : 'String'];
	}
}