import './bootstrap';

const maximumImageDimension = 1920;
const imageQuality = 0.8;

const compressImage = (file) => new Promise((resolve, reject) => {
	const image = new Image();
	const objectUrl = URL.createObjectURL(file);

	image.onload = () => {
		const scale = Math.min(1, maximumImageDimension / Math.max(image.width, image.height));
		const canvas = document.createElement('canvas');
		canvas.width = Math.max(1, Math.round(image.width * scale));
		canvas.height = Math.max(1, Math.round(image.height * scale));
		canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
		canvas.toBlob((blob) => {
			URL.revokeObjectURL(objectUrl);
			if (!blob) {
				reject(new Error('No se pudo preparar una fotografía.'));
				return;
			}

			resolve(new File([blob], `${file.name.replace(/\.[^.]+$/, '')}.jpg`, {
				type: 'image/jpeg',
				lastModified: file.lastModified,
			}));
		}, 'image/jpeg', imageQuality);
	};
	image.onerror = () => {
		URL.revokeObjectURL(objectUrl);
		reject(new Error('No se pudo leer una fotografía.'));
	};
	image.src = objectUrl;
});

const compressFormImages = (form) => {
	const fileInputs = [...form.querySelectorAll('input[type="file"]:not([data-skip-compress])')];
	const selectedFileInputs = fileInputs.filter((input) => input.files.length);

	return Promise.all(selectedFileInputs.map(async (input) => {
		const compressedFiles = await Promise.all([...input.files].map(compressImage));
		const dataTransfer = new DataTransfer();
		compressedFiles.forEach((file) => dataTransfer.items.add(file));
		input.files = dataTransfer.files;
	}));
};

// Single entry point for submitting the multi-step vehicle forms. Validation,
// image compression and the actual submit all happen here, exactly once, so a
// double click (or compression finishing late) can never POST the form twice.
window.submitFormOnce = async (form) => {
	if (form.dataset.submitting === 'true' || !form.reportValidity()) {
		return;
	}

	form.dataset.submitting = 'true';
	const submitButton = form.querySelector('button[type="submit"]');
	submitButton?.setAttribute('disabled', 'disabled');

	try {
		if (form.hasAttribute('data-compress-images')) {
			await compressFormImages(form);
		}
	} catch (error) {
		delete form.dataset.submitting;
		submitButton?.removeAttribute('disabled');
		window.alert(error.message);
		return;
	}

	// Fields on the inactive step are disabled so they skip validation above;
	// re-enable them so their values are included in the submission.
	form.querySelectorAll('fieldset').forEach((fieldset) => {
		fieldset.disabled = false;
	});
	form.submit();
};

// Restore the forms if the user comes back via the browser's back button.
window.addEventListener('pageshow', (event) => {
	if (!event.persisted) {
		return;
	}

	document.querySelectorAll('form[data-submitting]').forEach((form) => {
		delete form.dataset.submitting;
		form.querySelector('button[type="submit"]')?.removeAttribute('disabled');
	});
});
