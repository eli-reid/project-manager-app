{
    openMailboxWindow(payload) {
        if (!payload || typeof payload !== 'object') {
            return;
        }

        if (payload.mode === 'post_handshake' && payload.login_url && payload.session) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = payload.login_url;
            form.target = '_blank';

            const sessionInput = document.createElement('input');
            sessionInput.type = 'hidden';
            sessionInput.name = 'session';
            sessionInput.value = payload.session;

            form.appendChild(sessionInput);
            document.body.appendChild(form);
            form.submit();
            form.remove();

            return;
        }

        if (payload.url) {
            window.open(payload.url, '_blank', 'noopener,noreferrer');
        }
    }
}
