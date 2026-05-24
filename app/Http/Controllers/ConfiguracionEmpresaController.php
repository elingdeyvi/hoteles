<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEmpresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ConfiguracionEmpresaController extends Controller
{
    /**
     * Ensure storage directories exist
     */
    private function ensureStorageDirectories()
    {
        $directories = [
            'public/logos',
            'public/favicons'
        ];

        foreach ($directories as $directory) {
            $path = storage_path('app/' . $directory);
            if (!File::exists($path)) {
                try {
                    // Intentar crear el directorio con permisos recursivos
                    File::makeDirectory($path, 0755, true);
                } catch (\Exception $e) {
                    // Si falla, intentar con permisos más permisivos
                    try {
                        @mkdir($path, 0777, true);
                        // Intentar cambiar permisos después de crear
                        @chmod($path, 0777);
                    } catch (\Exception $e2) {
                        // Si aún falla, registrar el error pero continuar
                        Log::warning("No se pudo crear el directorio: {$path}. Error: " . $e2->getMessage());
                    }
                }
            } else {
                // Si el directorio existe, asegurar permisos de escritura
                try {
                    @chmod($path, 0775);
                } catch (\Exception $e) {
                    // Ignorar errores de permisos
                }
            }
        }

        // Asegurar que existe el directorio de imágenes por defecto
        $defaultImagesDir = public_path('images/default');
        if (!File::exists($defaultImagesDir)) {
            try {
                // Primero asegurar que el directorio padre existe
                $imagesDir = public_path('images');
                if (!File::exists($imagesDir)) {
                    @mkdir($imagesDir, 0755, true);
                    @chmod($imagesDir, 0775);
                }
                File::makeDirectory($defaultImagesDir, 0755, true);
                @chmod($defaultImagesDir, 0775);
            } catch (\Exception $e) {
                // Si falla, intentar con permisos más permisivos
                try {
                    @mkdir($defaultImagesDir, 0777, true);
                    @chmod($defaultImagesDir, 0777);
                } catch (\Exception $e2) {
                    Log::warning("No se pudo crear el directorio de imágenes: {$defaultImagesDir}. Error: " . $e2->getMessage());
                }
            }
        }
    }

    /**
     * Display the current configuration.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): JsonResponse
    {
        $configuracion = ConfiguracionEmpresa::obtenerConfiguracion();

        if (!$configuracion) {
            // Si no existe configuración, crear una por defecto
            $configuracion = ConfiguracionEmpresa::create([
                'nombre_empresa' => 'Hotel Demo',
                'nombre_corto' => 'Hotel',
                'nombre_largo' => 'Hotel Demo',
                'rfc' => 'GAGS920325JW5',
                'descripcion' => 'Sistema de gestión hotelera.',
                'telefono' => '993-632-8613',
                'email' => 'recepcion@hotel.demo',
                'direccion' => 'Calle Antonio Reyes Zurita #213, Colonia Carrizal',
                'codigo_postal' => '',
                'ciudad' => '',
                'estado' => '',
                'pais' => 'México',
                'sitio_web' => '',
                'color_primario' => '#1a365d',
                'color_secundario' => '#64748b',
                'ticket_encabezado_nombre' => null,
                'ticket_encabezado_rfc' => null,
                'ticket_encabezado_domicilio' => null,
                'ticket_encabezado_cp_ciudad' => null,
                'ticket_encabezado_telefono' => null,
                'ticket_encabezado_linea_folio' => null,
                'ticket_footer_text' => null,
                'horarios_lunes_viernes' => '6:00 AM - 10:00 PM',
                'horarios_sabados' => '7:00 AM - 8:00 PM',
                'horarios_domingos' => '8:00 AM - 6:00 PM',
                'activo' => true
            ]);
        }

        return response()->json([
            'data' => $configuracion,
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Get public configuration (logo, favicon, basic info) - no auth required.
     *
     * @return \Illuminate\Http\Response
     */
    public function publicConfig(): JsonResponse
    {
        $configuracion = ConfiguracionEmpresa::obtenerConfiguracion();

        if (!$configuracion) {
            // Si no existe configuración, devolver valores por defecto
            return response()->json([
                'data' => [
                    'nombre_empresa' => 'Punto de venta',
                    'nombre_corto' => 'PV',
                    'nombre_largo' => 'POS negocios V.1.0',
                    'logo_url' => null,
                    'favicon_url' => null,
                    'bienvenida' => '¡Bienvenido a Punto de venta!',
                    'titulo_mensaje' => '-',
                    'mensaje_descripcion' => '-',
                    'mensaje_alerta' => '¡Gracias por tu labor!',
                    'icono_mensaje_url' => null,
                    'telefono' => '+1 (555) 123-4567',
                    'email' => 'info@gymdeportivo.com',
                    'direccion' => '123 Calle Principal, Ciudad',
                    'horarios_lunes_viernes' => '6:00 AM - 10:00 PM',
                    'horarios_sabados' => '7:00 AM - 8:00 PM',
                    'horarios_domingos' => '8:00 AM - 6:00 PM',
                ]
            ], JsonResponse::HTTP_OK);
        }

        // Devolver solo los campos públicos necesarios
        $publicData = [
            'nombre_empresa' => $configuracion->nombre_empresa,
            'nombre_corto' => $configuracion->nombre_corto,
            'nombre_largo' => $configuracion->nombre_largo,
            'logo_url' => $configuracion->logo_url,
            'favicon_url' => $configuracion->favicon_url,
            'bienvenida' => $configuracion->bienvenida,
            'titulo_mensaje' => $configuracion->titulo_mensaje,
            'mensaje_descripcion' => $configuracion->mensaje_descripcion,
            'mensaje_alerta' => $configuracion->mensaje_alerta,
            'icono_mensaje_url' => $configuracion->icono_mensaje_url,
            'telefono' => $configuracion->telefono,
            'email' => $configuracion->email,
            'direccion' => $configuracion->direccion,
            'horarios_lunes_viernes' => $configuracion->horarios_lunes_viernes,
            'horarios_sabados' => $configuracion->horarios_sabados,
            'horarios_domingos' => $configuracion->horarios_domingos,
        ];

        return response()->json([
            'data' => $publicData,
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Update the specified configuration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre_empresa' => 'required|string|max:255',
            'nombre_corto' => 'required|string|max:50',
            'nombre_largo' => 'required|string|max:500',
            'rfc' => 'nullable|string|max:13',
            'descripcion' => 'nullable|string|max:1000',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'codigo_postal' => 'nullable|string|max:10',
            'ciudad' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:100',
            'pais' => 'nullable|string|max:100',
            'sitio_web' => 'nullable|url|max:255',
            'terminos_condiciones' => 'nullable|string',
            'ticket_encabezado_nombre' => 'nullable|string|max:255',
            'ticket_encabezado_rfc' => 'nullable|string|max:13',
            'ticket_encabezado_domicilio' => 'nullable|string|max:2000',
            'ticket_encabezado_cp_ciudad' => 'nullable|string|max:255',
            'ticket_encabezado_telefono' => 'nullable|string|max:30',
            'ticket_encabezado_linea_folio' => 'nullable|string|max:580',
            'ticket_footer_text' => 'nullable|string',
            'politica_privacidad' => 'nullable|string',
            'bienvenida' => 'nullable|string|max:255',
            'titulo_mensaje' => 'nullable|string|max:255',
            'mensaje_descripcion' => 'nullable|string',
            'mensaje_alerta' => 'nullable|string',
            'horarios_lunes_viernes' => 'nullable|string|max:100',
            'horarios_sabados' => 'nullable|string|max:100',
            'horarios_domingos' => 'nullable|string|max:100',
        ], [
            'nombre_empresa.required' => 'El nombre de la empresa es obligatorio',
            'nombre_corto.required' => 'El nombre corto es obligatorio',
            'nombre_largo.required' => 'El nombre largo es obligatorio',
            'email.email' => 'El email debe tener un formato válido',
            'sitio_web.url' => 'El sitio web debe ser una URL válida',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $configuracion = ConfiguracionEmpresa::obtenerConfiguracion();

        if (!$configuracion) {
            return response()->json([
                'message' => 'No se encontró la configuración'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $configuracion->update($request->only([
            'nombre_empresa',
            'nombre_corto',
            'nombre_largo',
            'rfc',
            'descripcion',
            'telefono',
            'email',
            'direccion',
            'codigo_postal',
            'ciudad',
            'estado',
            'pais',
            'sitio_web',
            'terminos_condiciones',
            'ticket_encabezado_nombre',
            'ticket_encabezado_rfc',
            'ticket_encabezado_domicilio',
            'ticket_encabezado_cp_ciudad',
            'ticket_encabezado_telefono',
            'ticket_encabezado_linea_folio',
            'ticket_footer_text',
            'politica_privacidad',
            'bienvenida',
            'titulo_mensaje',
            'mensaje_descripcion',
            'mensaje_alerta',
            'horarios_lunes_viernes',
            'horarios_sabados',
            'horarios_domingos',
        ]));

        return response()->json([
            'data' => $configuracion,
            'message' => 'Configuración actualizada correctamente'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Upload logo for the company.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ], [
            'logo.required' => 'El logo es obligatorio',
            'logo.image' => 'El archivo debe ser una imagen',
            'logo.mimes' => 'El archivo debe ser de tipo: jpeg, png, jpg, gif, svg',
            'logo.max' => 'El archivo no debe ser mayor a 2MB'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $configuracion = ConfiguracionEmpresa::obtenerConfiguracion();

        if (!$configuracion) {
            return response()->json([
                'message' => 'No se encontró la configuración'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Asegurar que los directorios existan
        $this->ensureStorageDirectories();

        // Eliminar logo anterior si existe
        if ($configuracion->logo_path) {
            $configuracion->eliminarLogo();
        }

        // Subir nuevo logo
        $file = $request->file('logo');
        $path = $file->store('public/logos');

        $configuracion->update([
            'logo_path' => $path,
            'logo_original_name' => $file->getClientOriginalName(),
            'logo_mime_type' => $file->getMimeType(),
            'logo_size_bytes' => $file->getSize(),
        ]);

        return response()->json([
            'data' => $configuracion,
            'message' => 'Logo actualizado correctamente'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Upload favicon for the company.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadFavicon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'favicon' => 'required|image|mimes:ico,png,jpg,jpeg,gif,svg|max:1024'
        ], [
            'favicon.required' => 'El favicon es obligatorio',
            'favicon.image' => 'El archivo debe ser una imagen',
            'favicon.mimes' => 'El archivo debe ser de tipo: ico, png, jpg, jpeg, gif, svg',
            'favicon.max' => 'El archivo no debe ser mayor a 1MB'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $configuracion = ConfiguracionEmpresa::obtenerConfiguracion();

        if (!$configuracion) {
            return response()->json([
                'message' => 'No se encontró la configuración'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Asegurar que los directorios existan
        $this->ensureStorageDirectories();

        // Eliminar favicon anterior si existe
        if ($configuracion->favicon_path) {
            $configuracion->eliminarFavicon();
        }

        // Subir nuevo favicon
        $file = $request->file('favicon');
        $path = $file->store('public/favicons');

        $configuracion->update([
            'favicon_path' => $path,
        ]);

        return response()->json([
            'data' => $configuracion,
            'message' => 'Favicon actualizado correctamente'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Delete logo.
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteLogo(): JsonResponse
    {
        $configuracion = ConfiguracionEmpresa::obtenerConfiguracion();

        if (!$configuracion) {
            return response()->json([
                'message' => 'No se encontró la configuración'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $configuracion->eliminarLogo();

        return response()->json([
            'data' => $configuracion,
            'message' => 'Logo eliminado correctamente'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Delete favicon.
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteFavicon(): JsonResponse
    {
        $configuracion = ConfiguracionEmpresa::obtenerConfiguracion();

        if (!$configuracion) {
            return response()->json([
                'message' => 'No se encontró la configuración'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $configuracion->eliminarFavicon();

        return response()->json([
            'data' => $configuracion,
            'message' => 'Favicon eliminado correctamente'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Upload icono del mensaje for the company.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadIconoMensaje(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'icono_mensaje' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2024'
        ], [
            'icono_mensaje.required' => 'El icono del mensaje es obligatorio',
            'icono_mensaje.image' => 'El archivo debe ser una imagen',
            'icono_mensaje.mimes' => 'El archivo debe ser de tipo: jpeg, png, jpg, gif, svg',
            'icono_mensaje.max' => 'El archivo no debe ser mayor a 2MB'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $configuracion = ConfiguracionEmpresa::obtenerConfiguracion();

        if (!$configuracion) {
            return response()->json([
                'message' => 'No se encontró la configuración'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Asegurar que los directorios existan
        $this->ensureStorageDirectories();

        // Eliminar icono anterior si existe
        if ($configuracion->icono_mensaje_path) {
            $configuracion->eliminarIconoMensaje();
        }

        // Subir nuevo icono
        $file = $request->file('icono_mensaje');
        $path = $file->store('public/logos');

        $configuracion->update([
            'icono_mensaje_path' => $path,
        ]);

        return response()->json([
            'data' => $configuracion,
            'message' => 'Icono del mensaje actualizado correctamente'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Delete icono del mensaje.
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteIconoMensaje(): JsonResponse
    {
        $configuracion = ConfiguracionEmpresa::obtenerConfiguracion();

        if (!$configuracion) {
            return response()->json([
                'message' => 'No se encontró la configuración'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $configuracion->eliminarIconoMensaje();

        return response()->json([
            'data' => $configuracion,
            'message' => 'Icono del mensaje eliminado correctamente'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Serve favicon dynamically from configuration.
     *
     * @return \Illuminate\Http\Response
     */
    public function serveFavicon()
    {
        // Asegurar que los directorios existan
        $this->ensureStorageDirectories();

        $configuracion = ConfiguracionEmpresa::obtenerConfiguracion();

        if (!$configuracion || !$configuracion->favicon_path || !Storage::exists($configuracion->favicon_path)) {
            // Si no hay favicon configurado, devolver un favicon por defecto
            $defaultFaviconPath = public_path('images/default/favicon.ico');

            if (File::exists($defaultFaviconPath)) {
                return response()->file($defaultFaviconPath, [
                    'Content-Type' => 'image/x-icon',
                    'Cache-Control' => 'public, max-age=3600'
                ]);
            }

            // Si no hay favicon por defecto, devolver 404
            return response('', 404);
        }

        $filePath = Storage::path($configuracion->favicon_path);
        $mimeType = Storage::mimeType($configuracion->favicon_path);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=3600'
        ]);
    }
}
