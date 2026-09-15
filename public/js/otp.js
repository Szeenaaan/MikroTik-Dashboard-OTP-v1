const boxes = Array.from(
    document.querySelectorAll('.otp-box')
);

const realInput = document.getElementById('otp-real');
const verifyForm = document.getElementById('verify-form');
const submitBtn = document.getElementById('submit-btn');
const resendForm = document.getElementById('resend-form');
const resendBtn = document.getElementById('resend-btn');


function syncRealInput() {
    const value = boxes
        .map(box => box.value)
        .join('');

    realInput.value = value;

    submitBtn.disabled = value.length !== boxes.length;
}


function showMessage(message, type = 'error') {
    let alert = document.querySelector('.js-alert');

    if (!alert) {
        alert = document.createElement('div');
        alert.className = 'alert js-alert';

        const card = document.querySelector('.card');

        card.insertBefore(alert, card.children[4]);
    }

    alert.classList.remove(
        'alert-error',
        'alert-success'
    );

    alert.classList.add(
        type === 'success'
            ? 'alert-success'
            : 'alert-error'
    );

    alert.textContent = message;
}


async function submitForm(form) {
    const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        redirect: 'follow'
    });

    const contentType =
        response.headers.get('content-type') || '';

    return {
        response,
        contentType
    };
}


boxes.forEach((box, idx) => {

    box.addEventListener('input', () => {

        box.value = box.value
            .replace(/\D/g, '')
            .slice(0, 1);

        if (
            box.value &&
            idx < boxes.length - 1
        ) {
            boxes[idx + 1].focus();
        }

        syncRealInput();
    });


    box.addEventListener('keydown', (event) => {

        if (
            event.key === 'Backspace' &&
            !box.value &&
            idx > 0
        ) {
            boxes[idx - 1].focus();
        }

    });


    box.addEventListener('paste', (event) => {

        event.preventDefault();

        const pasted = (
            event.clipboardData.getData('text') || ''
        ).replace(/\D/g, '');

        pasted
            .split('')
            .slice(0, boxes.length)
            .forEach((char, index) => {

                if (boxes[index]) {
                    boxes[index].value = char;
                }

            });

        const next = Math.min(
            pasted.length,
            boxes.length - 1
        );

        boxes[next].focus();

        syncRealInput();
    });

});


verifyForm.addEventListener('submit', async (event) => {

    event.preventDefault();

    submitBtn.disabled = true;

    try {
        const {
            response,
            contentType
        } = await submitForm(verifyForm);

        if (!contentType.includes('application/json')) {
            const html = await response.text();

            document.open();
            document.write(html);
            document.close();

            return;
        }

        const result = await response.json();

        if (result.success) {
            window.location.href = '/hotspot/success';
            return;
        }

        showMessage(result.message);

        submitBtn.disabled = false;

    } catch (error) {

        showMessage(
            'Unable to process your request. Please try again.'
        );

        submitBtn.disabled = false;
    }

});


resendForm.addEventListener('submit', async (event) => {

    event.preventDefault();

    resendBtn.disabled = true;

    try {
        const {
            response,
            contentType
        } = await submitForm(resendForm);

        if (!contentType.includes('application/json')) {
            const html = await response.text();

            document.open();
            document.write(html);
            document.close();

            return;
        }

        const result = await response.json();

        showMessage(
            result.message,
            result.success ? 'success' : 'error'
        );

        if (!result.success) {
            resendBtn.disabled = false;
        }

    } catch (error) {

        showMessage(
            'Unable to process your request. Please try again.'
        );

        resendBtn.disabled = false;
    }

});