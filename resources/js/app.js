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

document.querySelectorAll('form[data-compress-images]').forEach((form) => {
	form.addEventListener('submit', async (event) => {
		if (form.dataset.imagesCompressed === 'true') {
			delete form.dataset.imagesCompressed;
			return;
		}

		event.preventDefault();
		const fileInputs = [...form.querySelectorAll('input[type="file"]')];
		const submitButton = form.querySelector('button[type="submit"]');
		submitButton?.setAttribute('disabled', 'disabled');

		try {
			await Promise.all(fileInputs.map(async (input) => {
				if (!input.files.length) {
					return;
				}

				const compressedFiles = await Promise.all([...input.files].map(compressImage));
				const dataTransfer = new DataTransfer();
				compressedFiles.forEach((file) => dataTransfer.items.add(file));
				input.files = dataTransfer.files;
			}));
			form.dataset.imagesCompressed = 'true';
			form.requestSubmit();
		} catch (error) {
			submitButton?.removeAttribute('disabled');
			window.alert(error.message);
		}
	});
});
