<?php
/**
* Custom shortcodes for theme option content exposed over GraphQL
*
* @package memoria
* @since 1.0.0
*/

// Exit if accessed directly
if (!defined('ABSPATH')) { exit; }

// [b]bold text[/b] -> <strong>bold text</strong>
add_shortcode('b', function ($atts, $content = null): string {
	return '<strong>' . wp_kses_post(do_shortcode($content ?? '')) . '</strong>';
});

// [i]italic text[/i] -> <em>italic text</em>
add_shortcode('i', function ($atts, $content = null): string {
	return '<em>' . wp_kses_post(do_shortcode($content ?? '')) . '</em>';
});

// [a href="https://example.com"]link text[/a] -> <a href="...">link text</a>
add_shortcode('a', function ($atts, $content = null): string {
	$atts = shortcode_atts(['href' => '', 'target' => ''], $atts, 'a');
	$url = esc_url($atts['href']);
	$target = esc_attr($atts['target']);

	// Drop the link wrapper entirely if the href is missing or fails URL sanitization
	if (!$url) {
		return wp_kses_post(do_shortcode($content ?? ''));
	}

	// If target is defined, change link format
	if ($target) {
		return sprintf('<a href="%s" target="%s" rel="noreferrer">%s</a>', $url, $target, wp_kses_post(do_shortcode($content ?? '')));
	} else {
		return sprintf('<a href="%s">%s</a>', $url, wp_kses_post(do_shortcode($content ?? '')));
	}
});
