"use strict";

document.addEventListener("DOMContentLoaded", function () {

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
            const birthDate = document.getElementById("birthDate");

            firstName.value = firstName.value.trim();
            lastName.value = lastName.value.trim();
            phone.value = phone.value.trim();

            if (!firstName.value || !lastName.value) {
                event.preventDefault();
                alert("First name and last name are required.");
                return;
            }

            if (phone.value) {
                const phonePattern = /^[0-9+\s()\-]+$/;

                if (!phonePattern.test(phone.value)) {
                    event.preventDefault();
                    alert("Please enter a valid phone number.");
                    phone.focus();
                    return;
                }
            }

            if (birthDate && birthDate.value) {
                const selected = new Date(birthDate.value + "T00:00:00");
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (
                    Number.isNaN(selected.getTime()) ||
                    selected > today ||
                    selected < new Date("1900-01-01T00:00:00")
                ) {
                    event.preventDefault();
                    alert("Please enter a valid birth date.");
                    birthDate.focus();
                    return;
                }
            }

            if (!profileForm.checkValidity()) {
                event.preventDefault();
                profileForm.reportValidity();
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

        photoInput.addEventListener("change", function () {

            const file = photoInput.files[0];

            if (!file) return;

            if (!allowedTypes.includes(file.type)) {
                alert("Please select a JPG, PNG, or WebP image.");
                photoInput.value = "";
                return;
            }

            if (file.size <= 0 || file.size > maxSize) {
                alert("The photo must be 5 MB or smaller.");
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

            if (!addressForm.checkValidity()) {
                event.preventDefault();
                addressForm.reportValidity();
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