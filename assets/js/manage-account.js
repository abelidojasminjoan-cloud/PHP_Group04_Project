document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('maPhoto');
    const name = document.getElementById('maPhotoName');
    const save = document.getElementById('maPhotoSave');
    if (!input || !name || !save) return;
    input.addEventListener('change', () => {
        const file = input.files[0];
        save.hidden = !file;
        name.textContent = file ? file.name : '';
        input.setCustomValidity('');
        if (file && (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024)) {
            input.setCustomValidity('Choose a JPG, PNG, or WebP image up to 5 MB.');
            name.textContent = 'Choose a JPG, PNG, or WebP image up to 5 MB.';
            save.hidden = true;
        }
    });
});
