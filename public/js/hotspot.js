const form = document.getElementById('otp-form');

if (form) {
    const btn = document.getElementById('submit-btn');
    const phone = document.getElementById('phone');

    phone.addEventListener('input', () => {
        phone.value = phone.value
            .replace(/\D/g, '')
            .slice(0, 10);
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        btn.disabled = true;
        btn.textContent = 'Sending...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const contentType = response.headers.get('content-type') || '';

            if (contentType.includes('application/json')) {
                const result = await response.json();

                if (result.success) {
                    window.location.href = '/hotspot/otp';
                    return;
                }

                showError(result.message);

                btn.disabled = false;
                btn.textContent = 'Send OTP';

                return;
            }

            const html = await response.text();

            document.open();
            document.write(html);
            document.close();

        } catch (error) {
            showError('Unable to process your request. Please try again.');

            btn.disabled = false;
            btn.textContent = 'Send OTP';
        }
    });
}

function showError(message) {
    let alert = document.querySelector('.js-alert-error');

    if (!alert) {
        alert = document.createElement('div');
        alert.className = 'alert alert-error js-alert-error';

        const form = document.getElementById('otp-form');

        form.parentNode.insertBefore(alert, form);
    }

    alert.textContent = message;
}