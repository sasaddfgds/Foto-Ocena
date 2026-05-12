class FormUtils {
    static showError(formId, message) {
        const errorDiv = document.getElementById(formId + 'Error');
        const successDiv = document.getElementById(formId + 'Success');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.add('show');
        }
        if (successDiv) {
            successDiv.classList.remove('show');
        }
    }

    static showSuccess(formId, message) {
        const errorDiv = document.getElementById(formId + 'Error');
        const successDiv = document.getElementById(formId + 'Success');
        if (successDiv) {
            successDiv.textContent = message;
            successDiv.classList.add('show');
        }
        if (errorDiv) {
            errorDiv.classList.remove('show');
        }
    }

    static clearMessages(formId) {
        const errorDiv = document.getElementById(formId + 'Error');
        const successDiv = document.getElementById(formId + 'Success');
        if (errorDiv) errorDiv.classList.remove('show');
        if (successDiv) successDiv.classList.remove('show');
    }
}
