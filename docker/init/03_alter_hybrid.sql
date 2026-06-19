-- ============================================================
-- MySan — Migración 03: Sistema de Registro Híbrido + Flujo de Aprobación
-- Compatible con MySQL 8.0 (sin IF NOT EXISTS en ALTER TABLE)
-- ============================================================

-- 1. grupos_san: campos para enlace de invitación
ALTER TABLE grupos_san
    ADD COLUMN enlace_invitacion VARCHAR(64) UNIQUE NULL COMMENT 'Token unico para auto-registro de participantes',
    ADD COLUMN estado_enlace ENUM('activo','inactivo') DEFAULT 'activo' COMMENT 'Habilitar/deshabilitar el link de invitacion';

-- 2. participantes: tipo de registro
ALTER TABLE participantes
    ADD COLUMN tipo_registro ENUM('autonomo','asistido') DEFAULT 'asistido'
    COMMENT 'autonomo=se registro via enlace, asistido=registrado por el admin';

-- 3. pagos: ampliar enum de estado para flujo de aprobación en 2 pasos
ALTER TABLE pagos
    MODIFY COLUMN estado ENUM('pendiente','pendiente_verificacion','pagado','atrasado') DEFAULT 'pendiente'
    COMMENT 'pendiente_verificacion=participante reporto comprobante, pagado=admin aprobo';

-- 4. pagos: agregar columna referencia_pago
ALTER TABLE pagos
    ADD COLUMN referencia_pago VARCHAR(100) DEFAULT NULL COMMENT 'Numero de referencia del pago reportado';

-- 5. Eliminar tabla mensajes (comunicacion externa via WhatsApp segun informe 6.1.4)
DROP TABLE IF EXISTS mensajes;

SELECT 'Migracion 03 completada exitosamente' AS resultado;
