// Participante management functions
async function createParticipante(formData) {
    try {
        const response = await fetch('../../api/participantes.php?action=create', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Participante inscrito exitosamente', 'success');
            closeModal('nuevoParticipanteModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

async function loadParticipantes(grupoSanId) {
    try {
        const url = grupoSanId
            ? `../../api/participantes.php?action=list&grupo_san_id=${grupoSanId}`
            : '../../api/participantes.php?action=list';

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            return data.data.participantes;
        }
        return [];
    } catch (error) {
        console.error('Error loading participantes:', error);
        return [];
    }
}

async function deleteParticipante(id) {
    if (!confirmAction('¿Estás seguro de eliminar este participante?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);

        const response = await fetch('../../api/participantes.php?action=delete', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Participante eliminado exitosamente', 'success');
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
    const participanteForm = document.getElementById('nuevoParticipanteForm');
    if (participanteForm) {
        participanteForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            createParticipante(formData);
        });
    }

    // Format cedula input
    const cedulaInput = document.getElementById('cedula');
    if (cedulaInput) {
        cedulaInput.addEventListener('input', function (e) {
            // Remove non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // Format telefono input
    const telefonoInput = document.getElementById('telefono');
    if (telefonoInput) {
        telefonoInput.addEventListener('input', function (e) {
            // Remove non-numeric characters and limit to 11 digits
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);
        });
    }
});
