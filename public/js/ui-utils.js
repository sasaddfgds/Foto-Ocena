class Dropdown {
    constructor(buttonId, dropdownId) {
        this.button = document.getElementById(buttonId);
        this.dropdown = document.getElementById(dropdownId);
        if (this.button && this.dropdown) {
            this.init();
        }
    }

    init() {
        this.button.addEventListener('click', (e) => {
            e.stopPropagation();
            const isExpanded = this.button.getAttribute('aria-expanded') === 'true';
            this.button.setAttribute('aria-expanded', !isExpanded);
            this.dropdown.classList.toggle('show');
        });

        document.addEventListener('click', () => {
            this.button.setAttribute('aria-expanded', 'false');
            this.dropdown.classList.remove('show');
        });

        this.dropdown.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.button.setAttribute('aria-expanded', 'false');
                this.dropdown.classList.remove('show');
            }
        });
    }
}
