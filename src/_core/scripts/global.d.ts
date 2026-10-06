/* Packages */
import type { UtilsType as UtilsSharedType, UtilsBrowserType as UtilsSharedBrowserType } from '@displaycoffee/scripts/utils-types';

/* Type definitions */
type ObjectString = {
	[key: string]: string;
};

type ObjectPrimitive = {
	[key: string]: Primitive;
};

type Primitive = string | number | boolean;

type Theme = {
	bps: {
		bp01: Primitive;
		bp02: Primitive;
		bp03: Primitive;
		bp04: Primitive;
	};
	colors: {
		color01: Primitive;
		color02: Primitive;
		color03: Primitive;
		color04: Primitive;
		color05: Primitive;
		color06: Primitive;
		color07: Primitive;
		color08: Primitive;
		color09: Primitive;
		color10: Primitive;
		color11: Primitive;
		color12: Primitive;
	};
};

type Utils = UtilsSharedType;

type UtilsBrowser = UtilsSharedBrowserType;

/* WordPress type definitions */
type MediaPickerOptions = {
	classes: ObjectString;
	selectors: ObjectString;
	text: {
		[key: string]: ObjectString;
	};
};

type WP = {
	media(options: { title: string; button: { text: string }; multiple: boolean }): WPMediaFrame;
};

type WPMediaFrame = {
	open(): void;
	on(event: string, callback: () => void): void;
	state(): { get(key: string): { first(): { toJSON(): WPMediaAttachment } } };
};

type WPMediaAttachment = WPMediaFrame & {
	id: number;
	url: string;
	[key: string]: unknown;
	wp: WP;
};

declare global {
	// Declare global types
	type ObjectStringType = ObjectString;

	type ObjectPrimitiveType = ObjectPrimitive;

	type ThemeType = Theme;

	type UtilsType = Utils;

	type UtilsBrowserType = UtilsBrowser;

	// Declare global prop types
	type ObjectPrimitiveProps = ObjectPrimitive;

	// Declare global WordPress types
	type MediaPickerOptionsType = MediaPickerOptions;

	type WPMediaFrameType = WPMediaFrame;

	const wp: WP;
}

/* Export global types */
export {};
