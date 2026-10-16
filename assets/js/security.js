
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const form = document.getElementById("secPasswordForm");

    if (!form) return;

    const currentInput = document.getElementById("currentPassword");
    const newInput = document.getElementById("newPassword");
    const confirmInput = document.getElementById("confirmPassword");

    const passwordHint = document.getElementById("secPasswordHint");
    const confirmHint = document.getElementById("secConfirmHint");

    const errorBox = document.getElementById("secClientError");
    const submitButton = document.getElementById("secSubmitButton");

    // SHOW / HIDE PASSWORD

    document.querySelectorAll(".sec-toggle-password").forEach((button) => {
        const input = document.getElementById(button.dataset.target);
        if (!input) return;

        const label = button.getAttribute("aria-label").replace(/^(Show|Hide) /, "");
        const icon = button.querySelector("i");
        input.type = "password";

        function updateToggle() {
            const hasValue = input.value.length > 0;
            button.hidden = !hasValue;
            if (!hasValue) input.type = "password";
            const isVisible = input.type === "text";
            button.setAttribute("aria-pressed", String(isVisible));
            button.setAttribute("aria-label", `${isVisible ? "Hide" : "Show"} ${label}`);
            if (icon) {
                icon.className = isVisible ? "fa-regular fa-eye-slash" : "fa-regular fa-eye";
            }
        }

        input.addEventListener("input", updateToggle);
        input.addEventListener("change", updateToggle);
        input.addEventListener("focus", updateToggle);
        form.addEventListener("reset", () => {
            input.type = "password";
            requestAnimationFrame(updateToggle);
        });

        button.addEventListener("click", () => {
            if (!input.value) return;
            input.type = input.type === "password" ? "text" : "password";
            updateToggle();
            input.focus();
        });
        updateToggle();
    });

    function clearInputState(input) {
        input.classList.remove("sec-valid", "sec-invalid");
    }

    function showError(message) {
        errorBox.textContent = message;
        errorBox.hidden = false;
    }

    function clearError() {
        errorBox.textContent = "";
        errorBox.hidden = true;
    }

    // CHECK NEW PASSWORD

    function validateNewPassword() {
        const value = newInput.value;

        clearInputState(newInput);

        passwordHint.classList.remove(
            "sec-hint-success",
            "sec-hint-error"
        );

        if (!value) {
            passwordHint.textContent = "Use at least 12 characters.";
            return false;
        }

        const byteLength = new TextEncoder().encode(value).length;

        if (byteLength < 12) {
            passwordHint.textContent =
                "Password must contain at least 12 characters.";

            passwordHint.classList.add("sec-hint-error");
            newInput.classList.add("sec-invalid");

            return false;
        }

        if (byteLength > 128) {
            passwordHint.textContent =
                "Password must not exceed 128 bytes.";

            passwordHint.classList.add("sec-hint-error");
            newInput.classList.add("sec-invalid");

            return false;
        }

        passwordHint.textContent = "Password length requirement met.";
        passwordHint.classList.add("sec-hint-success");
        newInput.classList.add("sec-valid");

        return true;
    }

    // CHECK PASSWORD CONFIRMATION

    function validateConfirmation() {
        const newValue = newInput.value;
        const confirmValue = confirmInput.value;

        clearInputState(confirmInput);

        confirmHint.classList.remove(
            "sec-hint-success",
            "sec-hint-error"
        );

        if (!confirmValue) {
            confirmHint.textContent = "";
            return false;
        }

        if (newValue !== confirmValue) {
            confirmHint.textContent = "Passwords do not match.";
            confirmHint.classList.add("sec-hint-error");
            confirmInput.classList.add("sec-invalid");

            return false;
        }

        confirmHint.textContent = "Passwords match.";
        confirmHint.classList.add("sec-hint-success");
        confirmInput.classList.add("sec-valid");

        return true;
    }

    newInput.addEventListener("input", () => {
        validateNewPassword();
        validateConfirmation();
        clearError();
    });

    confirmInput.addEventListener("input", () => {
        validateConfirmation();
        clearError();
    });

    currentInput.addEventListener("input", () => {
        clearInputState(currentInput);
        clearError();
    });

    // FORM SUBMISSION

    form.addEventListener("submit", (event) => {
        clearError();

        if (!currentInput.value) {
            event.preventDefault();
            showError("Please enter your current password.");
            currentInput.focus();
            return;
        }

        if (!validateNewPassword()) {
            event.preventDefault();
            showError("Please enter a valid new password.");
            newInput.focus();
            return;
        }

        if (currentInput.value === newInput.value) {
            event.preventDefault();
            showError(
                "Your new password must be different from your current password."
            );
            newInput.focus();
            return;
        }

        if (!validateConfirmation()) {
            event.preventDefault();
            showError("Please confirm your new password correctly.");
            confirmInput.focus();
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = "Updating Password...";
    });
});
