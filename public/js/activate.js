document.getElementById('activation-form').addEventListener('submit', async function (event) {
    event.preventDefault();

    const applicationKey = document.getElementById('application-key').value;
    const message = document.getElementById('activation-message');

    message.textContent = '';

    try {
        const response = await fetch('/application/activate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                application_key: applicationKey
            })
        });

        console.log('Status:', response.status);
        console.log('Content-Type:', response.headers.get('content-type'));

        const data = await response.json();

        console.log('Backend response:', data);

        if (data.success) {
            window.location.href = '/login';
            return;
        }

        message.textContent = data.message;

    } catch (error) {
        console.error('Fetch/JS error:', error);
        message.textContent = 'Unable to connect to the server.';
    }
});