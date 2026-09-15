document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const signupForm = document.getElementById('signup-form');

    const formTitle = document.getElementById('form-title');
    const formSubtitle = document.getElementById('form-subtitle');

    const switchLabel = document.getElementById('switch-label');
    const switchButton = document.getElementById('switch-button');

    const responseMessage = document.getElementById('response-message');

    const loginButton = document.getElementById('login-button');
    const signupButton = document.getElementById('signup-button');

    switchButton.addEventListener('click', () => {
        responseMessage.textContent = '';

        if (!loginForm.hidden) {
            loginForm.hidden = true;
            signupForm.hidden = false;

            formTitle.textContent = 'Sign Up';
            formSubtitle.textContent = 'Create your account';

            switchLabel.textContent = 'Already have an account?';
            switchButton.textContent = 'Login';
        } else {
            signupForm.hidden = true;
            loginForm.hidden = false;

            formTitle.textContent = 'Login';
            formSubtitle.textContent = 'Sign in to continue';

            switchLabel.textContent = "Don't have an account?";
            switchButton.textContent = 'Sign Up';
        }
    });

    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        responseMessage.textContent = '';
        loginButton.disabled = true;

        try {
            const response = await fetch('/login', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: new FormData(loginForm)
            });

            const data = await response.json();

            responseMessage.textContent = data.message || '';

            if (response.ok && data.success) {
                window.location.href = '/dashboard';
            }
        } catch (error) {
            responseMessage.textContent = 'Unable to connect to the server. Please try again.';
        } finally {
            loginButton.disabled = false;
        }
    });

    signupForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        responseMessage.textContent = '';
        signupButton.disabled = true;

        try {
            const response = await fetch('/signup', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: new FormData(signupForm)
            });

            const data = await response.json();

            responseMessage.textContent = data.message || '';

            if (response.ok && data.success) {
                signupForm.reset();

                signupForm.hidden = true;
                loginForm.hidden = false;

                formTitle.textContent = 'Login';
                formSubtitle.textContent = 'Sign in to continue';

                switchLabel.textContent = "Don't have an account?";
                switchButton.textContent = 'Sign Up';
            }
        } catch (error) {
            responseMessage.textContent = 'Unable to connect to the server. Please try again.';
        } finally {
            signupButton.disabled = false;
        }
    });
});