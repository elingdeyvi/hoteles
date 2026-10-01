<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Propuesta Hotel Kleos</title>
    <style>
        @page { margin: 36px 40px 48px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a2f42; line-height: 1.45; }
        h1 { font-size: 22px; color: #1a365d; margin: 0 0 4px 0; }
        h2 { font-size: 14px; color: #1a365d; margin: 16px 0 6px 0; border-bottom: 2px solid #1a365d; padding-bottom: 3px; }
        h3 { font-size: 12px; color: #1a365d; margin: 10px 0 4px 0; }
        p { margin: 0 0 8px 0; }
        .sub { color: #64748b; font-size: 10px; }
        .banner { background: #1a365d; color: #fff; padding: 14px 16px; margin: 0 0 14px 0; }
        .banner h1 { color: #fff; }
        .banner .sub { color: #d6e0ea; }
        .price { font-size: 18px; font-weight: bold; color: #1a365d; }
        table { width: 100%; border-collapse: collapse; margin: 6px 0 10px 0; }
        th { background: #1a365d; color: #fff; text-align: left; padding: 6px 8px; font-size: 10px; }
        td { border-bottom: 1px solid #e2e8f0; padding: 6px 8px; vertical-align: top; }
        .box { background: #f4f7fb; border: 1px solid #d6e0ea; padding: 8px 10px; margin: 6px 0 10px 0; }
        .ok { color: #166534; }
        .no { color: #9a3412; }
        .muted { color: #64748b; font-size: 10px; }
        .right { text-align: right; }
        .center { text-align: center; }
        ul { margin: 4px 0 8px 16px; padding: 0; }
        li { margin-bottom: 3px; }
        .break { page-break-before: always; }
        .footer { position: fixed; bottom: -28px; left: 0; right: 0; font-size: 9px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="footer">Propuesta para Hotel Kleos · 29 de septiembre de 2026 · Precios en pesos mexicanos</div>

    <div class="banner">
        <h1>Sistema de recepción para Hotel Kleos</h1>
        <div class="sub">Tres formas de pagarlo. El precio se mide contra la noche de $600 que el hotel ya cobra.</div>
    </div>

    <h2>Los tres precios, de una vez</h2>
    <table>
        <thead>
            <tr>
                <th>Modalidad</th>
                <th>Qué paga el hotel</th>
                <th>En noches de $600</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Renta</strong><br>Dominio y hosting míos</td>
                <td>Sistema <strong>$1,200 al mes</strong><br>más hosting: <strong>$40 al mes</strong> los primeros 6 meses y <strong>$80 al mes</strong> después</td>
                <td>2 noches al mes, más el hosting</td>
            </tr>
            <tr>
                <td><strong>Compra</strong><br>Dominio y hosting del hotel</td>
                <td><strong>$30,000 una sola vez</strong><br>Incluye instalación y 3 meses de soporte<br>El hosting lo paga el hotel: $40 y luego $80</td>
                <td>50 noches</td>
            </tr>
            <tr>
                <td><strong>Local</strong><br>Solo en la computadora del hotel</td>
                <td><strong>$24,000 una sola vez</strong><br>Sin mensualidad y sin hosting</td>
                <td>40 noches</td>
            </tr>
        </tbody>
    </table>
    <p class="muted">Si se requiere factura, el IVA se suma a estos precios. La computadora, el escáner y la impresora térmica no van incluidos: los compra el hotel.</p>

    <h2>Por qué no es caro</h2>
    <p>El hotel ya cobra <strong>$600 por noche</strong>. En el registro del 26, 27 y 28 de septiembre las llegadas anotadas suman <strong>$11,400</strong> en tres días. El sistema no pide una parte de esa venta. Pide el equivalente a unas cuantas noches para dejar de anotar todo en Excel.</p>
    <div class="box">
        <strong>$1,200 al mes son 2 noches.</strong> Si en el mes se dejan de perder 2 noches que hoy no quedan bien anotadas, la renta ya quedó pagada.<br>
        <strong>$30,000 son 50 noches.</strong> Es menos de lo que el registro mostró en dos semanas de llegadas, si el ritmo de esos tres días se mantiene.<br>
        <strong>$24,000 son 40 noches.</strong> Un solo pago, y ahí termina.
    </div>

    <h2>En cuánto tiempo se recupera</h2>
    <p>La fuga es el dinero que sale y no queda escrito: un efectivo que no se anota, una hora de más que no se cobra, una fila de Excel que alguien borra. Con la noche a $600:</p>
    <table>
        <thead>
            <tr>
                <th>Si se deja de perder</th>
                <th>Al mes</th>
                <th>La renta de $1,200</th>
                <th>La compra de $30,000</th>
                <th>El local de $24,000</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1 noche al mes</td>
                <td>$600</td>
                <td>Se cubre la mitad</td>
                <td>50 meses</td>
                <td>40 meses</td>
            </tr>
            <tr>
                <td>2 noches al mes</td>
                <td>$1,200</td>
                <td>Se cubre el mes</td>
                <td>25 meses</td>
                <td>20 meses</td>
            </tr>
            <tr>
                <td>1 noche por semana</td>
                <td>$2,400</td>
                <td>Sobra una noche</td>
                <td>13 meses</td>
                <td>10 meses</td>
            </tr>
        </tbody>
    </table>
    <p>Eso es solo la fuga. También se evita rentar la misma habitación dos veces y discutir un cobro que nadie apuntó. El corte de caja dice cuánto efectivo debe haber. Si no cuadra, se ve el mismo día, no a fin de mes.</p>

    <div class="break">
        <h2>1. Renta</h2>
        <p class="price">$1,240 al mes los primeros 6 meses &nbsp;·&nbsp; $1,280 al mes después</p>
        <p>$1,200 son la renta del sistema. Los $40 y luego los $80 son el hosting de IONOS, el de la imagen: $40 al mes por 6 meses, periodo de 1 año, y después $80 al mes. El dominio va a mi nombre y va incluido: el hotel no compra nada en IONOS.</p>
        <table>
            <tr>
                <th style="width:50%">Ventajas</th>
                <th>Desventajas</th>
            </tr>
            <tr>
                <td class="ok">
                    El hotel empieza sin juntar $24,000 ni $30,000.<br>
                    Yo me encargo del dominio, del servidor y de que el sistema siga en línea.<br>
                    Se puede ver desde la recepción y desde el celular, con internet.<br>
                    La reserva pública puede quedar en internet.<br>
                    Mientras pague la renta, los fallos del sistema se atienden.
                </td>
                <td class="no">
                    Es un pago cada mes. Si deja de pagar, el sistema se apaga.<br>
                    El dominio y el hosting no están a nombre del hotel.<br>
                    Al terminar el contrato se entrega una copia de su base de datos (huéspedes, reservas y cobros). El programa en renta no se queda.
                </td>
            </tr>
        </table>
    </div>

    <div>
        <h2>2. Compra, en el hosting del hotel</h2>
        <p class="price">$30,000 una sola vez</p>
        <p>Se instala una vez en el dominio y el hosting que el hotel pague. Incluye 3 meses de soporte. Del mes 4 en adelante, cada cambio se cotiza y se cobra aparte. El hosting no va en los $30,000: el hotel lo paga directo, con el precio de la imagen.</p>
        <table>
            <tr>
                <th style="width:50%">Ventajas</th>
                <th>Desventajas</th>
            </tr>
            <tr>
                <td class="ok">
                    Un solo pago del sistema. No hay renta mensual del programa.<br>
                    El dominio y el hosting quedan a nombre del hotel.<br>
                    La información es suya y vive en su servidor.<br>
                    Se consulta desde cualquier lugar con internet.<br>
                    Tres meses para corregir el uso del día a día.
                </td>
                <td class="no">
                    Hay que desembolsar $30,000 al inicio.<br>
                    El hotel debe seguir pagando IONOS. Si no paga el hosting, la página se cae.<br>
                    Según la imagen: $40 al mes los primeros 6 meses y $80 al mes después, con periodo de 1 año.<br>
                    Pasados los 3 meses, un cambio nuevo tiene costo.
                </td>
            </tr>
        </table>
    </div>

    <div>
        <h2>3. Local, un solo pago</h2>
        <p class="price">$24,000 una sola vez</p>
        <p>Se instala en la computadora de la recepción. No hay mensualidad, no hay dominio y no hay hosting. Ahí termina el pago. Lo que se pida después de dejarlo funcionando se cotiza aparte.</p>
        <table>
            <tr>
                <th style="width:50%">Ventajas</th>
                <th>Desventajas</th>
            </tr>
            <tr>
                <td class="ok">
                    El precio más bajo de un solo pago: 40 noches.<br>
                    No depende de IONOS ni de un pago mensual.<br>
                    La recepción puede seguir trabajando si se va el internet.<br>
                    La impresora térmica con agente queda en esa misma computadora.
                </td>
                <td class="no">
                    Solo sirve en esa computadora. El dueño no lo ve desde su casa.<br>
                    No hay página de reserva en internet.<br>
                    Si el disco se daña y no hay copia, se pierde el historial. La copia de respaldo queda en manos del hotel.<br>
                    No incluye los 3 meses de soporte de la compra.
                </td>
            </tr>
        </table>
    </div>

    <h2 class="break">Hosting de la imagen (IONOS)</h2>
    <div class="box">
        Precio de la promoción: <strong>$40 al mes por 6 meses</strong>. Ahorro indicado del 33% sobre $90. Periodo de 1 año. <strong>Después, $80 al mes.</strong><br>
        Incluye 100 GB en disco SSD NVMe, acceso estándar al servidor, 1 correo y escáner de malware.
    </div>
    <table>
        <thead>
            <tr><th></th><th>Renta</th><th>Compra</th><th>Local</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Quién paga IONOS</td>
                <td>Va dentro de la mensualidad. El hotel paga $1,240 y luego $1,280.</td>
                <td>El hotel, directo a IONOS: $40 y luego $80.</td>
                <td>Nadie. No usa hosting.</td>
            </tr>
            <tr>
                <td>A nombre de quién</td>
                <td>A mi nombre.</td>
                <td>A nombre del hotel.</td>
                <td>No aplica.</td>
            </tr>
            <tr>
                <td>Costo del hosting en el año</td>
                <td>6 meses × $40 + 6 meses × $80 = <strong>$720</strong>, ya incluido en la renta.</td>
                <td>El mismo <strong>$720</strong> el primer año, pagado por el hotel. Luego $80 × 12 = <strong>$960</strong> al año.</td>
                <td><strong>$0</strong></td>
            </tr>
        </tbody>
    </table>
    <p class="muted">El primer año se cuenta así porque la imagen da $40 solo 6 meses y el periodo es de 1 año: los otros 6 meses ya van a $80. El dominio, en la renta, va incluido. En la compra, el hotel lo paga aparte una vez al año, según el precio que IONOS tenga ese día.</p>

    <h2>Equipo que el hotel compra</h2>
    <p>Ninguna modalidad incluye aparatos. Son precios de referencia en tienda, para que el dueño vea el total real.</p>
    <table>
        <thead>
            <tr><th>Equipo</th><th>Para qué</th><th class="right">Referencia</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Computadora de recepción</td>
                <td>Ahí se abre el sistema. En local, además, ahí vive la información.</td>
                <td class="right">$9,000 a $14,000</td>
            </tr>
            <tr>
                <td>Escáner</td>
                <td>Guardar la identificación del huésped junto a la reserva.</td>
                <td class="right">$1,800 a $3,500</td>
            </tr>
            <tr>
                <td>Impresora térmica de 80 mm</td>
                <td>Ticket de reserva y de folio, sin una hoja tamaño carta.</td>
                <td class="right">$1,500 a $3,000</td>
            </tr>
            <tr>
                <td><strong>Total de equipo</strong></td>
                <td></td>
                <td class="right"><strong>$12,300 a $20,500</strong></td>
            </tr>
        </tbody>
    </table>

    <h2>Impresión: con agente o sin agente</h2>
    <table>
        <thead>
            <tr><th style="width:50%">Con agente</th><th>Sin agente</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    Un programa queda encendido en la computadora de la impresora. Al cobrar o al guardar la reserva, el ticket sale en la térmica sin abrir ventanas.<br><br>
                    <span class="ok">Conviene en recepción: es más rápido y el cajero no elige otra impresora por error.</span><br>
                    <span class="no">Si ese programa está cerrado, no hay impresión automática. El sistema avisa y se puede imprimir por el navegador.</span>
                </td>
                <td>
                    No se instala nada. El navegador abre la ventana de imprimir y la persona elige la impresora.<br><br>
                    <span class="ok">Sirve el primer día y sirve de respaldo cuando el agente está apagado.</span><br>
                    <span class="no">Son más clics. Alguien puede mandar el ticket a la impresora equivocada.</span>
                </td>
            </tr>
        </tbody>
    </table>
    <p>Las dos formas ya están en el sistema. Se puede usar el agente en la recepción y, si se apaga, imprimir desde el navegador. No cambia el precio de la renta, de la compra ni del local.</p>

    <h2>Qué frena la fuga</h2>
    <ul>
        <li>No se registra un cobro ni un consumo de tienda si la caja del turno no está abierta.</li>
        <li>El corte X muestra cómo va el turno y la caja sigue abierta.</li>
        <li>El corte Z pide el efectivo contado, lo compara con lo que debió haber y cierra el turno.</li>
        <li>Cada cobro queda con forma de pago: efectivo, tarjeta, transferencia.</li>
        <li>En Excel una fila se borra y no queda quién la borró. Aquí el movimiento queda.</li>
    </ul>
    <div class="box">
        Con 2 noches al mes que hoy no se anoten bien, la renta se paga sola.<br>
        Con 1 noche a la semana, la compra de $30,000 se recupera en 13 meses y el local de $24,000 en 10 meses.<br>
        El hosting de la imagen, $40 y luego $80, es menos de una noche. No es eso lo que pesa en el precio.
    </div>

    <h2>Qué incluye el sistema</h2>
    <p>Reservas y reserva por internet, planeación, entrada y salida, huéspedes, folios, punto de venta, caja con corte X y Z, limpieza, reportes y ticket. Varios usuarios: administración, recepción, limpieza y caja, cada quien con lo que le toca.</p>
    <p class="muted">La factura del sistema es un PDF interno del folio. No sustituye la factura electrónica del SAT.</p>
</body>
</html>
