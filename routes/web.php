<?php

use App\Http\Controllers\BookingPaymentController;
use App\Http\Controllers\CheckInOutController;
use App\Http\Controllers\ConfiguracionEmpresaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolioController;
use App\Http\Controllers\HotelPlanningController;
use App\Http\Controllers\ImpresionController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\HotelReportController;
use App\Http\Controllers\ReporteInventarioController;
use App\Http\Controllers\ReporteVentaController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\HuespedController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomRateController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::post('/booking/webhooks/stripe', [BookingPaymentController::class, 'stripeWebhook'])
    ->name('booking.stripe-webhook');

Route::middleware('property.context')->prefix('reservar/{property:code}')->name('booking.')->group(function () {
    Route::get('/', [PublicBookingController::class, 'show'])->name('show');
    Route::post('/', [PublicBookingController::class, 'store'])->name('store');
    Route::post('/pago-demo', [PublicBookingController::class, 'demoPay'])->name('demo-pay');
});

Route::middleware(['auth', 'verified', 'property.context'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.ver')
        ->name('dashboard');

    Route::post('/propiedad', [PropertyController::class, 'switch'])->name('propiedad.switch');

    Route::middleware('permission:hotel.configurar')->group(function () {
        Route::get('/hotel/propiedades', [PropertyController::class, 'index'])->name('propiedades.index');
        Route::post('/hotel/propiedades', [PropertyController::class, 'store'])->name('propiedades.store');
        Route::put('/hotel/propiedades/{property}', [PropertyController::class, 'update'])->name('propiedades.update');
        Route::delete('/hotel/propiedades/{property}', [PropertyController::class, 'destroy'])->name('propiedades.destroy');

        Route::get('/hotel/tipos', [RoomTypeController::class, 'index'])->name('tipos.index');
        Route::post('/hotel/tipos', [RoomTypeController::class, 'store'])->name('tipos.store');
        Route::put('/hotel/tipos/{roomType}', [RoomTypeController::class, 'update'])->name('tipos.update');
        Route::delete('/hotel/tipos/{roomType}', [RoomTypeController::class, 'destroy'])->name('tipos.destroy');

        Route::get('/hotel/tarifas', [RoomRateController::class, 'index'])->name('tarifas.index');
        Route::post('/hotel/tarifas', [RoomRateController::class, 'store'])->name('tarifas.store');
        Route::put('/hotel/tarifas/{roomRate}', [RoomRateController::class, 'update'])->name('tarifas.update');
        Route::delete('/hotel/tarifas/{roomRate}', [RoomRateController::class, 'destroy'])->name('tarifas.destroy');
        Route::post('/hotel/tarifas/{roomRate}/temporadas', [RoomRateController::class, 'storeSeason'])->name('tarifas.seasons.store');
        Route::delete('/hotel/tarifas/temporadas/{seasonRate}', [RoomRateController::class, 'destroySeason'])->name('tarifas.seasons.destroy');

        Route::get('/hotel/impresion', [ImpresionController::class, 'index'])->name('impresion.index');
        Route::post('/hotel/impresion/impresoras', [ImpresionController::class, 'storeImpresora'])->name('impresion.impresoras.store');
        Route::put('/hotel/impresion/impresoras/{impresora}', [ImpresionController::class, 'updateImpresora'])->name('impresion.impresoras.update');
        Route::delete('/hotel/impresion/impresoras/{impresora}', [ImpresionController::class, 'destroyImpresora'])->name('impresion.impresoras.destroy');
        Route::post('/hotel/impresion/impresoras/{impresora}/probar', [ImpresionController::class, 'probar'])->name('impresion.probar');
        Route::post('/hotel/impresion/token', [ImpresionController::class, 'crearToken'])->name('impresion.token');

        Route::get('/configuracion-empresa', [ConfiguracionEmpresaController::class, 'index'])->name('configuracion-empresa.index');
        Route::put('/configuracion-empresa', [ConfiguracionEmpresaController::class, 'update'])->name('configuracion-empresa.update');
    });

    Route::get('/hotel/habitaciones', [RoomController::class, 'index'])
        ->middleware('permission:hotel.configurar|recepcion.reservas|housekeeping.gestionar')
        ->name('habitaciones.index');
    Route::post('/hotel/habitaciones', [RoomController::class, 'store'])->middleware('permission:hotel.configurar')->name('habitaciones.store');
    Route::put('/hotel/habitaciones/{room}', [RoomController::class, 'update'])
        ->middleware('permission:hotel.configurar|housekeeping.gestionar')
        ->name('habitaciones.update');
    Route::delete('/hotel/habitaciones/{room}', [RoomController::class, 'destroy'])->middleware('permission:hotel.configurar')->name('habitaciones.destroy');

    Route::middleware('permission:recepcion.huespedes')->group(function () {
        Route::get('/hotel/huespedes', [HuespedController::class, 'index'])->name('huespedes.index');
        Route::put('/hotel/huespedes/{huesped}', [HuespedController::class, 'update'])->name('huespedes.update');
        Route::delete('/hotel/huespedes/{huesped}', [HuespedController::class, 'destroy'])->name('huespedes.destroy');
    });

    // Alta rápida desde reservas (mismo endpoint JSON/Inertia).
    Route::post('/hotel/huespedes', [HuespedController::class, 'store'])
        ->middleware('permission:recepcion.huespedes|recepcion.reservas')
        ->name('huespedes.store');

    Route::middleware('permission:recepcion.reservas')->group(function () {
        Route::get('/hotel/planning', [HotelPlanningController::class, 'index'])->name('planning.index');
        Route::get('/hotel/planning/calendar', [HotelPlanningController::class, 'calendar'])->name('planning.calendar');
        Route::get('/hotel/planning/room-board', [HotelPlanningController::class, 'roomBoard'])->name('planning.room-board');

        Route::get('/hotel/reservas', [ReservationController::class, 'index'])->name('reservas.index');
        Route::post('/hotel/reservas', [ReservationController::class, 'store'])->name('reservas.store');
        Route::put('/hotel/reservas/{reservation}', [ReservationController::class, 'update'])->name('reservas.update');
        Route::post('/hotel/reservas/{reservation}/imprimir', [ImpresionController::class, 'imprimirReserva'])->name('reservas.imprimir');
        Route::post('/hotel/reservas/{reservation}/cancelar', [ReservationController::class, 'cancel'])->name('reservas.cancel');
        Route::post('/hotel/reservas/{reservation}/confirmar', [ReservationController::class, 'confirm'])->name('reservas.confirm');
    });

    Route::get('/hotel/reservas/{reservation}/check-in', [CheckInOutController::class, 'show'])
        ->middleware('permission:recepcion.checkin')
        ->name('reservas.check-in');
    Route::post('/hotel/reservas/{reservation}/check-in', [CheckInOutController::class, 'checkIn'])
        ->middleware('permission:recepcion.checkin')
        ->name('reservas.check-in.store');
    Route::post('/hotel/reservas/{reservation}/check-out', [CheckInOutController::class, 'checkOut'])
        ->middleware('permission:recepcion.checkout')
        ->name('reservas.check-out');

    Route::middleware('permission:facturacion.folios')->group(function () {
        Route::get('/hotel/folios', [FolioController::class, 'index'])->name('folios.index');
        Route::get('/hotel/folios/{folio}', [FolioController::class, 'show'])->name('folios.show');
        Route::post('/hotel/folios/{folio}/cargos', [FolioController::class, 'addCharge'])->name('folios.charges');
        Route::delete('/hotel/folios/{folio}/cargos/{charge}', [FolioController::class, 'destroyCharge'])->name('folios.charges.destroy');
        Route::post('/hotel/folios/{folio}/cerrar', [FolioController::class, 'close'])->name('folios.close');
        Route::get('/hotel/folios/{folio}/factura', [FolioController::class, 'invoicePdf'])->name('folios.invoice');
        Route::post('/hotel/folios/{folio}/imprimir', [ImpresionController::class, 'imprimirFolio'])->name('folios.imprimir');
    });
    Route::post('/hotel/folios/{folio}/pagos', [FolioController::class, 'addPayment'])
        ->middleware('permission:facturacion.pagos')
        ->name('folios.payments');

    Route::get('/hotel/housekeeping', [HousekeepingController::class, 'board'])
        ->middleware('permission:housekeeping.gestionar')
        ->name('housekeeping.index');
    Route::patch('/hotel/housekeeping/habitaciones/{room}', [HousekeepingController::class, 'updateStatus'])
        ->middleware('permission:housekeeping.gestionar')
        ->name('housekeeping.status');

    Route::get('/hotel/reportes', [HotelReportController::class, 'index'])
        ->middleware('permission:reportes.ver')
        ->name('reportes.index');
    Route::get('/hotel/reportes/pos/export', [HotelReportController::class, 'exportPosSales'])
        ->middleware('permission:reportes.ver|pos.catalogo')
        ->name('reportes.pos.export');
    Route::get('/hotel/reportes/ingresos/export', [HotelReportController::class, 'exportRevenue'])
        ->middleware('permission:reportes.ver')
        ->name('reportes.ingresos.export');

    Route::middleware('permission:reportes.ver')->prefix('hotel/reportes/ventas')->name('reportes.ventas.')->group(function () {
        Route::get('/', [ReporteVentaController::class, 'index'])->name('index');
        Route::get('/{tipo}/export', [ReporteVentaController::class, 'export'])->name('export');
        Route::get('/{tipo}', [ReporteVentaController::class, 'show'])->name('show');
    });

    Route::middleware('permission:reportes.ver')->prefix('hotel/reportes/inventario')->name('reportes.inventario.')->group(function () {
        Route::get('/', [ReporteInventarioController::class, 'index'])->name('index');
        Route::get('/generales', [ReporteInventarioController::class, 'generales'])->name('generales');
        Route::get('/por-producto', [ReporteInventarioController::class, 'porProducto'])->name('por-producto');
        Route::get('/por-tipo', [ReporteInventarioController::class, 'porTipo'])->name('por-tipo');
        Route::get('/por-usuario', [ReporteInventarioController::class, 'porUsuario'])->name('por-usuario');
        Route::get('/bajo-stock', [ReporteInventarioController::class, 'bajoStock'])->name('bajo-stock');
        Route::get('/resumen-general', [ReporteInventarioController::class, 'resumenGeneral'])->name('resumen-general');
    });

    Route::get('/hotel/pos', [PosController::class, 'index'])->middleware('permission:pos.vender')->name('pos.index');
    Route::post('/hotel/pos/cargo', [PosController::class, 'chargeToFolio'])->middleware('permission:pos.vender')->name('pos.charge');
    Route::post('/hotel/pos/vender', [PosController::class, 'vender'])->middleware('permission:pos.vender')->name('pos.vender');
    Route::post('/hotel/pos/ventas/{venta}/imprimir', [ImpresionController::class, 'imprimirVenta'])->middleware('permission:pos.vender')->name('pos.ventas.imprimir');

    Route::middleware('permission:caja.operar')->group(function () {
        Route::get('/hotel/caja', [CajaController::class, 'index'])->name('caja.index');
        Route::post('/hotel/caja/apertura', [CajaController::class, 'abrir'])->name('caja.abrir');
        Route::post('/hotel/caja/corte', [CajaController::class, 'cortar'])->name('caja.cortar');
        Route::post('/hotel/caja/movimientos', [CajaController::class, 'movimiento'])->name('caja.movimientos.store');
        Route::delete('/hotel/caja/movimientos/{movimiento}', [CajaController::class, 'eliminarMovimiento'])->name('caja.movimientos.destroy');
        Route::post('/hotel/caja/cortes/{corte}/imprimir', [ImpresionController::class, 'imprimirCorte'])->name('caja.cortes.imprimir');
    });

    Route::middleware('permission:pos.catalogo')->group(function () {
        Route::get('/hotel/pos/catalogo', [PosController::class, 'catalog'])->name('pos.catalog');
        Route::post('/hotel/pos/outlets', [PosController::class, 'storeOutlet'])->name('pos.outlets.store');
        Route::post('/hotel/pos/categorias', [PosController::class, 'storeCategory'])->name('pos.categories.store');
        Route::post('/hotel/pos/productos', [PosController::class, 'storeProduct'])->name('pos.products.store');
        Route::put('/hotel/pos/productos/{posProduct}', [PosController::class, 'updateProduct'])->name('pos.products.update');
        Route::delete('/hotel/pos/outlets/{posOutlet}', [PosController::class, 'destroyOutlet'])->name('pos.outlets.destroy');
        Route::delete('/hotel/pos/categorias/{posCategory}', [PosController::class, 'destroyCategory'])->name('pos.categories.destroy');
        Route::delete('/hotel/pos/productos/{posProduct}', [PosController::class, 'destroyProduct'])->name('pos.products.destroy');

        Route::get('/hotel/inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::post('/hotel/inventario/movimientos', [InventarioController::class, 'store'])->name('inventario.store');
    });

    Route::middleware('permission:administracion.usuarios')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('permission:administracion.roles')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
