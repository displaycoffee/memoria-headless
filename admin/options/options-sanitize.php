<?php
/**
* Functions to sanitize fields
*
* @package memoria
* @since 1.0.0
*/

// Exit if accessed directly
if (!defined('ABSPATH')) { exit; }

// Sanitize all theme options before saving, using each field's own callback
function memoria_sanitize_options(array $input, object $fields): array {
	// Flatten the nested field definitions into a key => field map
	$field_map = [];
	foreach ($fields as $section) {
		foreach ($section as $key => $field) {
			$field_map[$key] = $field;
		}
	}

	$clean = [];
	foreach ($input as $key => $value) {
		// Skip empty submissions so the key stays absent and the field's default can apply
		if ($value === '') {
			continue;
		}

		$field = $field_map[$key] ?? null;
		$callback = $field->sanitize_callback ?? 'sanitize_text_field';
		$value = $callback($value);

		// Enforce the field's maxlength server-side, so saved values can never exceed it
		if ($field && !empty($field->maxlength)) {
			$value = mb_substr($value, 0, $field->maxlength);
		}

		$clean[$key] = $value;
	}

	return $clean;
}