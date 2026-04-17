-- Schema para Neon PostgreSQL
-- Ejecuta esto en el SQL Editor de Neon

CREATE TABLE reparaciones (
    id SERIAL PRIMARY KEY,
    ticket INTEGER NOT NULL UNIQUE,
    cliente VARCHAR(255) NOT NULL,
    dispositivo VARCHAR(255) NOT NULL,
    problema TEXT NOT NULL,
    estatus VARCHAR(50) NOT NULL DEFAULT 'en_revision',
    fecha_entrada DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_salida DATE,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO reparaciones (ticket, cliente, dispositivo, problema, estatus)
VALUES (0, 'Temp', 'Temp', 'Temp', 'en_revision');

DELETE FROM reparaciones WHERE ticket = 0;