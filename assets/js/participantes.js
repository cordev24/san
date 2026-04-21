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

    // Format cedula input and auto-fill data if user exists
    const cedulaInput = document.getElementById('cedula');
    let lastCheckedCedula = '';
    if (cedulaInput) {
        cedulaInput.addEventListener('input', async function (e) {
            // Remove non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            const cedula = this.value;
            if (cedula.length >= 7 && cedula !== lastCheckedCedula) {
                lastCheckedCedula = cedula;
                try {
                    const response = await fetch(`../../api/participantes.php?action=get_by_cedula&cedula=${cedula}`);
                    const data = await response.json();
                    if (data.success && data.data && data.data.participante) {
                        const p = data.data.participante;
                        document.querySelector('#nuevoParticipanteForm input[name="nombre"]').value = p.nombre || '';
                        document.querySelector('#nuevoParticipanteForm input[name="apellido"]').value = p.apellido || '';
                        
                        const tl = document.querySelector('#nuevoParticipanteForm input[name="telefono"]');
                        if(tl) tl.value = p.telefono || '';
                        
                        const dr = document.querySelector('#nuevoParticipanteForm textarea[name="direccion"]');
                        if(dr) dr.value = p.direccion || '';
                        
                        showNotification('Datos autocompletados desde el registro previo', 'info');
                    }
                } catch (e) {
                    // Fail silently, just means they are totally new
                }
            }
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
