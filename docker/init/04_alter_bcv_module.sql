-- ============================================================
-- MySan — Migración 04: Módulo Financiero Multimoneda BCV
-- Agrega los campos requeridos por el informe técnico §6.2.1
-- ============================================================

-- 1. pagos: monto pagado en Bolívares (informado por el participante)
ALTER TABLE pagos
    ADD COLUMN monto_bs_pagado DECIMAL(16,2) DEFAULT NULL
    COMMENT 'Monto en Bs (VES) que el participante transfirió, informado al reportar el pago';

-- 2. tasas_cambio: permitir origen 'manual' (ingresado por admin) además de 'auto'
ALTER TABLE tasas_cambio
    MODIFY COLUMN origen ENUM('auto','manual') DEFAULT 'auto'
    COMMENT 'auto=obtenida via API BCV, manual=ingresada por el administrador';

SELECT 'Migracion 04 completada' AS resultado;
