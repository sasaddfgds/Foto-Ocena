class SettingsHandler {
    constructor() {
        this.init();
    }

    init() {
        this.initDropdown();
        this.initProfileForm();
        this.initAvatarForm();
        this.initPasswordForm();
        this.initPrivacyForm();
        this.initDeleteAccountForm();
        this.initLogout();
    }

    initDropdown() {
        const navUserBtn = document.getElementById('navUserBtn');
        const navDropdown = document.getElementById('navDropdown');

        if (navUserBtn && navDropdown) {
            new Dropdown('navUserBtn', 'navDropdown');
        }
    }

    initAvatarForm() {
        const form = document.getElementById('avatarForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            FormUtils.clearMessages('avatar');

            const fileInput = document.getElementById('avatarFile');
            if (!fileInput.files[0]) {
                FormUtils.showError('avatar', 'Wybierz plik');
                return;
            }

            const formData = new FormData();
            formData.append('avatar', fileInput.files[0]);

            try {
                const result = await ApiClient.postFormData('api/upload-avatar.php', formData);
                FormUtils.showSuccess('avatar', result.message);
                const currentAvatar = document.querySelector('.current-avatar img, .current-avatar .avatar-placeholder');
                if (currentAvatar) {
                    currentAvatar.outerHTML = `<img src="${result.avatar}" alt="Avatar">`;
                }

                // Update nav avatar
                const navAvatar = document.querySelector('.nav-avatar');
                if (navAvatar) {
                    navAvatar.src = result.avatar;
                }

                fileInput.value = '';
            } catch (error) {
                FormUtils.showError('avatar', error.message);
            }
        });
    }

    initProfileForm() {
        const form = document.getElementById('profileForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            FormUtils.clearMessages('profile');

            const username = form.username.value.trim();
            const bio = form.bio.value.trim();

            try {
                const result = await ApiClient.post('api/settings.php', {
                    action: 'update_profile',
                    username: username,
                    bio: bio
                });


                FormUtils.showSuccess('profile', result.message);
            } catch (error) {
                FormUtils.showError('profile', error.message);
            }
        });
    }
    initPasswordForm() {
        const form = document.getElementById('passwordForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            FormUtils.clearMessages('password');

            const currentPassword = form.current_password.value;
            const newPassword = form.new_password.value;
            const confirmPassword = form.confirm_password.value;

            if (newPassword !== confirmPassword) {
                FormUtils.showError('password', 'Hasła nie są identyczne');
                return;
            }

            if (newPassword.length < 6) {
                FormUtils.showError('password', 'Hasło musi mieć minimum 6 znaków');
                return;
            }

            try {
                const result = await ApiClient.post('api/settings.php', {
                    action: 'change_password',
                    current_password: currentPassword,
                    new_password: newPassword
                });

                FormUtils.showSuccess('password', result.message);
                form.reset();
            } catch (error) {
                FormUtils.showError('password', error.message);
            }
        });
    }
    initPrivacyForm() {
        const form = document.getElementById('privacyForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            FormUtils.clearMessages('privacy');

            const isPrivate = document.getElementById('isPrivate').checked;

            try {
                const result = await ApiClient.post('api/settings.php', {
                    action: 'update_privacy',
                    is_private: isPrivate
                });

                FormUtils.showSuccess('privacy', result.message);
            } catch (error) {
                FormUtils.showError('privacy', error.message);
            }
        });
    }
    initDeleteAccountForm() {
        const form = document.getElementById('deleteAccountForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            FormUtils.clearMessages('delete');

            const password = form.password.value;

            if (!confirm('Czy na pewno chcesz usunąć konto? Ta akcja jest nieodwracalna.')) {
                return;
            }

            try {
                const result = await ApiClient.post('api/settings.php', {
                    action: 'delete_account',
                    password: password
                });

                alert('Konto usunięte. Przekierowanie...');
                window.location.href = 'index.php';
            } catch (error) {
                FormUtils.showError('delete', error.message);
            }
        });
    }
    initLogout() {
        const logoutBtn = document.getElementById('logoutBtn');
        if (!logoutBtn) return;

        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            try {
                await ApiClient.post('api/logout.php', {});
                window.location.href = 'index.php';
            } catch (error) {
                alert('Błąd podczas wylogowania');
            }
        });
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new SettingsHandler();
});
