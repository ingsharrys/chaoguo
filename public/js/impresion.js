/* ============================================================
   IMPRESIÓN DE TICKETS · CHAO GUO  (QZ Tray, ESC/POS 80mm)
   ------------------------------------------------------------
   - El ticket se arma con los datos REALES de la base de datos
     (controllers/imprimir_datos.php), no con lo pintado en la
     pantalla: siempre sale completo aunque la fila no cargue.
   - Reemplaza a printInvoicepc() de los scripts anteriores:
     este archivo debe incluirse DESPUÉS de script.js.
   - Conexión robusta a QZ: reutiliza la conexión activa en vez
     de reconectar (reconectar hacía fallar la segunda impresión).
   ============================================================ */

(function () {
  const ANCHO = 42;                       // columnas impresora 80mm

  /* ---------- Conexión robusta a QZ Tray ---------- */
  function qzConexion() {
    if (typeof qz === 'undefined') {
      alert('QZ Tray no está cargado en esta página.');
      return Promise.reject('qz no definido');
    }
    if (qz.websocket.isActive()) return Promise.resolve();
    return qz.websocket.connect({ retries: 1, delay: 1 }).catch(err => {
      alert('No se pudo conectar a QZ Tray. Verifica que el programa esté abierto en este computador.');
      throw err;
    });
  }

  /* ---------- Utilidades de formato ---------- */
  const $$$ = n => '$' + Number(n || 0).toLocaleString('es-CO');
  const linea = (c = '-') => c.repeat(ANCHO) + '\n';

  function fila(izq, der) {               // texto izquierda + valor derecha
    izq = String(izq); der = String(der);
    const espacio = ANCHO - izq.length - der.length;
    return espacio >= 1 ? izq + ' '.repeat(espacio) + der + '\n'
                        : izq + '\n' + ' '.repeat(Math.max(0, ANCHO - der.length)) + der + '\n';
  }

  function envolver(texto, sangria = '') {
    const util = ANCHO - sangria.length;
    const palabras = String(texto).split(/\s+/);
    const lineas = []; let actual = '';
    palabras.forEach(p => {
      const cand = actual ? actual + ' ' + p : p;
      if (cand.length <= util) { actual = cand; }
      else { if (actual) lineas.push(sangria + actual); actual = p.slice(0, util); }
    });
    if (actual) lineas.push(sangria + actual);
    return lineas.join('\n') + '\n';
  }

  const TIPOS = { 50: 'DOMICILIO', 51: 'RESTAURANTE', 52: 'MESA', 53: 'RECOGER / LLAMADA' };

  /* ---------- Construcción del ticket ---------- */
  function armarTicket(d) {
    const ESC = '\x1B', GS = '\x1D';
    const CENTRO = ESC + '\x61\x01', IZQ = ESC + '\x61\x00';
    const GRANDE = ESC + '\x21\x30', NORMAL = ESC + '\x21\x00';
    const NEGRITA = ESC + '\x45\x01', FIN_NEG = ESC + '\x45\x00';

    let t = ESC + '\x40';                                  // reset
    t += CENTRO + GRANDE + 'CHAO GUO' + NORMAL + '\n';
    t += CENTRO + 'Restaurante\n' + IZQ;
    t += linea('=');

    /* Tipo de pedido bien visible */
    const tipo = TIPOS[d.tipo_solicitud] || 'PEDIDO';
    const titulo = (d.tipo_solicitud === 52 && d.mesa) ? `MESA ${d.mesa}` : tipo;
    t += CENTRO + GRANDE + titulo + NORMAL + '\n' + IZQ;
    t += linea('=');

    t += fila(`PEDIDO #${d.numero_pedido}`, d.turno ? `TURNO ${d.turno}` : '');
    if (d.fecha)  t += `Fecha: ${new Date(d.fecha.replace(' ', 'T')).toLocaleString('es-CO')}\n`;
    if (d.mesero) t += `Mesero: ${d.mesero}\n`;

    /* Cliente: siempre que exista; con teléfono/dirección en domicilio y recoger */
    if (d.cliente) {
      t += linea();
      t += NEGRITA + envolver(`CLIENTE: ${d.cliente}`) + FIN_NEG;
      if (d.celular && d.celular !== '0000') t += `Tel: ${d.celular}\n`;
      if (d.tipo_solicitud === 50) {
        if (d.direccion) t += envolver(`Dir: ${d.direccion}`);
        if (d.barrio)    t += envolver(`Barrio: ${d.barrio}`);
      }
    }

    /* Productos con cantidad, tipo, detalle y valor */
    t += linea();
    t += NEGRITA + fila('CANT  PRODUCTO', 'VALOR') + FIN_NEG;
    t += linea();
    (d.productos || []).forEach(p => {
      const tipoProd = (p.tipo_producto && p.tipo_producto !== 'Único') ? ` (${p.tipo_producto})` : '';
      t += envolver(`${p.cantidad} x ${p.producto || 'Producto'}${tipoProd}`);
      if (p.detalle && p.detalle.trim() && p.detalle !== 'Sin detalle') {
        t += envolver(`> ${p.detalle.trim()}`, '    ');
      }
      t += fila('', $$$(p.subtotal));
    });

    /* Totales */
    t += linea();
    if (d.costo_domicilio > 0) {
      t += fila('Subtotal:', $$$(d.subtotal));
      t += fila('Domicilio:', $$$(d.costo_domicilio));
    }
    t += CENTRO + NEGRITA + GRANDE + `TOTAL: ${$$$(d.total)}` + NORMAL + FIN_NEG + '\n' + IZQ;
    if (d.pagado) t += CENTRO + `** PAGADO${d.metodo_pago ? ' - ' + d.metodo_pago : ''} **\n` + IZQ;

    /* Comentario */
    if (d.comentario && d.comentario.trim()) {
      t += linea();
      t += NEGRITA + 'NOTA:\n' + FIN_NEG;
      t += envolver(d.comentario.trim());
    }

    t += linea('=');
    t += CENTRO + 'Gracias por su compra!\n' + IZQ;
    t += '\n\n\n\n';
    t += GS + '\x56\x00';                                  // corte de papel
    t += ESC + '\x70\x00\x19\xFA';                         // abrir cajón
    return t;
  }

  /* ---------- Envío a la impresora ---------- */
  function imprimirContenido(contenido) {
    return qzConexion()
      .then(() => qz.printers.getDefault().catch(() => null))
      .then(impresora => impresora || qz.printers.find().then(l => Array.isArray(l) ? l[0] : l))
      .then(impresora => {
        if (!impresora) throw new Error('No hay impresoras disponibles en este computador.');
        const config = qz.configs.create(impresora);
        return qz.print(config, [{ type: 'raw', format: 'plain', data: contenido }]);
      });
  }

  /* ---------- Función pública: reemplaza a las versiones viejas ---------- */
  window.printInvoicepc = function (numeroPedido) {
    console.log(`🖨 Imprimiendo pedido #${numeroPedido} con datos de la base de datos...`);
    fetch(`../controllers/imprimir_datos.php?numero_pedido=${numeroPedido}`)
      .then(r => r.json())
      .then(d => {
        if (!d.success) throw new Error(d.error || 'No se pudieron obtener los datos del pedido');
        return imprimirContenido(armarTicket(d));
      })
      .then(() => console.log('✅ Ticket impreso.'))
      .catch(err => {
        console.error('❌ Error al imprimir:', err);
        alert('No se pudo imprimir el pedido #' + numeroPedido + ':\n' + (err.message || err));
      });
  };

  /* ---------- Reemplazar TODAS las funciones viejas de impresión ----------
     Las versiones anteriores leían el HTML de la pantalla (fallaban si la
     fila o el modal no estaban pintados) o intentaban usar RawBT desde el
     navegador del PC (RawBT solo existe en Android). Todas imprimen ahora
     con los datos completos de la base de datos. */
  window.printInvoicemesa = window.printInvoicepc;   // modal de mesas
  window.printInvoice     = window.printInvoicepc;   // vista de domicilios

  /* Abrir el cajón de dinero sin imprimir ticket (botón de caja) */
  window.abrirCajon = function () {
    imprimirContenido('\x1B\x70\x00\x19\xFA')
      .then(() => console.log('✅ Cajón abierto.'))
      .catch(err => alert('No se pudo abrir el cajón: ' + (err.message || err)));
  };

  /* Conexión robusta también para el resto de scripts que la usan */
  window.ensureConnection = qzConexion;
})();
