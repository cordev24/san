-- Migration 003: Auto-asignación de turnos por orden de inscripción
-- Agrega 'orden_inscripcion' al ENUM de metodo_asignacion
ALTER TABLE turnos MODIFY COLUMN metodo_asignacion ENUM('aleatorio','manual','orden_inscripcion') NOT NULL;

-- Backfill: eliminar turnos existentes (solo hay 1, de Juan Perez en grupo 4)
DELETE FROM turnos;

-- Asignar turnos por orden de ID (orden de registro) para cada grupo
INSERT INTO turnos (grupo_san_id, participante_id, numero_turno, metodo_asignacion, estado, fecha_asignacion)
SELECT 
    p.grupo_san_id,
    p.id,
    ROW_NUMBER() OVER (PARTITION BY p.grupo_san_id ORDER BY p.id) as numero_turno,
    'orden_inscripcion',
    'asignado',
    NOW()
FROM participantes p
WHERE p.activo = 1;

SELECT 'Migracion 003 completada' AS resultado;
