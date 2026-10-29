
/* ==========================================
   PUREVIA COOKIE CONSENT
   Centered Popup -> Bottom Banner
========================================== */

"use strict";

document.addEventListener("DOMContentLoaded", function () {

    const COOKIE_NAME = "purevia_cookie_consent_v1";
    const DISMISS_KEY = "purevia_cookie_popup_dismissed_v1";
    const COOKIE_DAYS = 180;

    /* ======================================
       ELEMENTS
    ====================================== */

    const banner = document.getElementById("pvCookieBanner");
    const overlay = document.getElementById("pvCookieOverlay");
    const modal = document.getElementById("pvCookiePreferences");

    const acceptAll = document.getElementById("pvCookieAcceptAll");
    const rejectOptional = document.getElementById("pvCookieReject");
    const manage = document.getElementById("pvCookieManage");

    const footerSettings = document.getElementById(
        "pvOpenCookieSettings"
    );

    const closeButton = document.getElementById("pvCookieClose");
    const saveButton = document.getElementById("pvCookieSave");
    const modalAccept = document.getElementById(
        "pvCookieModalAccept"
    );

    const analyticsInput = document.getElementById(
        "pvCookieAnalytics"
    );

    if (!banner || !overlay || !modal) {
        return;
    }

    let previousFocus = null;

    /* ======================================
       SESSION DISMISSAL
    ====================================== */

    function wasDismissed() {
        try {
            return sessionStorage.getItem(DISMISS_KEY) === "1";
        } catch (error) {
            return false;
        }
    }

    function markDismissed() {
        try {
            sessionStorage.setItem(DISMISS_KEY, "1");
        } catch (error) {
            // Consent still works if storage is unavailable.
        }
    }

    function clearDismissal() {
        try {
            sessionStorage.removeItem(DISMISS_KEY);
        } catch (error) {
            // No action needed.
        }
    }

    /* ======================================
       READ COOKIE
    ====================================== */

    function readCookie(name) {
        const prefix = encodeURIComponent(name) + "=";

        const item = document.cookie
            .split("; ")
            .find(row => row.startsWith(prefix));

        if (!item) {
            return null;
        }

        try {
            return decodeURIComponent(
                item.substring(prefix.length)
            );
        } catch (error) {
            return null;
        }
    }

    /* ======================================
       VALIDATE SAVED CONSENT
    ====================================== */

    function getConsent() {
        const value = readCookie(COOKIE_NAME);

        if (!value) {
            return null;
        }

        try {
            const saved = JSON.parse(value);

            if (
                !saved ||
                saved.version !== 1 ||
                saved.essential !== true ||
                typeof saved.analytics !== "boolean"
            ) {
                return null;
            }

            return saved;
        } catch (error) {
            return null;
        }
    }

    /* ======================================
       OPEN CENTERED POPUP
    ====================================== */

    function openPreferences() {
        previousFocus = document.activeElement;

        const saved = getConsent();

        if (analyticsInput) {
            analyticsInput.checked = Boolean(
                saved &&
                saved.analytics &&
                !analyticsInput.disabled
            );
        }

        // Never show popup and banner together.
        banner.hidden = true;
        overlay.hidden = false;

        document.body.style.overflow = "hidden";

        modal.focus();
    }

    /* ======================================
       CLOSE POPUP
    ====================================== */

    function closePreferences() {
        overlay.hidden = true;
        document.body.style.overflow = "";

        if (
            previousFocus &&
            typeof previousFocus.focus === "function"
        ) {
            previousFocus.focus();
        }

        previousFocus = null;
    }

    /* ======================================
       DISMISS WITHOUT CONSENT
    ====================================== */

    function dismissPreferences() {

        closePreferences();

        if (!getConsent()) {

            // Remember only for this browser tab/session.
            markDismissed();

            // Display smaller banner at the bottom.
            banner.hidden = false;

        } else {

            banner.hidden = true;

        }
    }

    /* ======================================
       SAVE EXPLICIT COOKIE CHOICE
    ====================================== */

    function saveConsent(analyticsAllowed) {

        const consent = {
            version: 1,
            essential: true,

            // Optional analytics are not implemented yet.
            analytics: Boolean(
                analyticsInput &&
                !analyticsInput.disabled &&
                analyticsAllowed === true
            ),

            updatedAt: new Date().toISOString()
        };

        const expires = new Date();

        expires.setDate(
            expires.getDate() + COOKIE_DAYS
        );

        const secure = window.location.protocol === "https:"
            ? "; Secure"
            : "";

        document.cookie =
            encodeURIComponent(COOKIE_NAME) + "=" +
            encodeURIComponent(JSON.stringify(consent)) +
            "; Expires=" + expires.toUTCString() +
            "; Path=/" +
            "; SameSite=Lax" +
            secure;

        // No need to remember popup dismissal after consent.
        clearDismissal();

        closePreferences();

        banner.hidden = true;

        document.dispatchEvent(
            new CustomEvent("purevia:cookie-consent", {
                detail: consent
            })
        );
    }

    /* ======================================
       BANNER BUTTON EVENTS
    ====================================== */

    if (acceptAll) {
        acceptAll.addEventListener("click", function () {
            saveConsent(true);
        });
    }

    if (rejectOptional) {
        rejectOptional.addEventListener("click", function () {
            saveConsent(false);
        });
    }

    if (manage) {
        manage.addEventListener(
            "click",
            openPreferences
        );
    }

    /* ======================================
       FOOTER COOKIE SETTINGS
    ====================================== */

    if (footerSettings) {
        footerSettings.addEventListener(
            "click",
            openPreferences
        );
    }

    /* ======================================
       POPUP BUTTON EVENTS
    ====================================== */

    if (closeButton) {
        closeButton.addEventListener(
            "click",
            dismissPreferences
        );
    }

    if (saveButton) {
        saveButton.addEventListener(
            "click",
            function () {
                saveConsent(
                    Boolean(
                        analyticsInput &&
                        analyticsInput.checked
                    )
                );
            }
        );
    }

    if (modalAccept) {
        modalAccept.addEventListener(
            "click",
            function () {
                saveConsent(true);
            }
        );
    }

    /* ======================================
       CLICK OUTSIDE POPUP
    ====================================== */

    overlay.addEventListener("click", function (event) {

        if (event.target === overlay) {
            dismissPreferences();
        }

    });

    /* ======================================
       KEYBOARD ACCESSIBILITY
    ====================================== */

    document.addEventListener("keydown", function (event) {

        if (overlay.hidden) {
            return;
        }

        if (event.key === "Escape") {
            event.preventDefault();
            dismissPreferences();
            return;
        }

        if (event.key !== "Tab") {
            return;
        }

        const focusable = Array.from(
            modal.querySelectorAll(
                'button:not([disabled]), ' +
                'input:not([disabled]), ' +
                'a[href]'
            )
        ).filter(element => (
            !element.hidden &&
            element.getClientRects().length > 0
        ));

        if (!focusable.length) {
            event.preventDefault();
            modal.focus();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (
            event.shiftKey &&
            (
                document.activeElement === first ||
                document.activeElement === modal
            )
        ) {
            event.preventDefault();
            last.focus();
        } else if (
            !event.shiftKey &&
            document.activeElement === last
        ) {
            event.preventDefault();
            first.focus();
        }
    });

    /* ======================================
       INITIALIZE COOKIE CONSENT
    ====================================== */

    const savedConsent = getConsent();

    if (savedConsent) {

        // Customer already chose cookies.
        banner.hidden = true;
        overlay.hidden = true;

    } else if (wasDismissed()) {

        // Customer dismissed the popup earlier
        // during this browser session.
        overlay.hidden = true;
        banner.hidden = false;

    } else {

        // First visit without a saved choice:
        // show the centered popup automatically.
        openPreferences();

    }

});
