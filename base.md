# el sistema se llama TIBU

- El proyecto es de una lavandería

##objetivo

- Requiero actualizar todo el proyecto adaptarlo a lo siguiente, tienes que considerar la logica de negocio y la logica de programacion, para proponerme un listado de promt en un archivo .md aparte, considera las reglas .cursorrules que esta en la raiz del proyecto, requiero prompts que me ayuden a actualizar el proecto a nivel base de datos, vistas, controladores, rutas, etc, debes considerar las reglas de negocio y las reglas de programacion, y las reglas de seguridad, y las reglas de performance, y las reglas de mantenimiento, y las reglas de optimizacion, y las reglas de scalabilidad, y las reglas de seguridad, y las reglas de performance, y las reglas de mantenimiento, y las reglas de optimizacion, y las reglas de scalabilidad, y las reglas de seguridad, y las reglas de performance, y las reglas de mantenimiento, y las reglas de optimizacion, y las reglas de scalabilidad, backend, frontend, etc. estos prompts deben ser detallados y específicos, para que no haya ambigüedad y se pueda actualizar el proyecto de manera correcta para el mismo cursor


# Tiene estas areas:
- Recepcion (es la recepcion de la ropa es donde se imprime el ticket de venta y se genera los qr para separar la ropa en el patio con el objetivo de que la ropa pueda ir al lavado pesado o al segundo piso esto se define por el tipo de carga.)
- Patio se llamara seleccion (es seleccion o separacion de ropa)
- Lavado pesado se llamara lavado pesado(es lavado de ropa pesada)
- Segundo piso se llamado lavado ligero(es lavado de ropa ligera)
- mismo segundo piso se llamara planchado(es planchado de ropa)
- mismo segundo piso se llamara almacenado(es almacenamiento de ropa)
- tiene para envios(opcion para indicar si este ya enviado o en proceso de envio o entregado, ect)

Requiero la opcion de poder agregar mas areas si es necesario, o quitar areas si es necesario.

Para el caso de recepcion cuando se realice la venta esta se debe generar el mismo qr 2 veces, para al separarla la ropa en el patio  este lleve el mismo qr, por que la ropa puede ir al lavado pesado y al segundo piso esto se define por el tipo de carga.

Requiero que cada una de estas areas sea como un proceso o paso del sistema de lavandería, que se puede ubicar en que etapa del sistema se encuentra la ropa,
ojo desde recepcion  puede estar todo la ropa revuelta pero en la seleccion de patio se estara separado por tipo de carga, para mandarla al lavado pesado o al segundo piso esto se define por el tipo de carga.

Ojo requiero que para cada area debe tener un usuario, para que este este logueado

Todos los usuarios tendra la vista el estara identificada por el rol, Este se podra escanea la ropa por el QR cuando se escanee por primera vez  esto indicara que inicia el proceso del area en el cual se encuentra, al escanear el QR por segunda vez esto indicara que finalizo el proceso del area en el cual se encuentra.

Requiero otra vista el cual indicara el estado del ticket y en areas se encuentra la ropa, puede estar en mas de una area al mismo tiempo.


# configuracion general
- precio por peso de prenda

# tipo de servicio 
- lavado
- planchado

# tipo de carga
- carga pesada
- carga ligera

# tipo de prenda(de finir precio por prenda)
- playera
- calcetines
- pantalones
- camisas
- chamarras
- edredones
- mantas
- toallas
- ropa de cama
- ropa de baño
- ropa de cocina
- ropa de oficina
- ropa de jardin
- ropa de playa
- ropa de invierno
- ropa de verano


#tipo de prioridad 
- urgente
- express
- normal


-- nivel venta

Requiero que a nivel venta se inidique 
- si es envio o  no
- tipo de prioridad
- por prenda selecionada por defecto la el cobro es por tipo de prenda con opcion de cambiar a cobro por peso

- agregar opservacio por tipo de prenda, cuando se imprima el ticket este se mostrara las observaciones aparte en forma de lista

- modulo de clientes

- modulo de ventas historial con opcion de reeimprimir el ticket
- modulo de ventas el cual sera para recepcion 

# aparte del proceso de lavandería

-- inventario esto es aparte del proceso de lavandería

- requiero un inventario por productos
- modulo de productos

- modulo por tipo de producto
- modulo por marca(opcional)
- modulo por categoria(opcional)
- modulo por subcategoria(opcional)
- modulo por proveedor(opcional)

todo estos estaran relacionado al producto 

- modulo de entrada y salida de inventario




