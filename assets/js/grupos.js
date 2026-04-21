// Grupo management functions
async function createGrupo(formData) {
    try {
        const response = await fetch('../../api/grupos.php?action=create', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Grupo creado exitosamente', 'success');
            closeModal('nuevoGrupoModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

async function loadGrupos(categoriaId) {
    try {
        const response = await fetch(`../../api/grupos.php?action=list&categoria_id=${categoriaId}`);
        const data = await response.json();

        if (data.success) {
            return data.data.grupos;
        }
        return [];
    } catch (error) {
        console.error('Error loading grupos:', error);
        return [];
    }
}

async function deleteGrupo(id) {
    if (!confirmAction('¿Estás seguro de eliminar este grupo?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);

        const response = await fetch('../../api/grupos.php?action=delete', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Grupo eliminado exitosamente', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

// Edit Group
async function editGrupo(id) {
    try {
        const response = await fetch(`../../api/grupos.php?action=get&id=${id}`);
        const data = await response.json();

        if (data.success) {
            const grupo = data.data.grupo;

            // Populate modal
            document.getElementById('edit_grupo_id').value = grupo.id;
            document.getElementById('edit_grupo_nombre').value = grupo.nombre;
            document.getElementById('edit_grupo_estado').value = grupo.estado;
            
            const dp = document.getElementById('edit_grupo_fecha_inicio');
            if(dp) dp.value = grupo.fecha_inicio;

            openModal('editarGrupoModal');
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading group:', error);
        showNotification('Error al cargar datos del grupo', 'error');
    }
}

async function updateGrupo(formData) {
    try {
        const response = await fetch('../../api/grupos.php?action=update', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Grupo actualizado exitosamente', 'success');
            closeModal('editarGrupoModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

// Handle form submission
document.addEventListener('DOMContentLoaded', function () {
    const grupoForm = document.getElementById('nuevoGrupoForm');
    const editGrupoForm = document.getElementById('editarGrupoForm');

    if (grupoForm) {
        grupoForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            // Auto-fill numero_cuotas with the value of cupos_totales if not present
            if (!formData.has('numero_cuotas') && formData.has('cupos_totales')) {
                formData.append('numero_cuotas', formData.get('cupos_totales'));
            }

            createGrupo(formData);
        });
    }

    if (editGrupoForm) {
        editGrupoForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            updateGrupo(formData);
        });
    }

    // Calculate monto_cuota on change
    const productoSelect = document.getElementById('producto_id');
    const cuposInput = document.getElementById('cupos_totales') || document.getElementById('numero_cuotas');
    const montoCuotaDisplay = document.getElementById('monto_cuota_display');

    if (productoSelect && cuposInput && montoCuotaDisplay) {
        function calculateMontoCuota() {
            const selectedOption = productoSelect.options[productoSelect.selectedIndex];
            const valorTotal = parseFloat(selectedOption.dataset.valor || 0);
            const cantidad = parseInt(cuposInput.value) || 1;

            const montoCuota = valorTotal / cantidad;
            montoCuotaDisplay.textContent = formatCurrency(montoCuota);
        }

        productoSelect.addEventListener('change', calculateMontoCuota);
        cuposInput.addEventListener('input', calculateMontoCuota);
    }
});
