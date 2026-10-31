
/* =========================================================
   PUREVIA MANAGE ACCOUNT
   ADMIN AND STAFF

   FEATURES:
   - Profile photo validation
   - Live password requirement checking
   - Red X for unmet requirements
   - Green check for satisfied requirements
   - Uses rules provided by PHP security policy
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       PROFILE PHOTO VALIDATION
       Preserves existing functionality
    ===================================================== */

    const photoInput =
        document.getElementById("maPhoto");

    const photoName =
        document.getElementById("maPhotoName");

    const photoSave =
        document.getElementById("maPhotoSave");

    if (photoInput && photoName && photoSave) {

        photoInput.addEventListener("change", function () {

            const file = photoInput.files[0];

            photoSave.hidden = !file;

            photoName.textContent =
                file ? file.name : "";

            photoInput.setCustomValidity("");

            if (
                file &&
                (
                    ![
                        "image/jpeg",
                        "image/png",
                        "image/webp"
                    ].includes(file.type) ||

                    file.size > 5 * 1024 * 1024
                )
            ) {

                const message =
                    "Choose a JPG, PNG, or WebP image up to 5 MB.";

                photoInput.setCustomValidity(message);

                photoName.textContent = message;

                photoSave.hidden = true;
            }

        });
    }

    /* =====================================================
       LIVE PASSWORD REQUIREMENTS
    ===================================================== */

    const passwordInput =
        document.getElementById("maNew");

    const requirementsList =
        document.getElementById("maRequirements");

    if (!passwordInput || !requirementsList) {
        return;
    }

    const rules = Array.from(
        requirementsList.querySelectorAll(
            ".ma-rule[data-rule]"
        )
    );

    /* =====================================================
       COUNT PASSWORD CHARACTER TYPES
    ===================================================== */

    function countMatches(password, expression) {

        const matches = password.match(expression);

        return matches ? matches.length : 0;
    }

    /* =====================================================
       CHECK EACH PASSWORD REQUIREMENT
    ===================================================== */

    function checkRule(password, ruleName, minimum) {

        switch (ruleName) {

            case "length":

                return (
                    Array.from(password).length >= minimum
                );

            case "uppercase":

                return (
                    countMatches(password, /[A-Z]/g)
                    >= minimum
                );

            case "lowercase":

                return (
                    countMatches(password, /[a-z]/g)
                    >= minimum
                );

            case "number":

                return (
                    countMatches(password, /[0-9]/g)
                    >= minimum
                );

            case "symbol":

                return (
                    countMatches(
                        password,
                        /[^A-Za-z0-9]/gu
                    ) >= minimum
                );

            case "byte_limit": {

                const bytes = new TextEncoder()
                    .encode(password);

                return (
                    bytes.length <= 72 &&
                    !password.includes("\0")
                );
            }

            default:
                return false;
        }
    }

    /* =====================================================
       UPDATE RED / GREEN INDICATORS
    ===================================================== */

    function updatePasswordRequirements() {

        const password = passwordInput.value;

        let allPassed = true;

        rules.forEach(function (rule) {

            const ruleName =
                rule.dataset.rule;

            const minimum =
                parseInt(
                    rule.dataset.minimum || "1",
                    10
                );

            const passed = checkRule(
                password,
                ruleName,
                minimum
            );

            rule.classList.toggle(
                "ma-rule-met",
                passed
            );

            rule.classList.toggle(
                "ma-rule-unmet",
                !passed
            );

            const icon =
                rule.querySelector(".ma-rule-icon");

            if (icon) {

                icon.textContent =
                    passed ? "✓" : "×";
            }

            if (!passed) {
                allPassed = false;
            }

        });

        passwordInput.setAttribute(
            "aria-invalid",
            password.length > 0 && !allPassed
                ? "true"
                : "false"
        );

        return allPassed;
    }

    /* =====================================================
       UPDATE AS USER TYPES
    ===================================================== */

    passwordInput.addEventListener(
        "input",
        updatePasswordRequirements
    );

    passwordInput.addEventListener(
        "change",
        updatePasswordRequirements
    );

    /* =====================================================
       INITIAL STATE
       Unmet rules start red
    ===================================================== */

    updatePasswordRequirements();

});
