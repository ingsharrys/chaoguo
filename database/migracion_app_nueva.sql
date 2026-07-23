-- ============================================================================
-- MIGRACIÓN DE BASE DE DATOS PARA EL APLICATIVO NUEVO - ChaoGuo
-- Base de datos: hgarzon_u936058592_restaurant
--
-- El aplicativo nuevo necesita tablas y columnas que la base de datos actual
-- no tiene (venía de la app antigua). Este script agrega TODO lo que falta,
-- sin borrar ni alterar los datos existentes.
--
-- Ejecutar UNA VEZ, completo, en phpMyAdmin (pestaña SQL) o por terminal:
--   mysql -u USUARIO_DB -p NOMBRE_DB < database/migracion_app_nueva.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Tabla `turnero`: la API registra aquí cada pedido y el panel la lee.
--    SIN ESTA TABLA, TODOS LOS PEDIDOS DE LA APP FALLAN CON ERROR 500.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS turnero (
  id_t INT AUTO_INCREMENT PRIMARY KEY,
  id_pedido INT NOT NULL,
  turno INT NOT NULL DEFAULT 0,
  fecha DATETIME NOT NULL,
  tipo_solicitud INT NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'nuevo',
  id_cliente INT NOT NULL DEFAULT 1,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_turnero_pedido (id_pedido),
  KEY idx_turnero_fecha_tipo (fecha, tipo_solicitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 2. Tabla `users`: usuarios del login del administrador.
--    Se crea con el usuario admin (contraseña temporal: ChaoGuo2026*).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, email, password)
SELECT 'admin', 'dario.charry.ramos@gmail.com',
       '$2y$12$G3u4xBfJYWJ0Ou77uV714uRS9XAvNlmvEBqqFXAnGyyeQKpyNCWUq'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'dario.charry.ramos@gmail.com');

-- ----------------------------------------------------------------------------
-- 3. Columnas nuevas en `pedidos`: el panel admin las usa para mostrar,
--    filtrar y ordenar los pedidos.
-- ----------------------------------------------------------------------------
ALTER TABLE pedidos
  ADD COLUMN id_cliente INT NOT NULL DEFAULT 1,
  ADD COLUMN producto VARCHAR(200) NOT NULL DEFAULT '',
  ADD COLUMN prefijos VARCHAR(50) NOT NULL DEFAULT '',
  ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'nuevo',
  ADD COLUMN estado_boton VARCHAR(20) NOT NULL DEFAULT 'nuevo';

-- ----------------------------------------------------------------------------
-- 4. Los pedidos históricos quedan como 'entregado' para que la alerta de
--    "pedido nuevo" no se dispare con pedidos viejos.
-- ----------------------------------------------------------------------------
UPDATE pedidos
SET estado = 'entregado', estado_boton = 'entregado'
WHERE DATE(fecha) < CURDATE();

-- ----------------------------------------------------------------------------
-- 5. Completar nombre y prefijo del producto en los pedidos existentes.
-- ----------------------------------------------------------------------------
UPDATE pedidos p
JOIN productos pr ON p.id_pro = pr.id_pro
SET p.producto = pr.nombre,
    p.prefijos = pr.prefijo
WHERE p.producto = '';

-- ----------------------------------------------------------------------------
-- 6. Cliente genérico (id=1): los pedidos de mesa se asocian a este cliente.
--    La tabla clientes está vacía y el panel exige que el cliente exista.
-- ----------------------------------------------------------------------------
INSERT INTO clientes (id, cliente, celular, email, direccion, cedula, barrio)
SELECT 1, 'Cliente Mesa', '', '', '', NULL, ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM clientes WHERE id = 1);

-- ----------------------------------------------------------------------------
-- 7. Si hay mesas abiertas en este momento, crearles su registro de turno
--    para que aparezcan de inmediato en el panel.
-- ----------------------------------------------------------------------------
INSERT INTO turnero (id_pedido, turno, fecha, tipo_solicitud, estado, id_cliente)
SELECT m.id_pedido, 0, COALESCE(m.fecha, NOW()), 52, 'nuevo', 1
FROM mesas m
WHERE m.id_pedido IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM turnero t WHERE t.id_pedido = m.id_pedido);

-- ----------------------------------------------------------------------------
-- 8. Índices de rendimiento. Si alguno ya existe, MySQL dirá
--    "Duplicate key name" en esa línea: es inofensivo, continúa.
-- ----------------------------------------------------------------------------
CREATE INDEX idx_pedidos_numero     ON pedidos (numero_pedido);
CREATE INDEX idx_pedidos_fecha      ON pedidos (fecha);
CREATE INDEX idx_pedidos_estado     ON pedidos (estado);
CREATE INDEX idx_caja_pedido        ON caja (id_pedidoc);
CREATE INDEX idx_comentarios_pedido ON comentarios (id_pedido);
CREATE INDEX idx_mesas_pedido       ON mesas (id_pedido);

-- ----------------------------------------------------------------------------
-- 9. Sincronizar el consecutivo de pedidos con el máximo real.
-- ----------------------------------------------------------------------------
UPDATE consecutivos
SET valor = (SELECT COALESCE(MAX(numero_pedido), 0) FROM pedidos)
WHERE nombre = 'num_pedido'
  AND valor < (SELECT COALESCE(MAX(numero_pedido), 0) FROM pedidos);

-- ----------------------------------------------------------------------------
-- 10. Restaurar claves primarias y auto-incrementos.
--     La importación de la base de datos llegó SIN claves primarias ni
--     auto_increment, lo que rompe todos los INSERT (pedidos, caja, etc.).
--     Si alguna tabla ya la tiene, dará "Multiple primary key defined":
--     es inofensivo, continúa con la siguiente.
-- ----------------------------------------------------------------------------
ALTER TABLE pedidos     MODIFY id_pedido INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id_pedido);
ALTER TABLE caja        MODIFY id_c      INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id_c);
ALTER TABLE clientes    MODIFY id        INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id);
ALTER TABLE comentarios MODIFY idc       INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (idc);
ALTER TABLE mesas       MODIFY idm       INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (idm);
ALTER TABLE meseros     MODIFY id_mese   INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id_mese);
ALTER TABLE precios     MODIFY idpre     INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (idpre);
ALTER TABLE productos   MODIFY id_pro    INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id_pro);
ALTER TABLE domicilios  MODIFY id_d      INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id_d);
