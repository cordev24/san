// ──────────────────────────────────────────────
// Product CRUD
// ──────────────────────────────────────────────

async function createProducto(formData) {
    try {
        const response = await fetch('../../api/productos.php?action=create', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showNotification('Producto creado exitosamente', 'success');
            closeModal('nuevoProductoModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

async function updateProducto(formData) {
    try {
        const response = await fetch('../../api/productos.php?action=update', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showNotification('Producto actualizado exitosamente', 'success');
            closeModal('editarProductoModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

async function deleteProducto(id) {
    if (!confirmAction('¿Estás seguro de eliminar este producto?')) {
        return;
    }
    try {
        const formData = new FormData();
        formData.append('id', id);
        const response = await fetch('../../api/productos.php?action=delete', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showNotification('Producto eliminado exitosamente', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

// ──────────────────────────────────────────────
// Load product for edit (multi-image)
// ──────────────────────────────────────────────

async function loadProductoForEdit(id) {
    try {
        const response = await fetch(`../../api/productos.php?action=get&id=${id}`);
        const data = await response.json();

        if (data.success) {
            const producto = data.data.producto;

            // Fill form fields
            document.getElementById('edit_producto_id').value = producto.id;
            document.getElementById('edit_nombre').value = producto.nombre;
            document.getElementById('edit_marca').value = producto.marca || '';
            document.getElementById('edit_modelo').value = producto.modelo || '';
            document.getElementById('edit_descripcion').value = producto.descripcion || '';
            document.getElementById('edit_valor_total').value = producto.valor_total;

            // Load image gallery
            renderEditGallery(producto.imagenes || [], producto.imagen);
            openModal('editarProductoModal');
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

// ──────────────────────────────────────────────
// Edit gallery rendering
// ──────────────────────────────────────────────

function renderEditGallery(imagenes, coverRuta) {
    var container = document.getElementById('edit_gallery');
    container.innerHTML = '';

    if (!imagenes || imagenes.length === 0) {
        container.innerHTML = '<div class="gallery-empty">Sin imágenes</div>';
        return;
    }

    imagenes.forEach(function (img) {
        var isCover = img.ruta === coverRuta;
        var div = document.createElement('div');
        div.className = 'gallery-thumb' + (isCover ? ' gallery-thumb--cover' : '');

        div.innerHTML =
            '<img src="../../' + img.ruta + '" loading="lazy" onerror="this.parentElement.classList.add(\'gallery-thumb--broken\')">' +
            '<div class="gallery-thumb-overlay">' +
                '<button type="button" class="gallery-btn gallery-btn--cover" ' +
                    'onclick="setCover(' + img.id + ')" title="Establecer como principal">' +
                    '<svg class="icon-sm"><use href="#icon-check-circle"></use></svg>' +
                '</button>' +
                '<button type="button" class="gallery-btn gallery-btn--del" ' +
                    'onclick="deleteImagen(' + img.id + ')" title="Eliminar">' +
                    '<svg class="icon-sm"><use href="#icon-trash-2"></use></svg>' +
                '</button>' +
            '</div>' +
            (isCover ? '<span class="gallery-cover-badge">Principal</span>' : '');

        container.appendChild(div);
    });
}

// ──────────────────────────────────────────────
// Image management
// ──────────────────────────────────────────────

async function deleteImagen(imagenId) {
    if (!confirmAction('¿Eliminar esta imagen?')) return;

    try {
        var formData = new FormData();
        formData.append('id', imagenId);
        formData.append('action', 'delete_imagen');
        var response = await fetch('../../api/productos.php?action=delete_imagen', {
            method: 'POST',
            body: formData
        });
        var data = await response.json();
        if (data.success) {
            showNotification('Imagen eliminada', 'success');
            // Reload the edit form
            var prodId = document.getElementById('edit_producto_id').value;
            if (prodId) loadProductoForEdit(prodId);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

async function setCover(imagenId) {
    try {
        var formData = new FormData();
        formData.append('id', imagenId);
        formData.append('action', 'set_cover');
        var response = await fetch('../../api/productos.php?action=set_cover', {
            method: 'POST',
            body: formData
        });
        var data = await response.json();
        if (data.success) {
            showNotification('Imagen principal actualizada', 'success');
            var prodId = document.getElementById('edit_producto_id').value;
            if (prodId) loadProductoForEdit(prodId);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

// ──────────────────────────────────────────────
// Multi-file preview (create modal)
// ──────────────────────────────────────────────

function previewMultiImagen(input) {
    var container = document.getElementById('nuevo_galeria_preview');
    container.innerHTML = '';

    if (!input.files || input.files.length === 0) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'flex';

    for (var i = 0; i < input.files.length; i++) {
        (function (file) {
            var div = document.createElement('div');
            div.className = 'gallery-thumb';

            var img = document.createElement('img');
            img.className = 'gallery-thumb-img';
            img.loading = 'lazy';

            var reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);

            div.appendChild(img);

            var name = document.createElement('span');
            name.className = 'gallery-thumb-name';
            name.textContent = file.name.length > 20
                ? file.name.substring(0, 17) + '...'
                : file.name;
            div.appendChild(name);

            container.appendChild(div);
        })(input.files[i]);
    }
}

// ──────────────────────────────────────────────
// Form submissions
// ──────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    var nuevoForm = document.getElementById('nuevoProductoForm');
    if (nuevoForm) {
        nuevoForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            createProducto(formData);
        });
    }

    var editForm = document.getElementById('editarProductoForm');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            updateProducto(formData);
        });
    }
});

// viewGallery is now defined globally in shared.js
