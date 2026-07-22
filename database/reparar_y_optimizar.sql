-- ============================================================================
-- REPARACIÓN Y OPTIMIZACIÓN - ChaoGuo
-- Ejecutar en phpMyAdmin (cPanel), seleccionando primero la base de datos.
-- Puede ejecutarse completo o por secciones. Es seguro correrlo una sola vez.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. REPARAR pedidos ya enviados desde la app de mesas que quedaron
--    "invisibles" en el panel admin (les faltaba producto, cliente y estado).
-- ----------------------------------------------------------------------------

-- 1a. Completar nombre y prefijo del producto
UPDATE pedidos p
JOIN productos pr ON p.id_pro = pr.id_pro
SET p.producto = pr.nombre,
    p.prefijos = pr.prefijo
WHERE (p.producto IS NULL OR p.producto = '');

-- 1b. Completar cliente genérico donde falte
UPDATE pedidos
SET id_cliente = 1
WHERE id_cliente IS NULL OR id_cliente = 0;

-- 1c. Pedidos de días ANTERIORES sin estado: marcarlos como entregados
--     (para que NO disparen alertas de "pedido nuevo" viejas)
UPDATE pedidos
SET estado = 'entregado', estado_boton = 'entregado'
WHERE (estado IS NULL OR estado = '')
  AND DATE(fecha) < CURDATE();

-- 1d. Pedidos de HOY sin estado: marcarlos como nuevos para que aparezcan
UPDATE pedidos
SET estado = 'nuevo', estado_boton = 'nuevo'
WHERE (estado IS NULL OR estado = '')
  AND DATE(fecha) = CURDATE();

-- ----------------------------------------------------------------------------
-- 2. ÍNDICES para acelerar las consultas más frecuentes
--    (el listado de pedidos, el estado de mesas que la app consulta cada 5
--    segundos, y la verificación de pagos en caja).
--    NOTA: si algún índice ya existe, MySQL mostrará el error "Duplicate key
--    name" en esa línea — es inofensivo, continúa con las siguientes.
-- ----------------------------------------------------------------------------

CREATE INDEX idx_pedidos_numero      ON pedidos (numero_pedido);
CREATE INDEX idx_pedidos_fecha       ON pedidos (fecha);
CREATE INDEX idx_pedidos_estado      ON pedidos (estado);
CREATE INDEX idx_turnero_pedido      ON turnero (id_pedido);
CREATE INDEX idx_turnero_fecha_tipo  ON turnero (fecha, tipo_solicitud);
CREATE INDEX idx_caja_pedido         ON caja (id_pedidoc);
CREATE INDEX idx_comentarios_pedido  ON comentarios (id_pedido);

-- ----------------------------------------------------------------------------
-- 3. SINCRONIZAR el consecutivo de pedidos
--    La API usa la tabla `consecutivos` para numerar pedidos, pero otras
--    partes del sistema usan MAX(numero_pedido)+1. Esto los alinea para
--    evitar que dos pedidos distintos reciban el mismo número.
-- ----------------------------------------------------------------------------

UPDATE consecutivos
SET valor = (SELECT COALESCE(MAX(numero_pedido), 0) FROM pedidos)
WHERE nombre = 'num_pedido'
  AND valor < (SELECT COALESCE(MAX(numero_pedido), 0) FROM pedidos);
