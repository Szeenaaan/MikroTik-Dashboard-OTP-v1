function showToast(result) {
    const container = document.getElementById(
        'response-toast-container'
    );

    if (!container) {
        return;
    }

    const toast = document.createElement('div');

    toast.classList.add(
        'response-toast',
        result.success ? 'success' : 'error'
    );

    toast.textContent = result.message;

    container.appendChild(toast);

    setTimeout(function () {

        toast.classList.add('hide');

        setTimeout(function () {
            toast.remove();
        }, 250);

    }, 4750);
}