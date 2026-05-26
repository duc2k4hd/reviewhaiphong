
        // ===== JAVASCRIPT =====

        // --- Mobile Menu Toggle ---
        (function () {
            var reviewhaiphong_home_toggle = document.getElementById('reviewhaiphong_home_menu_toggle');
            var reviewhaiphong_home_nav = document.getElementById('reviewhaiphong_home_mobile_nav');
            var reviewhaiphong_home_close = document.getElementById('reviewhaiphong_home_mobile_close');

            if (reviewhaiphong_home_toggle && reviewhaiphong_home_nav) {
                reviewhaiphong_home_toggle.addEventListener('click', function () {
                    reviewhaiphong_home_nav.classList.add('reviewhaiphong_home_open');
                    document.body.style.overflow = 'hidden';
                });
            }
            if (reviewhaiphong_home_close && reviewhaiphong_home_nav) {
                reviewhaiphong_home_close.addEventListener('click', function () {
                    reviewhaiphong_home_nav.classList.remove('reviewhaiphong_home_open');
                    document.body.style.overflow = '';
                });
            }
            if (reviewhaiphong_home_nav) {
                reviewhaiphong_home_nav.addEventListener('click', function (e) {
                    if (e.target === reviewhaiphong_home_nav) {
                        reviewhaiphong_home_nav.classList.remove('reviewhaiphong_home_open');
                        document.body.style.overflow = '';
                    }
                });
            }
        })();

        // --- Tab System ---
        (function () {
            var reviewhaiphong_home_tabContainers = document.querySelectorAll('[id$="_tabs"]');
            reviewhaiphong_home_tabContainers.forEach(function (reviewhaiphong_home_tabContainer) {
                var reviewhaiphong_home_btns = reviewhaiphong_home_tabContainer.querySelectorAll('.reviewhaiphong_home_tab_btn');
                reviewhaiphong_home_btns.forEach(function (reviewhaiphong_home_btn) {
                    reviewhaiphong_home_btn.addEventListener('click', function () {
                        var reviewhaiphong_home_targetId = this.getAttribute('data-tab');
                        // Deactivate all in this container
                        reviewhaiphong_home_btns.forEach(function (b) { b.classList.remove('reviewhaiphong_home_tab_active'); });
                        // Hide all tab contents (find parent section)
                        var reviewhaiphong_home_parentSection = reviewhaiphong_home_tabContainer.closest('.reviewhaiphong_home_section_block');
                        if (reviewhaiphong_home_parentSection) {
                            reviewhaiphong_home_parentSection.querySelectorAll('.reviewhaiphong_home_tab_content').forEach(function (tc) {
                                tc.classList.remove('reviewhaiphong_home_tab_show');
                            });
                            var reviewhaiphong_home_target = document.getElementById(reviewhaiphong_home_targetId);
                            if (reviewhaiphong_home_target) reviewhaiphong_home_target.classList.add('reviewhaiphong_home_tab_show');
                        }
                        this.classList.add('reviewhaiphong_home_tab_active');
                    });
                });
            });
        })();

        // --- Load More ---
        (function () {
            var reviewhaiphong_home_loadMoreBtns = document.querySelectorAll('.reviewhaiphong_home_loadmore_btn');
            reviewhaiphong_home_loadMoreBtns.forEach(function (reviewhaiphong_home_btn) {
                reviewhaiphong_home_btn.addEventListener('click', function () {
                    var reviewhaiphong_home_sectionId = this.getAttribute('data-section');
                    var reviewhaiphong_home_section = document.getElementById(reviewhaiphong_home_sectionId);
                    if (reviewhaiphong_home_section) {
                        if (reviewhaiphong_home_section.style.display === 'none' || reviewhaiphong_home_section.style.display === '') {
                            reviewhaiphong_home_section.style.display = 'block';
                            this.textContent = 'Thu gọn ▲';
                        } else {
                            reviewhaiphong_home_section.style.display = 'none';
                            this.textContent = 'Xem thêm ▼';
                        }
                    }
                });
            });
        })();

        // --- Search Enter Key ---
        (function () {
            var reviewhaiphong_home_searchInput = document.querySelector('.reviewhaiphong_home_search_input');
            var reviewhaiphong_home_searchBtn = document.querySelector('.reviewhaiphong_home_search_btn');
            if (reviewhaiphong_home_searchInput) {
                reviewhaiphong_home_searchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        if (reviewhaiphong_home_searchBtn) reviewhaiphong_home_searchBtn.click();
                    }
                });
            }
            if (reviewhaiphong_home_searchBtn) {
                reviewhaiphong_home_searchBtn.addEventListener('click', function () {
                    var reviewhaiphong_home_q = reviewhaiphong_home_searchInput ? reviewhaiphong_home_searchInput.value.trim() : '';
                    if (reviewhaiphong_home_q) {
                        alert('Tìm kiếm: ' + reviewhaiphong_home_q);
                    }
                });
            }
        })();

        // --- Sticky header shadow on scroll ---
        (function () {
            var reviewhaiphong_home_hdr = document.querySelector('.reviewhaiphong_home_header');
            if (!reviewhaiphong_home_hdr) return;
            window.addEventListener('scroll', function () {
                if (window.scrollY > 10) {
                    reviewhaiphong_home_hdr.style.boxShadow = '0 2px 12px rgba(0,0,0,0.16)';
                } else {
                    reviewhaiphong_home_hdr.style.boxShadow = '0 1px 4px rgba(0,0,0,0.10)';
                }
            });
        })();
    
// --- Scroll Animation Observer ---
(function () {
    document.addEventListener("DOMContentLoaded", function() {
        var animatedElements = document.querySelectorAll('.anim-fade-up, .anim-slide-left, .anim-slide-right, .anim-zoom');
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                        observer.unobserve(entry.target);
                    }
                });
            }, { rootMargin: "0px 0px -50px 0px" });

            animatedElements.forEach(function(el) {
                observer.observe(el);
            });
        } else {
            animatedElements.forEach(function(el) {
                el.classList.add('in-view');
            });
        }
    });
})();
