
<?php
// includes/policy_confirmation.php

if (
    !isset($policyType) ||
    !in_array($policyType, ['terms', 'privacy'], true)
) {
    throw new LogicException('Invalid policy type.');
}

$policyName = $policyType === 'terms'
    ? 'Terms and Conditions'
    : 'Privacy Policy';

$statement = $policyType === 'terms'
    ? 'I have read and agree to the Terms and Conditions.'
    : 'I have read and acknowledge the Privacy Policy.';

$confirmed = !empty(
    $_SESSION['policy_confirmation'][$policyType]
);

$policyUrl = htmlspecialchars(
    BASE_URL . '/actions/auth/policy_confirm.php',
    ENT_QUOTES,
    'UTF-8'
);
?>

<section class="pv-policy-confirm">

    <h2>
        Acknowledge <?= htmlspecialchars($policyName) ?>
    </h2>

    <p>
        Please review the document above before continuing.
    </p>

    <form
        class="pv-policy-form"
        action="<?= $policyUrl ?>"
        method="POST"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION['csrf_token'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <input
            type="hidden"
            name="policy"
            value="<?= htmlspecialchars($policyType) ?>"
        >

        <label class="pv-policy-confirm-label">
            <input
                type="checkbox"
                name="confirmed"
                value="1"
                <?= $confirmed ? 'checked' : '' ?>
            >

            <span>
                <?= htmlspecialchars($statement) ?>
            </span>
        </label>

        <p
            class="pv-policy-save-status"
            role="status"
            aria-live="polite"
        >
            <?= $confirmed
                ? 'Acknowledgment saved.'
                : 'Please check the box to confirm.' ?>
        </p>

    </form>

</section>

<script>
(function () {
    "use strict";

    const form = document.currentScript
        .previousElementSibling
        .querySelector(".pv-policy-form");

    const checkbox = form.querySelector(
        'input[name="confirmed"]'
    );

    const status = form.querySelector(
        ".pv-policy-save-status"
    );

    let lastSaved = checkbox.checked;

    checkbox.addEventListener("change", async function () {

        const intendedValue = checkbox.checked;

        checkbox.disabled = true;
        status.textContent = "Saving...";

        const formData = new FormData(form);
        formData.set(
            "confirmed",
            intendedValue ? "1" : "0"
        );

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    "Accept": "application/json"
                }
            });

            if (!response.ok) {
                throw new Error("Unable to save confirmation.");
            }

            const data = await response.json();

            if (
                data.success !== true ||
                data.confirmed !== intendedValue
            ) {
                throw new Error("Unexpected server response.");
            }

            lastSaved = intendedValue;

            status.textContent = intendedValue
                ? "Acknowledgment saved."
                : "Acknowledgment removed.";

        } catch (error) {

            checkbox.checked = lastSaved;

            status.textContent =
                "Unable to save. Please try again.";

        } finally {
            checkbox.disabled = false;
        }
    });
})();
</script>
