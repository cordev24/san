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

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: var(--space-6);
        right: var(--space-6);
        padding: var(--space-4) var(--space-6);
        background: ${type === 'success' ? 'var(--color-menta)' : '#ff6464'};
        color: var(--color-background);
        border-radius: var(--radius-md);
        font-weight: var(--font-weight-semibold);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        box-shadow: var(--shadow-lg);
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

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
`;
document.head.appendChild(style);
