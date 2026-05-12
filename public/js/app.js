class ImageGallery {
    constructor() {
        this.grid = document.getElementById('imageGrid');
        this.images = [];
        this.userVotes = new Map();
        this.init();
    }

    async init() {
        await this.loadPopularImages();
        this.setupEventListeners();
        this.setupSSE();
    }

    async loadPopularImages() {
        try {
            const data = await ApiClient.get('api/images.php?type=popular&limit=20');
            this.images = data.images;
            this.render();
        } catch (error) {
            this.grid.innerHTML = `<p class="error">Błąd ładowania zdjęć: ${error.message}</p>`;
        }
    }

    render() {
        if (this.images.length === 0) {
            this.grid.innerHTML = '<p class="no-images">Brak zdjęć do wyświetlenia.</p>';
            return;
        }

        this.grid.innerHTML = this.images.map((image, index) => {
            const card = this.createImageCard(image);
            return card.replace('class="image-card"', `class="image-card stagger-item" style="transition-delay: ${index * 0.05}s"`);
        }).join('');
        this.attachImageListeners();
        setTimeout(() => {
            document.querySelectorAll('.image-card').forEach(card => {
                card.classList.add('visible');
            });
        }, 100);
    }

    createImageCard(image) {
        const userVote = this.userVotes.get(image.id);
        const likeClass = userVote === 1 ? 'active' : '';
        const dislikeClass = userVote === 0 ? 'active' : '';

        return `
            <div class="image-card" data-image-id="${image.id}">
                <img src="${image.file_path}" alt="Zdjęcie od ${image.username}" loading="lazy">
                <div class="image-footer">
                    <div class="user-avatar" onclick="event.stopPropagation(); window.location.href='profile.php?id=${image.user_id}'">
                        ${image.avatar ? 
                            `<img src="${image.avatar}" alt="${image.username}">` : 
                            `<div class="avatar-placeholder">${image.username[0].toUpperCase()}</div>`
                        }
                        <span>${image.username}</span>
                    </div>
                    <div class="image-actions">
                        <button class="like-btn ${likeClass}" data-image-id="${image.id}" aria-label="Polub">
                            <span class="icon">👍</span>
                            <span class="count">${image.likes}</span>
                        </button>
                        <button class="dislike-btn ${dislikeClass}" data-image-id="${image.id}" aria-label="Nie lub">
                            <span class="icon">👎</span>
                            <span class="count">${image.dislikes}</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    attachImageListeners() {
        document.querySelectorAll('.image-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.like-btn') && !e.target.closest('.dislike-btn') && !e.target.closest('.user-avatar')) {
                    const imageId = card.dataset.imageId;
                    this.showImageModal(imageId);
                }
            });
        });

        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.handleVote(btn.dataset.imageId, 1);
            });
        });

        document.querySelectorAll('.dislike-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.handleVote(btn.dataset.imageId, 0);
            });
        });
    }

    async handleVote(imageId, isLike) {
        try {
            const result = await ApiClient.post('api/like.php', { image_id: imageId, is_like: isLike });
            if (result.action === 'added' || result.action === 'changed') {
                this.userVotes.set(imageId, isLike);
            } else if (result.action === 'removed') {
                this.userVotes.delete(imageId);
            }
            await this.loadPopularImages();
        } catch (error) {
            alert('Błąd podczas głosowania: ' + error.message);
        }
    }

    showImageModal(imageId) {
        const image = this.images.find(img => img.id == imageId);
        if (!image) return;

        const modal = document.getElementById('imageModal');
        const content = document.getElementById('imageModalContent');
        
        if (!modal || !content) return;
        
        content.innerHTML = `
            <img src="${image.file_path}" alt="Zdjęcie od ${image.username}" style="max-width: 100%; border-radius: 8px;">
            <div style="margin-top: 1rem;">
                <h3>Przez: ${image.username}</h3>
                <p>Liki: ${image.likes} | Nie lubią: ${image.dislikes}</p>
            </div>
        `;
        
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
    }

    setupEventListeners() {
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadModal = document.getElementById('uploadModal');
        const closeButtons = document.querySelectorAll('.close');

        if (uploadBtn) {
            uploadBtn.addEventListener('click', () => {
                uploadModal.classList.add('show');
                uploadModal.setAttribute('aria-hidden', 'false');
                this.trapFocus(uploadModal);
            });
        }

        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.closeModals();
            });
        });
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModals();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModals();
            }
        });
        const uploadForm = document.getElementById('uploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', (e) => this.handleUpload(e));
        }
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
        }
    }

    trapFocus(modal) {
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        modal.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        e.preventDefault();
                        lastFocusable.focus();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        e.preventDefault();
                        firstFocusable.focus();
                    }
                }
            }
        });
        if (firstFocusable) {
            firstFocusable.focus();
        }
    }

    closeModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        });
    }

    async handleUpload(e) {
        e.preventDefault();
        const form = e.target;
        const fileInput = document.getElementById('imageFile');
        const errorDiv = document.getElementById('uploadError');

        if (!fileInput.files[0]) {
            errorDiv.textContent = 'Wybierz plik';
            errorDiv.classList.add('show');
            return;
        }

        const formData = new FormData();
        formData.append('image', fileInput.files[0]);

        try {
            console.log('Uploading file...');
            const result = await ApiClient.upload('api/upload.php', formData);
            console.log('Upload result:', result);
            errorDiv.classList.remove('show');
            this.closeModals();
            form.reset();
            await this.loadPopularImages();
            alert('Zdjęcie przesłane pomyślnie!');
        } catch (error) {
            console.error('Upload error:', error);
            errorDiv.textContent = error.message;
            errorDiv.classList.add('show');
        }
    }

    async handleLogout() {
        try {
            await ApiClient.post('api/logout.php', {});
            window.location.href = 'login.php';
        } catch (error) {
            console.error('Logout error:', error);
            window.location.href = 'login.php';
        }
    }

    setupSSE() {
        return;
        
        try {
            const eventSource = new EventSource('api/events.php');
            
            eventSource.onmessage = (event) => {
                const data = JSON.parse(event.data);
                
                if (data.type === 'likes_update') {
                    this.loadPopularImages();
                } else if (data.type === 'new_images') {
                    this.loadPopularImages();
                }
            };

            eventSource.onerror = (error) => {
                console.error('SSE error:', error);
                eventSource.close();
            };
        } catch (error) {
            console.error('SSE not supported:', error);
        }
    }
}
class ScrollAnimations {
    constructor() {
        this.init();
    }

    init() {
        this.progressBar = document.createElement('div');
        this.progressBar.className = 'scroll-progress';
        this.progressBar.style.width = '0%';
        document.body.appendChild(this.progressBar);

        this.setupEventListeners();
        this.checkScroll();
    }

    setupEventListeners() {
        window.addEventListener('scroll', () => this.checkScroll());
        window.addEventListener('resize', () => this.checkScroll());
    }

    checkScroll() {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrollPercent = (scrollTop / docHeight) * 100;
        this.progressBar.style.width = scrollPercent + '%';
        this.revealElements();
    }

    revealElements() {
        const reveals = document.querySelectorAll('.scroll-reveal, .scroll-reveal-left, .scroll-reveal-right, .scroll-reveal-scale');
        const windowHeight = window.innerHeight;

        reveals.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const revealPoint = 150;

            if (elementTop < windowHeight - revealPoint) {
                element.classList.add('visible');
            }
        });
        const staggerItems = document.querySelectorAll('.stagger-item');
        staggerItems.forEach((item, index) => {
            const elementTop = item.getBoundingClientRect().top;
            if (elementTop < windowHeight - 100) {
                setTimeout(() => {
                    item.classList.add('visible');
                }, index * 100);
            }
        });
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new ScrollAnimations();

    if (document.getElementById('imageGrid')) {
        new ImageGallery();
    }
    const navUserBtn = document.getElementById('navUserBtn');
    const navDropdown = document.getElementById('navDropdown');

    if (navUserBtn && navDropdown) {
        new Dropdown('navUserBtn', 'navDropdown');
    }
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';

            this.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
});
