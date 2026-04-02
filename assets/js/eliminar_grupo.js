// Add eliminarGrupo function to grupos.js
async function eliminarGrupo(id) {
    if (!confirmAction('¿Estás seguro de eliminar este grupo? Se eliminarán todos los participantes y pagos asociados. Esta acción no se puede deshacer.')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    try {
        const response = await fetch('../../api/grupos.php', {
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
