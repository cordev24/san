// Shared modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ── Toast system ──────────────────────────────────────────────────────────────
// Stacking container for toasts
let toastContainer = null;

function getToastContainer() {
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: var(--space-6, 24px);
            right: var(--space-6, 24px);
            z-index: 100000;
            display: flex;
            flex-direction: column;
            gap: var(--space-3, 12px);
            pointer-events: none;
            max-width: 400px;
            width: calc(100% - 48px);
        `;
        document.body.appendChild(toastContainer);
    }
    return toastContainer;
}

// Toast color map
const TOAST_COLORS = {
    success: { bg: 'var(--color-menta, #38d9a9)', icon: '#icon-check-circle' },
    error:   { bg: 'var(--color-error, #ef4444)', icon: '#icon-alert-triangle' },
    info:    { bg: 'var(--color-electro, #60a5fa)', icon: '#icon-alert-circle' },
};

/**
 * Muestra un toast flotante con auto-dismiss y click to dismiss.
 * @param {string} message - Texto del mensaje
 * @param {'success'|'error'|'info'} type - Tipo de toast (define color)
 * @param {number} duration - Milisegundos antes de auto-dismiss (default 4000)
 */
function showNotification(message, type = 'success', duration = 4000) {
    const colors = TOAST_COLORS[type] || TOAST_COLORS.success;
    const container = getToastContainer();

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.cssText = `
        background: ${colors.bg};
        color: #fff;
        padding: var(--space-4, 16px) var(--space-5, 20px);
        border-radius: var(--radius-md, 8px);
        font-weight: var(--font-weight-semibold, 600);
        font-size: var(--font-size-sm, 14px);
        box-shadow: 0 8px 32px rgba(0,0,0,0.25);
        animation: toastSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: auto;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: var(--space-3, 12px);
        line-height: 1.4;
        word-break: break-word;
        position: relative;
    `;

    // Add close icon area
    toast.innerHTML = `
        <svg style="width:18px;height:18px;flex-shrink:0;stroke:currentColor;stroke-width:2;">
            <use href="${colors.icon}"></use>
        </svg>
        <span style="flex:1;">${escapeHtml(message)}</span>
        <svg style="width:16px;height:16px;flex-shrink:0;opacity:0.7;stroke:currentColor;stroke-width:2;">
            <use href="#icon-x"></use>
        </svg>
    `;

    // Click to dismiss
    toast.addEventListener('click', () => dismissToast(toast));

    container.appendChild(toast);

    // Auto-dismiss
    const timer = setTimeout(() => dismissToast(toast), duration);

    // Store timer on element for cleanup
    toast._dismissTimer = timer;
}

function dismissToast(toast) {
    if (toast._dismissing) return;
    toast._dismissing = true;

    if (toast._dismissTimer) {
        clearTimeout(toast._dismissTimer);
    }

    toast.style.animation = 'toastSlideOut 0.3s ease forwards';
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 300);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// ── Sidebar toggle ────────────────────────────────────────────────────────────
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar) return;
    const isOpen = sidebar.classList.toggle('open');
    if (backdrop) backdrop.classList.toggle('active', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('active');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
});

window.TASA_BCV = 50.00; // Fallback inicial

// Intentar cargar la tasa de cambio globalmente
fetch('/api/tasa_bcv.php')
    .then(res => res.json())
    .then(data => {
        if (data.success && data.data && data.data.tasa) {
            window.TASA_BCV = parseFloat(data.data.tasa);
            // Si hay elementos renderizados previamente que deban actualizarse, 
            // se podría disparar un evento custom aquí.
        }
    })
    .catch(console.error);

// Format currency - CHANGED TO USD and Bs
function formatCurrency(amount) {
    const num = parseFloat(amount);
    const safe = isNaN(num) ? 0 : num;
    let usd = safe.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    let bs = (safe * window.TASA_BCV).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    return '$' + usd + ' (Bs ' + bs + ')';
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
}

// Confirm dialog
function confirmAction(message) {
    return confirm(message);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    @keyframes toastSlideIn {
        from {
            transform: translateX(120%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes toastSlideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(120%);
            opacity: 0;
        }
    }

    @media (max-width: 480px) {
        #toast-container {
            left: var(--space-4, 16px);
            right: var(--space-4, 16px);
            max-width: none;
            width: auto;
        }
    }
`;
document.head.appendChild(style);
