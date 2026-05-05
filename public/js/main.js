// Main JavaScript for DIX Game Farm

// Global CSRF token handling for AJAX
$.ajaxSetup({
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
});

// Read cookie by name
function getCookieValue(name) {
    const cookieString = document.cookie || '';
    const cookies = cookieString.split(';');

    for (let i = 0; i < cookies.length; i++) {
        const cookie = cookies[i].trim();
        if (cookie.startsWith(name + '=')) {
            return decodeURIComponent(cookie.substring(name.length + 1));
        }
    }

    return '';
}

function getCsrfFieldName() {
    return (window.appCsrf && window.appCsrf.fieldName) ? String(window.appCsrf.fieldName) : 'csrf_token_name';
}

function getCsrfHashValue() {
    if (window.appCsrf && typeof window.appCsrf.hash === 'string' && window.appCsrf.hash.trim() !== '') {
        return window.appCsrf.hash.trim();
    }

    const cookieToken = getCookieValue('csrf_cookie_name');
    if (cookieToken) {
        return cookieToken;
    }

    const tokenInput = document.querySelector(`input[name="${getCsrfFieldName()}"]`);
    return tokenInput ? String(tokenInput.value || '') : '';
}

function appendCsrfTokenToData(data = {}) {
    const payload = Object.assign({}, data);
    const tokenValue = getCsrfHashValue();

    if (tokenValue) {
        payload[getCsrfFieldName()] = tokenValue;
    }

    return payload;
}

function updateCsrfToken(nextToken) {
    if (!nextToken || typeof nextToken !== 'string') {
        return;
    }

    window.appCsrf = window.appCsrf || {};
    window.appCsrf.hash = nextToken;

    document.querySelectorAll(`input[name="${getCsrfFieldName()}"]`).forEach((input) => {
        input.value = nextToken;
    });
}

function updateCsrfTokenFromResponse(payload) {
    if (!payload || typeof payload !== 'object') {
        return;
    }

    const nextToken = payload.csrf_hash || payload.csrfHash || payload.token || '';
    if (typeof nextToken === 'string' && nextToken.trim() !== '') {
        updateCsrfToken(nextToken.trim());
    }
}

function resolveAjaxErrorMessage(xhr, fallbackMessage = 'Terjadi kesalahan. Silakan coba lagi.') {
    const responseJson = xhr && xhr.responseJSON ? xhr.responseJSON : null;
    updateCsrfTokenFromResponse(responseJson);

    if (responseJson && responseJson.message) {
        return responseJson.message;
    }

    if (xhr && xhr.status === 403) {
        return 'Permintaan ditolak oleh sistem keamanan. Silakan muat ulang halaman lalu coba lagi.';
    }

    if (xhr && xhr.status === 401) {
        return 'Sesi login Anda berakhir. Silakan login kembali.';
    }

    return fallbackMessage;
}

// Attach CSRF header from cookie for all same-origin AJAX requests
$(document).ajaxSend(function (event, jqXHR) {
    const csrfCookieToken = getCsrfHashValue();
    if (csrfCookieToken) {
        jqXHR.setRequestHeader('X-CSRF-TOKEN', csrfCookieToken);
    }
});

$(document).ajaxComplete(function (event, jqXHR) {
    const responseJson = jqXHR && jqXHR.responseJSON ? jqXHR.responseJSON : null;
    if (responseJson) {
        updateCsrfTokenFromResponse(responseJson);
    }
});

// Update cart count (called globally)
function updateCartCount() {
    if ($('#cart-count').length) {
        $.get(baseUrl + 'customer/cart/count', function (data) {
            if (data.count > 0) {
                $('#cart-count').text(data.count).show();
            } else {
                $('#cart-count').hide();
            }
        }).fail(function () {
            // User not logged in or error
            $('#cart-count').hide();
        });
    }
}

// Format currency (Indonesian Rupiah)
function formatCurrency(amount) {
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
}

// Show loading spinner
function showLoading(element) {
    $(element).html('<span class="spinner-border spinner-border-sm me-1"></span>Loading...');
    $(element).prop('disabled', true);
}

// Hide loading spinner
function hideLoading(element, originalText) {
    $(element).html(originalText);
    $(element).prop('disabled', false);
}

// Show toast notification
function showToast(message, type = 'success') {
    const normalizedMessage = String(message || '').trim();
    if (!normalizedMessage) {
        return;
    }

    window.__rooToastHistory = window.__rooToastHistory || new Map();
    window.__rooActiveToasts = window.__rooActiveToasts || [];

    const now = Date.now();
    const signature = `${type}::${normalizedMessage}`;
    const lastShownAt = window.__rooToastHistory.get(signature) || 0;
    if (now - lastShownAt < 1800) {
        return;
    }
    window.__rooToastHistory.set(signature, now);

    const toneMap = {
        success: { bgClass: 'bg-success', icon: 'check-circle-fill', delay: 3200 },
        error: { bgClass: 'bg-danger', icon: 'exclamation-triangle-fill', delay: 5200 },
        info: { bgClass: 'bg-info', icon: 'info-circle-fill', delay: 4200 },
    };
    const tone = toneMap[type] || toneMap.success;

    const toastHtml = `
        <div class="toast align-items-center text-white ${tone.bgClass} border-0 shadow-sm" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${tone.icon} me-2"></i>${normalizedMessage}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    let toastContainer = $('#toast-container');
    if (!toastContainer.length) {
        $('body').append('<div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>');
        toastContainer = $('#toast-container');
    }

    toastContainer.append(toastHtml);
    const toastElement = toastContainer.find('.toast').last()[0];
    const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: tone.delay });

    while (window.__rooActiveToasts.length >= 4) {
        const oldestToast = window.__rooActiveToasts.shift();
        if (oldestToast) {
            oldestToast.hide();
        }
    }

    window.__rooActiveToasts.push(toast);
    toast.show();

    $(toastElement).on('hidden.bs.toast', function () {
        window.__rooActiveToasts = (window.__rooActiveToasts || []).filter((instance) => instance !== toast);
        $(this).remove();
    });
}

// Confirm dialog
function confirmAction(message, callback) {
    const safeCallback = typeof callback === 'function' ? callback : function () { };

    if (typeof bootstrap === 'undefined') {
        if (window.confirm(message)) {
            safeCallback();
        }
        return;
    }

    let modalElement = document.getElementById('global-confirm-modal');
    if (!modalElement) {
        modalElement = document.createElement('div');
        modalElement.id = 'global-confirm-modal';
        modalElement.className = 'modal fade';
        modalElement.tabIndex = -1;
        modalElement.setAttribute('aria-hidden', 'true');
        modalElement.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-crimson text-white">
                        <h5 class="modal-title"><i class="bi bi-question-circle me-2"></i>Konfirmasi</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0" id="global-confirm-message"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-crimson btn-pill" id="global-confirm-accept">Ya, Lanjutkan</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modalElement);
    }

    const messageElement = document.getElementById('global-confirm-message');
    const acceptButton = document.getElementById('global-confirm-accept');
    if (messageElement) {
        messageElement.textContent = message;
    }

    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    const handleConfirm = function () {
        acceptButton.removeEventListener('click', handleConfirm);
        modalInstance.hide();
        safeCallback();
    };

    acceptButton.addEventListener('click', handleConfirm);
    modalElement.addEventListener('hidden.bs.modal', function cleanup() {
        acceptButton.removeEventListener('click', handleConfirm);
        modalElement.removeEventListener('hidden.bs.modal', cleanup);
    });

    modalInstance.show();
}

// Keep alerts visible (no auto-hide/fadeout)
$(document).ready(function () {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Number input validation
    $('input[type="number"]').on('keypress', function (e) {
        if (e.which < 48 || e.which > 57) {
            e.preventDefault();
        }
    });

    // Image preview on file input
    $('input[type="file"][accept*="image"]').on('change', function (e) {
        const file = e.target.files[0];
        const preview = $(this).data('preview');

        if (file && preview) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $(preview).attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });

    // --- PREMIUM NAVBAR SCROLL EFFECT ---
    const navbar = $('.navbar');
    let lastScroll = 0;

    $(window).on('scroll', function () {
        const currentScroll = $(this).scrollTop();

        if (currentScroll > 50) {
            navbar.addClass('scrolled');
        } else {
            navbar.removeClass('scrolled');
        }

        lastScroll = currentScroll;
    });

    // --- SMOOTH SCROLL FOR ANCHOR LINKS ---
    $('a[href^="#"]').on('click', function (e) {
        const target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 80
            }, 800, 'swing');
        }
    });

    // --- INTERSECTION OBSERVER FOR FADE-IN ANIMATIONS ---
    if ('IntersectionObserver' in window) {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe cards and sections for animation
        document.querySelectorAll('.card, .stat-card, .feature-icon').forEach(function (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    }
});

// Format date (Indonesian locale)
function formatDate(dateString) {
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

// Validate file upload
function validateFile(input, maxSizeMB = 2, allowedTypes = ['image/jpeg', 'image/png', 'image/jpg']) {
    const file = input.files[0];

    if (!file) {
        return { valid: false, message: 'Pilih file terlebih dahulu' };
    }

    if (!allowedTypes.includes(file.type)) {
        return { valid: false, message: 'Format file tidak didukung. Gunakan JPG, JPEG, atau PNG' };
    }

    const maxSize = maxSizeMB * 1024 * 1024; // Convert to bytes
    if (file.size > maxSize) {
        return { valid: false, message: `Ukuran file maksimal ${maxSizeMB}MB` };
    }

    return { valid: true };
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Disalin ke clipboard!', 'success');
    }).catch(() => {
        showToast('Gagal menyalin', 'error');
    });
}
