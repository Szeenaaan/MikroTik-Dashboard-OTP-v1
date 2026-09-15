document.addEventListener('DOMContentLoaded', function () {

    const forms = document.querySelectorAll('.dashboard-action-form');

    forms.forEach(function (form) {

        form.addEventListener('submit', async function (event) {

            event.preventDefault();

            const button = form.querySelector('button');

            button.disabled = true;

            try {

                const response = await fetch(form.action, {
                    method: 'POST',

                    headers: {
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content'),

                        'Accept': 'application/json'
                    },

                    credentials: 'same-origin'
                });

                const result = await response.json();

                showToast(result);

                if (result.success === true) {

                    setTimeout(function () {
                        window.location.reload();
                    }, 500);

                } else {

                    button.disabled = false;
                }

            } catch (error) {

                showToast({
                    success: false,
                    statuscode: 503,
                    message: 'Server unavailable.'
                });

                button.disabled = false;
            }

        });

    });

});