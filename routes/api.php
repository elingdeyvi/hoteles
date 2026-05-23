<?php

use App\Http\Controllers\BookingPaymentController;
use App\Http\Controllers\CheckInOutController;
use App\Http\Controllers\ConfiguracionEmpresaController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\FolioController;
use App\Http\Controllers\HotelDashboardController;
use App\Http\Controllers\HotelPlanningController;
use App\Http\Controllers\PosCatalogAdminController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\HotelReportController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\HuespedController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomRateController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
| API Hotel — autenticación Sanctum en rutas protegidas.
| Públicas: registro, config empresa, tokens login/reset.
*/

Route::get('/health', HealthController::class);

Route::post('/registro', [UserController::class, 'registro']);
Route::get('/configuracion-empresa/public', [ConfiguracionEmpresaController::class, 'publicConfig']);

Route::prefix('/booking/{property:code}')->middleware(['throttle:public-booking', 'property.context'])->group(function (): void {
    Route::get('/config', [PublicBookingController::class, 'config']);
    Route::get('/room-types', [PublicBookingController::class, 'roomTypes']);
    Route::get('/availability', [PublicBookingController::class, 'availability']);
    Route::post('/reservations', [PublicBookingController::class, 'store']);
    Route::get('/reservations/lookup', [PublicBookingController::class, 'lookup']);
    Route::post('/payments/checkout', [BookingPaymentController::class, 'checkout']);
    Route::post('/payments/demo-confirm', [BookingPaymentController::class, 'demoConfirm']);
});

Route::post('/booking/webhooks/stripe', [BookingPaymentController::class, 'stripeWebhook']);

Route::middleware('dev.setup')->group(function (): void {
    Route::get('/storage-link', function () {
        Artisan::call('storage:link');

        return response()->json(['success' => true, 'output' => Artisan::output()]);
    });
});

Route::prefix('/tokens')->group(function (): void {
    Route::post('/create', [TokenController::class, 'createToken'])->middleware('throttle:login');
    Route::post('/pw', [TokenController::class, 'createResetPW'])->middleware('throttle:login');
    Route::post('/savepw', [TokenController::class, 'saveResetPW']);
    Route::post('/getpwuuid', [TokenController::class, 'getUserUuid']);
});

Route::get('/configuracion-empresa', [ConfiguracionEmpresaController::class, 'index']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('/tokens')->group(function (): void {
        Route::post('/logout', [TokenController::class, 'logout']);
        Route::delete('/{token}', [TokenController::class, 'expire']);
        Route::get('/permissions', function () {
            if (! auth()->check()) {
                return response()->json(['all' => false, 'permissions' => []], 401);
            }
            $user = auth()->user();
            $roles = $user->getRoleNames()->toArray();

            return response()->json([
                'all' => in_array('Administrador', $roles, true),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'roles' => $roles,
            ]);
        });
    });

    Route::prefix('/users')->group(function (): void {
        Route::get('/', fn (Request $request) => $request->user()->load('roles'));
        Route::get('/all', [UserController::class, 'getUsers']);
        Route::get('/{user}', [UserController::class, 'getUser']);
        Route::post('/create', [UserController::class, 'store']);
        Route::put('/{user}/edit', [UserController::class, 'update']);
        Route::put('/{user}/password', [UserController::class, 'updatePassword']);
        Route::put('/perfil', [UserController::class, 'updatePerfil']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });

    Route::prefix('/roles')->group(function (): void {
        Route::get('/all', [RoleController::class, 'getRoles']);
        Route::get('/permissions', [RoleController::class, 'getPermissions']);
        Route::put('/{role}/permissions', [RoleController::class, 'updateRolePermissions']);
    });

    Route::prefix('/configuracion-empresa')->group(function (): void {
        Route::put('/update', [ConfiguracionEmpresaController::class, 'update']);
        Route::post('/upload-logo', [ConfiguracionEmpresaController::class, 'uploadLogo']);
        Route::post('/upload-favicon', [ConfiguracionEmpresaController::class, 'uploadFavicon']);
        Route::delete('/delete-logo', [ConfiguracionEmpresaController::class, 'deleteLogo']);
        Route::delete('/delete-favicon', [ConfiguracionEmpresaController::class, 'deleteFavicon']);
    });

    Route::get('/dashboard/resumen', [HotelDashboardController::class, 'summary'])
        ->middleware('permission:dashboard.ver');

    Route::prefix('/hotel')->middleware('property.context')->group(function (): void {
        Route::get('/properties', [PropertyController::class, 'index'])
            ->middleware('permission:dashboard.ver|hotel.configurar|recepcion.reservas|pos.vender|housekeeping.gestionar');
        Route::get('/properties/manage', [PropertyController::class, 'manage'])
            ->middleware('permission:hotel.configurar');
        Route::post('/properties', [PropertyController::class, 'store'])
            ->middleware('permission:hotel.configurar');
        Route::put('/properties/{property:id}', [PropertyController::class, 'update'])
            ->middleware('permission:hotel.configurar');
        Route::delete('/properties/{property:id}', [PropertyController::class, 'destroy'])
            ->middleware('permission:hotel.configurar');
        Route::get('/room-types', [RoomTypeController::class, 'index'])
            ->middleware('permission:hotel.configurar|recepcion.reservas');
        Route::post('/room-types', [RoomTypeController::class, 'store'])->middleware('permission:hotel.configurar');
        Route::put('/room-types/{roomType}', [RoomTypeController::class, 'update'])->middleware('permission:hotel.configurar');
        Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'destroy'])->middleware('permission:hotel.configurar');

        Route::get('/rooms', [RoomController::class, 'index'])
            ->middleware('permission:hotel.configurar|recepcion.reservas|housekeeping.gestionar');
        Route::post('/rooms', [RoomController::class, 'store'])->middleware('permission:hotel.configurar');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->middleware('permission:hotel.configurar|housekeeping.gestionar');
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->middleware('permission:hotel.configurar');

        Route::get('/rates', [RoomRateController::class, 'index'])->middleware('permission:hotel.configurar|recepcion.reservas');
        Route::post('/rates', [RoomRateController::class, 'store'])->middleware('permission:hotel.configurar');
        Route::put('/rates/{roomRate}', [RoomRateController::class, 'update'])->middleware('permission:hotel.configurar');
        Route::post('/rates/{roomRate}/seasons', [RoomRateController::class, 'storeSeason'])->middleware('permission:hotel.configurar');

        Route::get('/huespedes', [HuespedController::class, 'index'])->middleware('permission:recepcion.huespedes');
        Route::post('/huespedes', [HuespedController::class, 'store'])->middleware('permission:recepcion.huespedes');
        Route::get('/huespedes/{huesped}', [HuespedController::class, 'show'])->middleware('permission:recepcion.huespedes');
        Route::put('/huespedes/{huesped}', [HuespedController::class, 'update'])->middleware('permission:recepcion.huespedes');
        Route::delete('/huespedes/{huesped}', [HuespedController::class, 'destroy'])->middleware('permission:recepcion.huespedes');

        Route::get('/planning/calendar', [HotelPlanningController::class, 'calendar'])
            ->middleware('permission:recepcion.reservas');
        Route::get('/planning/room-board', [HotelPlanningController::class, 'roomBoard'])
            ->middleware('permission:recepcion.reservas');

        Route::get('/reservations/availability', [ReservationController::class, 'availability'])
            ->middleware('permission:recepcion.reservas');
        Route::get('/reservations', [ReservationController::class, 'index'])->middleware('permission:recepcion.reservas');
        Route::post('/reservations', [ReservationController::class, 'store'])->middleware('permission:recepcion.reservas');
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->middleware('permission:recepcion.reservas');
        Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])->middleware('permission:recepcion.reservas');
        Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->middleware('permission:recepcion.reservas');
        Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])->middleware('permission:recepcion.reservas');

        Route::post('/reservations/{reservation}/check-in', [CheckInOutController::class, 'checkIn'])
            ->middleware('permission:recepcion.checkin');
        Route::post('/reservations/{reservation}/check-out', [CheckInOutController::class, 'checkOut'])
            ->middleware('permission:recepcion.checkout');

        Route::get('/folios', [FolioController::class, 'index'])->middleware('permission:facturacion.folios');
        Route::get('/folios/{folio}', [FolioController::class, 'show'])->middleware('permission:facturacion.folios');
        Route::post('/folios/{folio}/charges', [FolioController::class, 'addCharge'])->middleware('permission:facturacion.folios');
        Route::post('/folios/{folio}/payments', [FolioController::class, 'addPayment'])->middleware('permission:facturacion.pagos');
        Route::post('/folios/{folio}/close', [FolioController::class, 'close'])->middleware('permission:facturacion.folios');
        Route::get('/folios/{folio}/invoice-pdf', [FolioController::class, 'invoicePdf'])
            ->middleware('permission:facturacion.folios|facturacion.pagos');

        Route::get('/housekeeping/board', [HousekeepingController::class, 'board'])
            ->middleware('permission:housekeeping.gestionar|dashboard.ver');
        Route::patch('/housekeeping/rooms/{room}/status', [HousekeepingController::class, 'updateStatus'])
            ->middleware('permission:housekeeping.gestionar');

        Route::get('/reports/occupancy', [HotelReportController::class, 'occupancy'])->middleware('permission:reportes.ver');
        Route::get('/reports/revenue', [HotelReportController::class, 'revenue'])->middleware('permission:reportes.ver');
        Route::get('/reports/arrivals-departures', [HotelReportController::class, 'arrivalsDepartures'])
            ->middleware('permission:reportes.ver');
        Route::get('/reports/pos-sales', [HotelReportController::class, 'posSales'])
            ->middleware('permission:reportes.ver|pos.catalogo');
        Route::get('/reports/pos-sales/export', [HotelReportController::class, 'exportPosSales'])
            ->middleware('permission:reportes.ver|pos.catalogo');
        Route::get('/reports/revenue/export', [HotelReportController::class, 'exportRevenue'])
            ->middleware('permission:reportes.ver');

        Route::prefix('/pos')->group(function (): void {
            Route::get('/catalog', [PosController::class, 'catalog'])->middleware('permission:pos.vender|pos.catalogo');
            Route::get('/rooms-in-house', [PosController::class, 'roomsInHouse'])->middleware('permission:pos.vender');
            Route::post('/charge-to-folio', [PosController::class, 'chargeToFolio'])->middleware('permission:pos.vender');

            Route::get('/admin/catalog', [PosCatalogAdminController::class, 'outlets'])->middleware('permission:pos.catalogo');
            Route::post('/admin/outlets', [PosCatalogAdminController::class, 'storeOutlet'])->middleware('permission:pos.catalogo');
            Route::post('/admin/categories', [PosCatalogAdminController::class, 'storeCategory'])->middleware('permission:pos.catalogo');
            Route::post('/admin/products', [PosCatalogAdminController::class, 'storeProduct'])->middleware('permission:pos.catalogo');
            Route::put('/admin/products/{posProduct}', [PosCatalogAdminController::class, 'updateProduct'])->middleware('permission:pos.catalogo');
        });
    });
});
