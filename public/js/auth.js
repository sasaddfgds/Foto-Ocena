class AuthHandler {
    constructor() {
        this.init();
    }

    init() {
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        const form = e.target;
        const errorDiv = document.getElementById('loginError');
        const username = form.username.value.trim();
        const password = form.password.value;

        try {
            const result = await ApiClient.post('api/login.php', {
                username: username,
                password: password
            });

            errorDiv.classList.remove('show');
            window.location.href = 'index.php';
        } catch (error) {
            errorDiv.textContent = error.message;
            errorDiv.classList.add('show');
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        const form = e.target;
        const errorDiv = document.getElementById('registerError');
        const username = form.username.value.trim();
        const password = form.password.value;

        if (password.length < 6) {
            errorDiv.textContent = 'Hasło musi mieć minimum 6 znaków';
            errorDiv.classList.add('show');
            return;
        }

        try {
            const result = await ApiClient.post('api/register.php', {
                username: username,
                password: password
            });

            errorDiv.classList.remove('show');
            alert('Rejestracja zakończona pomyślnie! Zostałeś automatycznie zalogowany.');
            window.location.href = 'index.php';
        } catch (error) {
            errorDiv.textContent = error.message;
            errorDiv.classList.add('show');
        }
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new AuthHandler();
});
