"use strict";

document.addEventListener("DOMContentLoaded", function () {

    const fieldNames = {
        first_name: 'first name', last_name: 'last name', phone: 'phone number',
        recipient_first_name: 'first name', recipient_last_name: 'last name',
        country: 'country', addressRegion: 'region', province: 'province', city: 'city',
        barangay: 'barangay', postal_code: 'postal code', address_line1: 'street address',
        address_line2: 'address line 2', birth_date: 'birth date', gender: 'gender',
        profile_photo: 'profile photo'
    };
    function errorLabel(input) {
        if (!input.id) input.id = 'account-' + input.name;
        const id = input.id + 'Error';
        let label = document.getElementById(id);
        if (!label) {
            if (input.parentElement.classList.contains('account-name-fields')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'account-input-group';
                input.before(wrapper); wrapper.appendChild(input);
            }
            label = document.createElement('span');
            label.id = id; label.className = 'account-field-error';
            label.setAttribute('role', 'alert'); label.hidden = true;
            input.after(label);
            const described = new Set((input.getAttribute('aria-describedby') || '').split(' ').filter(Boolean));
            described.add(id); input.setAttribute('aria-describedby', [...described].join(' '));
        }
        return label;
    }
    function setError(input, message) {
        const label = errorLabel(input);
        label.textContent = message; label.hidden = !message;
        input.setAttribute('aria-invalid', String(Boolean(message)));
    }
    function fields(form) {
        return [...form.querySelectorAll('input[id]:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]),select[id],textarea[id]')]
            .filter(input => !input.readOnly);
    }
    function message(input) {
        const name = input.name || input.id;
        const title = fieldNames[name] || 'value';
        const value = input.type === 'file' ? input.files.length : input.value.trim();
        if (input.type === 'date' && input.validity.badInput) return 'Please enter a valid birth date.';
        if (!value && input.getAttribute('aria-required') === 'true') {
            return 'Please ' + (input.tagName === 'SELECT' || input.type === 'file' ? 'select your ' : 'enter your ') + title + '.';
        }
        if (!value) return '';
        if (input.maxLength > 0 && [...String(value)].length > input.maxLength) return 'Use no more than ' + input.maxLength + ' characters.';
        if (name === 'phone' && !/^[0-9+\s()\-]+$/.test(value)) return 'Please enter a valid phone number.';
        if (name === 'gender' && !['Female','Male','Non-binary','Prefer not to say'].includes(value)) return 'Please select a valid gender option.';
        if (input.type === 'date' && (input.validity.badInput || input.validity.rangeOverflow || input.validity.rangeUnderflow)) return 'Please enter a valid birth date.';
        if (input.type === 'email' && input.validity.typeMismatch) return 'Please enter a valid email address.';
        if (input.type === 'file') {
            const file = input.files[0];
            if (!['image/jpeg','image/png','image/webp'].includes(file.type)) return 'Please select a JPG, PNG, or WebP image.';
            if (file.size <= 0 || file.size > 5 * 1024 * 1024) return 'The photo must be 5 MB or smaller.';
        }
        return '';
    }
    function validate(form) {
        let first = null;
        fields(form).forEach(input => {
            const error = message(input); setError(input, error);
            if (error && !first) first = input;
        });
        if (first) first.focus();
        return !first;
    }
    function clear(form) {
        fields(form).forEach(input => setError(input, ''));
        form.querySelectorAll('.account-field-error').forEach(label => { label.textContent = ''; label.hidden = true; });
        form.querySelectorAll('[aria-invalid="true"]').forEach(input => input.setAttribute('aria-invalid', 'false'));
    }
    window.PureViaAccountValidation = { setError, validate, clear };
    ['profileEditForm','profilePhotoForm','addressModalForm'].forEach(id => {
        const form = document.getElementById(id);
        if (!form) return;
        const errors = JSON.parse(form.dataset.fieldErrors || '{}');
        fields(form).forEach(input => setError(input, errors[input.name] || ''));
    });

    /* =========================================
       PROFILE EDIT FORM
    ========================================= */

    const profileForm = document.getElementById("profileEditForm");
    const saveProfileBtn = document.getElementById("saveProfileBtn");

    if (profileForm && saveProfileBtn) {

        profileForm.addEventListener("submit", function (event) {

            const firstName = document.getElementById("firstName");
            const lastName = document.getElementById("lastName");
            const phone = document.getElementById("phone");

            firstName.value = firstName.value.trim();
            lastName.value = lastName.value.trim();
            phone.value = phone.value.trim();

            if (!validate(profileForm)) {
                event.preventDefault();
                return;
            }

            if (saveProfileBtn.disabled) {
                event.preventDefault();
                return;
            }

            saveProfileBtn.disabled = true;
            saveProfileBtn.textContent = "Saving Changes...";
        });
    }

    /* =========================================
       PROFILE PHOTO UPLOAD
    ========================================= */

    const photoForm = document.getElementById("profilePhotoForm");
    const photoInput = document.getElementById("profilePhotoInput");
    const uploadPhotoBtn = document.getElementById("uploadPhotoBtn");
    const photoPreview = document.getElementById("profilePhotoPreview");

    if (photoForm && photoInput && uploadPhotoBtn && photoPreview) {

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        const maxSize = 5 * 1024 * 1024;

        let previewUrl = null;

        uploadPhotoBtn.addEventListener("click", function () {
            if (!uploadPhotoBtn.disabled) {
                photoInput.click();
            }
        });

        photoForm.addEventListener('submit', event => {
            if (!validate(photoForm)) event.preventDefault();
        });

        photoInput.addEventListener("change", function () {

            const file = photoInput.files[0];

            if (!file) return;

            if (!allowedTypes.includes(file.type)) {
                setError(photoInput, 'Please select a JPG, PNG, or WebP image.');
                photoInput.value = "";
                return;
            }

            if (file.size <= 0 || file.size > maxSize) {
                setError(photoInput, 'The photo must be 5 MB or smaller.');
                photoInput.value = "";
                return;
            }

            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }

            previewUrl = URL.createObjectURL(file);

            photoPreview.replaceChildren();

            const image = document.createElement("img");
            image.src = previewUrl;
            image.alt = "Selected profile photo preview";

            photoPreview.appendChild(image);

            uploadPhotoBtn.disabled = true;
            uploadPhotoBtn.textContent = "Uploading...";

            // Submit after the file is selected and validated.
            photoForm.requestSubmit();
        });

        window.addEventListener("pagehide", function () {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        });
    }

    /* =========================================
       ADDRESS FORM
    ========================================= */

    const addressForm = document.getElementById("addressForm");
    const saveAddressBtn = document.getElementById("saveAddressBtn");

    if (addressForm && saveAddressBtn) {

        addressForm.addEventListener("submit", function (event) {

            const fields = addressForm.querySelectorAll(
                'input:not([type="hidden"]):not([type="checkbox"])'
            );

            fields.forEach(function (field) {
                field.value = field.value.trim();
            });

            if (!validate(addressForm)) {
                event.preventDefault();
                return;
            }

            if (saveAddressBtn.disabled) {
                event.preventDefault();
                return;
            }

            saveAddressBtn.disabled = true;
            saveAddressBtn.textContent = "Saving...";
        });
    }

    /* =========================================
       ADDRESS DELETE CONFIRMATION
    ========================================= */

    document.querySelectorAll(
        "[data-address-delete-form]"
    ).forEach(function (form) {

        form.addEventListener("submit", function (event) {

            const confirmed = confirm(
                "Are you sure you want to delete this address?"
            );

            if (!confirmed) {
                event.preventDefault();
            }
        });
    });

});
