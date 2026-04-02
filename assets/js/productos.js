// Product management functions
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

async function loadProductoForEdit(id) {
    try {
        const response = await fetch(`../../api/productos.php?action=get&id=${id}`);
        const data = await response.json();

        if (data.success) {
            const producto = data.data.producto;

            // Fill edit form
            document.getElementById('edit_producto_id').value = producto.id;
            document.getElementById('edit_nombre').value = producto.nombre;
            document.getElementById('edit_marca').value = producto.marca || '';
            document.getElementById('edit_modelo').value = producto.modelo || '';
            document.getElementById('edit_descripcion').value = producto.descripcion || '';
            document.getElementById('edit_valor_total').value = producto.valor_total;

            openModal('editarProductoModal');
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

// Handle form submissions
document.addEventListener('DOMContentLoaded', function () {
    const nuevoProductoForm = document.getElementById('nuevoProductoForm');
    if (nuevoProductoForm) {
        nuevoProductoForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            createProducto(formData);
        });
    }

    const editarProductoForm = document.getElementById('editarProductoForm');
    if (editarProductoForm) {
        editarProductoForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            updateProducto(formData);
        });
    }
});
