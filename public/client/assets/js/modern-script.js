// Clean Professional Script for Review Hai Phong

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('open');
            mobileMenuToggle.classList.toggle('active');
        });
    }

    // Back to top button
    const backToTopBtn = document.getElementById('back-to-top');
    
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });

        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Search form submission
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const keyword = document.getElementById('keyword').value.trim();
            if (keyword) {
                window.location.href = `/tim-kiem?q=${encodeURIComponent(keyword)}`;
            }
        });
    }

    // Comment like functionality
    window.likeComment = function(commentId) {
        console.log('Like comment:', commentId);
    };

    // Comment reply functionality
    window.replyComment = function(commentId) {
        console.log('Reply to comment:', commentId);
    };

    // Lazy loading for images
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));

    // Add loading state on form submit (avoid blocking default submit)
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                if (!submitBtn.dataset.originalText) {
                    submitBtn.dataset.originalText = submitBtn.innerHTML;
                }
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                // Re-enable after navigation fallback (in case of client-side validation error)
                setTimeout(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = submitBtn.dataset.originalText || 'Gửi';
                    }
                }, 5000);
            }
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('keyword');
            if (searchInput) searchInput.focus();
        }
        if (e.key === 'Escape') {
            if (navMenu && navMenu.classList.contains('open')) {
                navMenu.classList.remove('open');
                mobileMenuToggle.classList.remove('active');
            }
        }
    });

    // Hover micro-interactions
    const interactiveElements = document.querySelectorAll('.nav-link, .dropdown-item, .tag-item, .share-btn, .action-btn');
    interactiveElements.forEach(element => {
        element.addEventListener('mouseenter', function() { this.style.transform = 'translateY(-1px)'; });
        element.addEventListener('mouseleave', function() { this.style.transform = 'translateY(0)'; });
    });

    // Reading progress bar
    const article = document.querySelector('.modern-article');
    if (article) {
        const progressBar = document.createElement('div');
        progressBar.className = 'reading-progress';
        progressBar.style.cssText = `position: fixed; top: 0; left: 0; width: 0%; height: 3px; background: var(--primary); z-index: 1000; transition: width 0.1s ease;`;
        document.body.appendChild(progressBar);
        window.addEventListener('scroll', function() {
            const articleTop = article.offsetTop;
            const articleHeight = article.offsetHeight;
            const scrollTop = window.pageYOffset;
            const windowHeight = window.innerHeight;
            if (scrollTop >= articleTop) {
                const progress = Math.min(((scrollTop - articleTop) / (articleHeight - windowHeight)) * 100, 100);
                progressBar.style.width = progress + '%';
            } else {
                progressBar.style.width = '0%';
            }
        });
    }

    // Debounced scroll (placeholder)
    function debounce(func, wait) { let timeout; return function(...args) { clearTimeout(timeout); timeout = setTimeout(() => func(...args), wait); }; }
    const debouncedScrollHandler = debounce(function() {}, 100);
    window.addEventListener('scroll', debouncedScrollHandler);
});

// Global
function redirectToSearch() {
    const keyword = document.getElementById('keyword').value.trim();
    if (keyword) {
        window.location.href = `/tim-kiem?q=${encodeURIComponent(keyword)}`;
        return false;
    }
    return false;
}
