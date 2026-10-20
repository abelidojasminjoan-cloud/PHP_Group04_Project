(() => {
    let dialog;
    let previousFocus;

    function show(title, message) {
        if (!dialog) {
            dialog = document.createElement('dialog');
            dialog.className = 'auth-loading-dialog';
            dialog.setAttribute('aria-labelledby', 'authLoadingTitle');
            dialog.setAttribute('aria-describedby', 'authLoadingMessage');
            dialog.setAttribute('tabindex', '-1');

            const spinner = document.createElement('div');
            spinner.className = 'auth-loading-spinner';
            spinner.setAttribute('aria-hidden', 'true');

            const heading = document.createElement('h2');
            heading.id = 'authLoadingTitle';

            const description = document.createElement('p');
            description.id = 'authLoadingMessage';

            dialog.append(spinner, heading, description);
            dialog.addEventListener('cancel', event => event.preventDefault());
            dialog.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
            document.body.append(dialog);
        }

        dialog.querySelector('h2').textContent = title;
        dialog.querySelector('p').textContent = message;
        if (!dialog.open) {
            previousFocus = document.activeElement;
            dialog.showModal();
        }
        document.body.classList.add('auth-is-loading');
        dialog.focus();
    }

    function hide() {
        if (dialog && dialog.open) dialog.close();
        document.body.classList.remove('auth-is-loading');
        if (previousFocus && previousFocus.isConnected) previousFocus.focus();
        previousFocus = null;
    }

    window.PureViaAuthLoading = { show, hide };
    window.addEventListener('pageshow', hide);
})();
