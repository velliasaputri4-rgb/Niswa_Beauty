/* ================================================
   LUMIÈRE SALON — Main JavaScript
   ================================================ */

// ---- Loading Screen ----
// Hanya tampil saat refresh (F5/Ctrl+R), tidak saat navigasi antar halaman
(function () {
    const loader = document.getElementById('loadingScreen');
    if (!loader) return;

    const navType = performance.getEntriesByType('navigation')[0]?.type;
    const isRefresh = navType === 'reload';
    const isNavigating = sessionStorage.getItem('navigating') === '1';

    // Hapus flag navigasi setelah dibaca
    sessionStorage.removeItem('navigating');

    if (isRefresh && !isNavigating) {
        // Refresh → tampilkan loading
        window.addEventListener('load', () => {
            setTimeout(() => loader.classList.add('hide'), 1800);
        });
    } else {
        // Navigasi antar halaman → langsung sembunyikan
        loader.style.display = 'none';
    }

    // Tandai setiap klik link internal sebagai navigasi (bukan refresh)
    document.addEventListener('click', function (e) {
        const a = e.target.closest('a[href]');
        if (!a) return;
        const href = a.getAttribute('href');
        if (href && !href.startsWith('#') && !href.startsWith('http') && a.target !== '_blank') {
            sessionStorage.setItem('navigating', '1');
        }
    });
})();


// ---- AOS Init ----
AOS.init({
    duration: 700,
    easing: 'ease-out-cubic',
    once: true,
    offset: 60,
});

// ---- Navbar Scroll ----
const navbar = document.getElementById('mainNav');
window.addEventListener('scroll', () => {
    if (window.scrollY > 60) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// ---- Back to Top ----
const btt = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
        btt.classList.add('show');
    } else {
        btt.classList.remove('show');
    }
});

if (btt) {
    btt.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// ---- Counter Animation ----
function animateCounter(el) {
    const target = parseInt(el.dataset.target);
    const suffix = el.dataset.suffix || '';
    const duration = 1800;
    const step = target / (duration / 16);
    let current = 0;

    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        el.textContent = Math.floor(current).toLocaleString() + suffix;
    }, 16);
}

// Trigger counters when visible
const counters = document.querySelectorAll('[data-target]');
if (counters.length) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                entry.target.classList.add('counted');
                animateCounter(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(c => observer.observe(c));
}

// ---- Gallery Filter ----
const filterBtns = document.querySelectorAll('.filter-btn');
const galleryItems = document.querySelectorAll('.gallery-item-card');

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const filter = btn.dataset.filter;

        galleryItems.forEach(item => {
            if (filter === 'all' || item.dataset.category === filter) {
                item.style.display = 'block';
                item.style.animation = 'fadeIn 0.4s ease';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

// ---- Booking Form: Date min today ----
const dateInput = document.getElementById('bookingDate');
if (dateInput) {
    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);
}

// ---- Form Submission Animation ----
const bookingForm = document.getElementById('bookingForm');
if (bookingForm) {
    bookingForm.addEventListener('submit', function (e) {
        const btn = this.querySelector('.btn-submit');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Memproses...';
        btn.disabled = true;
    });
}

// ---- Dashboard: Live Search ----
const searchInput = document.querySelector('.search-input');
if (searchInput) {
    searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        const rows = document.querySelectorAll('.custom-table tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    });
}

// ---- Dashboard: Delete Confirm ----
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function (e) {
        if (!confirm('Hapus data booking ini?')) {
            e.preventDefault();
        }
    });
});

// ---- Smooth Hover on Cards ----
document.querySelectorAll('.service-card, .why-card, .testimonial-card').forEach(card => {
    card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width - 0.5) * 8;
        const y = ((e.clientY - rect.top) / rect.height - 0.5) * 8;
        card.style.transform = `translateY(-8px) rotateX(${-y}deg) rotateY(${x}deg)`;
    });
    card.addEventListener('mouseleave', () => {
        card.style.transform = '';
    });
});

// ---- Floating label effect ----
document.querySelectorAll('.form-control, .form-select').forEach(input => {
    input.addEventListener('focus', function () {
        this.parentElement.classList.add('focused');
    });
    input.addEventListener('blur', function () {
        this.parentElement.classList.remove('focused');
    });
});

// ---- Nav Active on Scroll (for index.php sections) ----
const sections = document.querySelectorAll('section[id]');
const navLinks = document.querySelectorAll('.nav-link[href^="#"]');

window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(s => {
        const top = s.offsetTop - 100;
        if (window.scrollY >= top) current = s.getAttribute('id');
    });
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current) link.classList.add('active');
    });
});

// ---- Cursor sparkle effect (subtle) ----
document.addEventListener('mousemove', (e) => {
    if (Math.random() > 0.96) {
        const dot = document.createElement('div');
        dot.style.cssText = `
            position: fixed;
            left: ${e.clientX}px;
            top: ${e.clientY}px;
            width: 6px; height: 6px;
            background: var(--pink);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9998;
            opacity: 0.7;
            transform: translate(-50%, -50%);
            animation: sparkle 0.8s ease forwards;
        `;
        document.body.appendChild(dot);
        setTimeout(() => dot.remove(), 800);
    }
});

// Add sparkle animation
const sparkleStyle = document.createElement('style');
sparkleStyle.textContent = `
    @keyframes sparkle {
        0% { transform: translate(-50%, -50%) scale(1); opacity: 0.7; }
        100% { transform: translate(-50%, -120%) scale(0); opacity: 0; }
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;

// ============================================
// PREMIUM GALLERY ENHANCEMENTS
// ============================================

// Gallery Lightbox (Fancybox)
Fancybox.bind("[data-fancybox]", {
    Toolbar: {
        display: {
            left: ["infobar"],
            middle: [
                "zoomIn",
                "zoomOut",
                "toggle1to1",
                "rotateCCW",
                "rotateCW",
                "flipX",
                "flipY",
            ],
            right: ["slideshow", "thumbs", "close"],
        },
    },
    Images: {
        zoom: true,
    },
    Thumbs: {
        autoStart: false,
    },
});

// Enhanced Masonry & Filter
let allGalleryItems = [];
let visibleItems = 12;

function initGallery() {
    allGalleryItems = document.querySelectorAll('.gallery-item');
    
    // Animate in visible items
    allGalleryItems.forEach((item, index) => {
        if (!item.classList.contains('d-none') && index < visibleItems) {
            setTimeout(() => {
                item.classList.add('animate-in');
            }, index * 80);
        }
    });
    
    masonryLayout();
}

function masonryLayout() {
    const masonry = document.getElementById('galleryMasonry');
    if (masonry) {
        // Trigger reflow for CSS grid
        masonry.style.height = masonry.scrollHeight + 'px';
        setTimeout(() => {
            masonry.style.height = '';
        }, 100);
    }
}

// Enhanced Filter with Animation
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filterValue = this.dataset.filter;
            
            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Filter items
            galleryItems.forEach((item, index) => {
                if (filterValue === '*' || item.dataset.category === filterValue) {
                    item.style.display = 'block';
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(30px)';
                    
                    setTimeout(() => {
                        item.classList.add('animate-in');
                    }, index * 60);
                } else {
                    item.classList.remove('animate-in');
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(0)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
            
            masonryLayout();
        });
    });
});

// Load More Functionality
document.addEventListener('click', function(e) {
    if (e.target.closest('.load-more-btn')) {
        const btn = e.target.closest('.load-more-btn');
        const hiddenItems = document.querySelectorAll('.gallery-item.d-none');
        
        if (hiddenItems.length > 0) {
            let loaded = 0;
            hiddenItems.forEach((item, index) => {
                if (loaded < 12) {
                    setTimeout(() => {
                        item.classList.remove('d-none');
                        item.classList.add('animate-in');
                        loaded++;
                    }, index * 100);
                }
            });
            
            // Update button
            const countSpan = btn.querySelector('.loaded-count');
            if (countSpan) {
                const currentShown = parseInt(countSpan.textContent.match(/\\d+/)?.[0] || 12);
                countSpan.textContent = `+${currentShown + loaded} shown`;
            }
            
            masonryLayout();
        }
        
        if (document.querySelectorAll('.gallery-item.d-none').length === 0) {
            btn.style.opacity = '0.5';
            btn.disabled = true;
        }
    }
});

// Gallery Intersection Observer for staggered animation
const galleryObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.gallery-item').forEach(item => {
    galleryObserver.observe(item);
});

// Init gallery when DOM ready
if (document.getElementById('galleryMasonry')) {
    initGallery();
}

// ============================================
 // SERVICES FILTER (Optional Enhancement)
 // ============================================

document.addEventListener('DOMContentLoaded', function() {
    const serviceFilterBtns = document.querySelectorAll('.services-filter-buttons .filter-btn');
    const serviceItems = document.querySelectorAll('[data-category]');
    
    serviceFilterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filterValue = this.dataset.filter;
            
            // Update active
            serviceFilterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Filter services
            serviceItems.forEach(item => {
                if (filterValue === '*' || item.dataset.category === filterValue) {
                    item.style.display = 'block';
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(30px)';
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0)';
                    }, 100);
                } else {
                    item.style.opacity = '0';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
        });
    });
});

// ============================================
 // PREMIUM BOOKING ENHANCEMENTS
 // ============================================

// Flatpickr Datepicker
if (document.getElementById('bookingDate')) {
    flatpickr('#bookingDate', {
        dateFormat: "Y-m-d",
        minDate: "today",
        altInput: true,
        altFormat: "d F Y",
        locale: {
            firstDayOfWeek: 1
        }
    });
}

// Premium Form Validation + Loading
const premiumBookingForm = document.getElementById('premiumBookingForm');
if (premiumBookingForm) {
    premiumBookingForm.addEventListener('submit', function(e) {
        const btn = this.querySelector('.btn-submit-premium');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Konfirmasi...';
        btn.disabled = true;
    });
}

// Success Modal Auto-show (if PHP success)
if (document.querySelector('.success-trigger')) {
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    const trigger = document.querySelector('.success-trigger');
    const message = trigger.textContent;
    document.getElementById('modalMessage').textContent = message;
    successModal.show();
}

document.head.appendChild(sparkleStyle);
function scrollGallery(button, direction) {
    const slider = button.closest('.gallery-category').querySelector('.gallery-slider');
    slider.scrollBy({
        left: direction * 320,
        behavior: 'smooth'
    });
}