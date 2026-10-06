/* Selectors and variables for site icon functionality */
const mp = 'media-picker';
const mediaPickerOptions: MediaPickerOptionsType = {
	classes: {
		hidden: 'hidden',
		hasMedia: 'has-media',
	},
	selectors: {
		container: `.${mp}`,
		select: `.${mp}-select`,
		input: `.${mp}-input`,
		preview: `.${mp}-preview`,
		previewApp: `.app-${mp}-preview`,
		previewBrowser: `.browser-${mp}-preview`,
		remove: `.${mp}-remove`,
	},
	text: {
		image: {
			choose: 'Choose an Image',
			change: 'Change Image',
			select: 'Select Image',
			use: 'Use Image',
		},
		icon: {
			choose: 'Choose a Site Icon',
			change: 'Change Site Icon',
			select: 'Select Site Icon',
			use: 'Use Site Icon',
		},
	},
};

/* Function to initialize media picker */
const initMediaPicker = (picker: HTMLElement) => {
	const { classes, selectors, text } = mediaPickerOptions;
	let frame: WPMediaFrameType | undefined;

	// Query selectors
	const selectButton = picker.querySelector<HTMLElement>(selectors.select);
	const removeButton = picker.querySelector<HTMLElement>(selectors.remove);
	const input = picker.querySelector<HTMLInputElement>(selectors.input);
	const preview = picker.querySelector<HTMLElement>(selectors.preview);
	const previewApp = picker.querySelector<HTMLImageElement>(selectors.previewApp);
	const previewBrowser = picker.querySelector<HTMLImageElement>(selectors.previewBrowser);

	// Check if any elements are present before proceeding
	if (!selectButton || !removeButton || !input || !preview || !previewApp) return;

	// Is the media-picker an icon selector?
	const isIcon = preview.classList.contains('site-icon-preview');

	// Toggle details when media is added or remove
	const toggleSelection = (action: 'add' | 'remove', id: string, url: string) => {
		const isAdd = action == 'add';
		input.value = id;
		selectButton.textContent = isIcon ? (isAdd ? text.icon.change : text.icon.choose) : isAdd ? text.image.change : text.image.choose;
		preview.classList.replace(isAdd ? classes.hidden : classes.hasMedia, isAdd ? classes.hasMedia : classes.hidden);
		previewApp.setAttribute('src', url);
		if (previewBrowser) previewBrowser.setAttribute('src', url);
		if (isAdd) {
			removeButton.classList.remove(classes.hidden);
		} else {
			removeButton.classList.add(classes.hidden);
		}
	};

	// Add click functionality for media picker
	selectButton.onclick = (e: MouseEvent) => {
		e.preventDefault();

		// If there is a frame, open it
		if (frame) {
			frame.open();
			return;
		}

		// Set frame details
		const f = (frame = wp.media({
			title: isIcon ? text.icon.select : text.image.select,
			button: { text: isIcon ? text.icon.use : text.image.use },
			multiple: false,
		}));

		// Select media element
		f.on('select', () => {
			const a = f.state().get('selection').first().toJSON();
			toggleSelection('add', String(a.id), a.url);
		});

		// Then toggle open again
		f.open();
	};

	// Remove media if set
	removeButton.onclick = () => {
		toggleSelection('remove', '', '');
	};
};

/* Add a live character count under any textarea that declares a maxlength */
const initCharacterCounts = () => {
	const textareas = document.querySelectorAll<HTMLTextAreaElement>('.memoria-theme-options textarea[maxlength]');

	textareas.forEach((textarea) => {
		const max = textarea.maxLength;
		const count = document.createElement('p');
		count.className = 'character-count description';

		const updateCount = () => {
			count.textContent = `${textarea.value.length} / ${max}`;
		};

		updateCount();
		textarea.insertAdjacentElement('afterend', count);
		textarea.addEventListener('input', updateCount);
	});
};

document.addEventListener('DOMContentLoaded', () => {
	// Initialize media picker
	const mediaPicker = document.querySelectorAll<HTMLElement>(mediaPickerOptions.selectors.container);
	if (mediaPicker && mediaPicker.length !== 0) {
		mediaPicker.forEach((picker) => {
			initMediaPicker(picker);
		});
	}

	// Initialize character counts for limited textareas
	initCharacterCounts();
});
