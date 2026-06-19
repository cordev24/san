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

// ── Impresión global de comprobantes ──────────────────────────────────────────
function imprimirRecibo(id) {
    window.open(`../../api/comprobantes.php?action=recibo&id=${id}`, '_blank');
}

function descargarComprobantePDF(id) {
    window.open(`../../api/comprobantes.php?action=pdf&id=${id}`, '_blank');
}

// ── Global Gallery Lightbox ───────────────────────────────────────────────────
// Injects modal HTML once into the DOM, then reuses it from any page/depth.

function ensureGalleryModal() {
    if (document.getElementById('globalGalleryModal')) return;

    const html = `
    <style>
        #globalGalleryModal .ggm-content {
            background: var(--color-surface,#1a1a1a);
            border: 1px solid var(--glass-border,rgba(255,255,255,.1));
            border-radius: var(--radius-xl,16px);
            padding: var(--space-6,24px);
            max-width: 900px;
            width: 96vw;
            max-height: 96vh;
            display: flex;
            flex-direction: column;
            gap: var(--space-4,16px);
            position: relative;
        }
        @media (max-width: 768px) {
            #globalGalleryModal .ggm-content {
                width: 100vw;
                height: 100vh;
                max-width: none;
                max-height: none;
                border-radius: 0;
                padding: var(--space-4, 16px);
                border: none;
            }
            #ggm-main-wrap { max-height: 65vh !important; }
        }
    </style>
    <div id="globalGalleryModal" class="modal-overlay" style="z-index:9999;">
        <div class="ggm-content">
            <!-- Header -->
            <div style="display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:var(--space-3,12px);">
                    <div style="
                        width:36px;height:36px;border-radius:var(--radius-md,8px);
                        background:var(--color-primary-tint,rgba(139,92,246,.15));
                        border:1px solid var(--color-primary-glow,rgba(139,92,246,.3));
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;
                    ">
                        <svg style="width:18px;height:18px;stroke:var(--color-primary,#8b5cf6);stroke-width:2;">
                            <use href="#icon-image"></use>
                        </svg>
                    </div>
                    <div>
                        <div id="ggm-title" style="font-weight:700;font-size:var(--font-size-lg,18px);color:var(--color-text-primary,#fff);"></div>
                        <div id="ggm-counter" style="font-size:var(--font-size-xs,12px);color:var(--color-text-tertiary,#666);margin-top:2px;"></div>
                    </div>
                </div>
                <button onclick="closeGalleryModal()" style="
                    width:36px;height:36px;border:1px solid var(--glass-border,rgba(255,255,255,.1));
                    border-radius:var(--radius-md,8px);background:transparent;cursor:pointer;
                    display:flex;align-items:center;justify-content:center;color:var(--color-text-secondary,#aaa);
                    transition:all .15s;
                " onmouseover="this.style.background='var(--color-surface-hover,rgba(255,255,255,.05))'"
                   onmouseout="this.style.background='transparent'">
                    <svg style="width:18px;height:18px;stroke:currentColor;stroke-width:2;">
                        <use href="#icon-x"></use>
                    </svg>
                </button>
            </div>

            <!-- Main image -->
            <div id="ggm-main-wrap" style="
                flex: 1 1 auto;
                min-height: 400px; /* Minimum height ensures it does not collapse */
                border-radius: var(--radius-lg,12px);
                background: var(--color-background,#0d0d0d);
                position: relative;
                overflow: hidden;
                cursor: zoom-in;
            " onwheel="ggmZoom(event)">
                <img id="ggm-main" src="" alt="" style="
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                    padding: 16px; /* Safety margin */
                    box-sizing: border-box;
                    transition: opacity .2s, transform 0.15s ease-out;
                    border-radius: var(--radius-md,8px);
                ">
                <!-- Prev / Next arrows -->
                <button id="ggm-prev" onclick="ggmNav(-1)" style="
                    position:absolute;left:var(--space-3,12px);top:50%;transform:translateY(-50%);
                    width:40px;height:40px;border-radius:50%;border:none;cursor:pointer;
                    background:rgba(0,0,0,.55);backdrop-filter:blur(6px);
                    display:flex;align-items:center;justify-content:center;
                    color:#fff;transition:all .15s;
                ">
                    <svg style="width:20px;height:20px;stroke:currentColor;stroke-width:2.5;"><use href="#icon-chevron-left"></use></svg>
                </button>
                <button id="ggm-next" onclick="ggmNav(1)" style="
                    position:absolute;right:var(--space-3,12px);top:50%;transform:translateY(-50%);
                    width:40px;height:40px;border-radius:50%;border:none;cursor:pointer;
                    background:rgba(0,0,0,.55);backdrop-filter:blur(6px);
                    display:flex;align-items:center;justify-content:center;
                    color:#fff;transition:all .15s;
                ">
                    <svg style="width:20px;height:20px;stroke:currentColor;stroke-width:2.5;"><use href="#icon-chevron-right"></use></svg>
                </button>
            </div>

            <!-- Thumbnails strip -->
            <div id="ggm-thumbs" style="
                display:flex;gap:var(--space-2,8px);overflow-x:auto;
                padding-bottom:4px;flex-shrink:0;
            "></div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', html);

    // Close on backdrop click
    document.getElementById('globalGalleryModal').addEventListener('click', function(e) {
        if (e.target === this) closeGalleryModal();
    });
}

// State
let _ggmImages = [];
let _ggmIndex  = 0;

function ggmSetIndex(i) {
    _ggmIndex = i;
    const img  = document.getElementById('ggm-main');
    const wrap = document.getElementById('ggm-main-wrap');
    const thumbs = document.getElementById('ggm-thumbs');
    const counter = document.getElementById('ggm-counter');

    img.style.opacity = '0';
    setTimeout(() => {
        _ggmScale = 1;
        img.style.transform = 'scale(1)';
        img.src = _ggmImages[i].ruta;
        img.style.opacity = '1';
    }, 120);

    counter.textContent = `${i + 1} / ${_ggmImages.length}`;

    Array.from(thumbs.children).forEach((t, idx) => {
        t.style.border = idx === i
            ? '2px solid var(--color-primary,#8b5cf6)'
            : '2px solid transparent';
        t.style.opacity = idx === i ? '1' : '0.6';
    });

    // Show/hide arrows
    const showNav = _ggmImages.length > 1;
    document.getElementById('ggm-prev').style.display = showNav ? 'flex' : 'none';
    document.getElementById('ggm-next').style.display = showNav ? 'flex' : 'none';
}

function ggmNav(dir) {
    let next = (_ggmIndex + dir + _ggmImages.length) % _ggmImages.length;
    ggmSetIndex(next);
}

function closeGalleryModal() {
    const m = document.getElementById('globalGalleryModal');
    if (m) m.classList.remove('active');
    document.body.style.overflow = '';
}

let _ggmScale = 1;
function ggmZoom(e) {
    e.preventDefault();
    _ggmScale += e.deltaY * -0.002;
    _ggmScale = Math.min(Math.max(1, _ggmScale), 5); // No menos de 1
    const img = document.getElementById('ggm-main');
    if(img) img.style.transform = `scale(${_ggmScale})`;
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    const m = document.getElementById('globalGalleryModal');
    if (!m || !m.classList.contains('active')) return;
    if (e.key === 'ArrowRight') ggmNav(1);
    if (e.key === 'ArrowLeft')  ggmNav(-1);
    if (e.key === 'Escape')     closeGalleryModal();
});

/**
 * Opens the gallery lightbox for a product.
 * Works from any page depth — uses absolute API path.
 * @param {number} productoId
 * @param {string} title
 */
async function viewGallery(productoId, title) {
    ensureGalleryModal();

    try {
        const res  = await fetch(`/api/productos.php?action=get&id=${productoId}`);
        const data = await res.json();

        if (!data.success) {
            showNotification(data.message || 'No se pudo cargar la galería', 'error');
            return;
        }

        const producto = data.data.producto;
        let imagenes   = producto.imagenes || [];

        // Fallback: si no hay galería pero sí portada
        if (imagenes.length === 0 && producto.imagen) {
            imagenes = [{ ruta: producto.imagen }];
        }

        if (imagenes.length === 0) {
            showNotification('Este producto no tiene imágenes cargadas', 'info');
            return;
        }

        // Build thumb strip
        const thumbsEl = document.getElementById('ggm-thumbs');
        thumbsEl.innerHTML = '';
        _ggmImages = imagenes;

        imagenes.forEach((img, idx) => {
            const t = document.createElement('img');
            t.src = img.ruta;  // absolute path from DB
            t.alt = `Imagen ${idx + 1}`;
            t.style.cssText = `
                width:72px;height:72px;object-fit:cover;flex-shrink:0;
                border-radius:var(--radius-md,8px);cursor:pointer;
                transition:all .15s;border:2px solid transparent;
            `;
            t.onclick = () => ggmSetIndex(idx);
            t.onerror = () => { t.style.opacity = '0.3'; };
            thumbsEl.appendChild(t);
        });

        document.getElementById('ggm-title').textContent = title || producto.nombre;
        ggmSetIndex(0);

        // Open
        document.getElementById('globalGalleryModal').classList.add('active');
        document.body.style.overflow = 'hidden';

    } catch (err) {
        console.error('viewGallery error:', err);
        showNotification('Error de conexión al cargar la galería', 'error');
    }
}
