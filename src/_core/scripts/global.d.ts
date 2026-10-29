/* Type definitions */
type ObjectString = {
	[key: string]: string;
};

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

	// Declare global WordPress types
	type MediaPickerOptionsType = MediaPickerOptions;

	type WPMediaFrameType = WPMediaFrame;

	const wp: WP;
}

/* Export global types */
export {};
