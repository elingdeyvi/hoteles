<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Support\BrandAssets;

class ConfiguracionEmpresa extends Model
{
    use HasFactory;

    protected $table = 'configuracion_empresa';

    protected $fillable = [
        'property_id',
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
        'logo_path',
        'logo_original_name',
        'logo_mime_type',
        'logo_size_bytes',
        'favicon_path',
        'color_primario',
        'color_secundario',
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
        'icono_mensaje_path',
        'titulo_mensaje',
        'mensaje_descripcion',
        'mensaje_alerta',
        'horarios_lunes_viernes',
        'horarios_sabados',
        'horarios_domingos',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'logo_url',
        'favicon_url',
        'icono_mensaje_url',
        'direccion_completa',
        'tiene_logo',
        'tiene_favicon',
        'logo_tamano_formateado'
    ];

    /**
     * Scope para obtener solo la configuración activa
     */
    public function scopeActiva($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Hotel al que pertenece esta configuración.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Accessor para obtener la URL completa del logo
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo_path && Storage::exists($this->logo_path)) {
            return asset(str_replace('public', 'storage', $this->logo_path));
        }

        $code = $this->relationLoaded('property')
            ? $this->property?->code
            : ($this->property_id ? Property::query()->whereKey($this->property_id)->value('code') : null);

        return BrandAssets::logoUrl($code);
    }

    /**
     * Accessor para obtener la URL completa del favicon
     */
    public function getFaviconUrlAttribute()
    {
        if ($this->favicon_path && Storage::exists($this->favicon_path)) {
            return asset(str_replace('public', 'storage', $this->favicon_path));
        }

        return BrandAssets::faviconUrl();
    }

    /**
     * Accessor para obtener la URL completa del icono del mensaje
     */
    public function getIconoMensajeUrlAttribute()
    {
        if ($this->icono_mensaje_path) {
            return asset(str_replace("public", "storage", $this->icono_mensaje_path));
        }
        return null;
    }

    /**
     * Accessor para obtener el tamano del logo formateado
     */
    public function getLogoTamanoFormateadoAttribute()
    {
        if (!$this->logo_size_bytes) {
            return null;
        }

        $bytes = $this->logo_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Accessor para verificar si tiene logo
     */
    public function getTieneLogoAttribute()
    {
        return !empty($this->logo_path) && Storage::exists($this->logo_path);
    }

    /**
     * Accessor para verificar si tiene favicon
     */
    public function getTieneFaviconAttribute()
    {
        return !empty($this->favicon_path) && Storage::exists($this->favicon_path);
    }

    /**
     * Accessor para dirección completa
     */
    public function getDireccionCompletaAttribute()
    {
        $direccion = $this->direccion;
        if ($this->ciudad) {
            $direccion .= ", {$this->ciudad}";
        }
        if ($this->estado) {
            $direccion .= ", {$this->estado}";
        }
        if ($this->codigo_postal) {
            $direccion .= " CP: {$this->codigo_postal}";
        }
        return $direccion;
    }

    /**
     * Método para eliminar el logo actual
     */
    public function eliminarLogo()
    {
        if ($this->logo_path && Storage::exists($this->logo_path)) {
            Storage::delete($this->logo_path);
        }

        $this->update([
            'logo_path' => null,
            'logo_original_name' => null,
            'logo_mime_type' => null,
            'logo_size_bytes' => null
        ]);
    }

    /**
     * Método para eliminar el favicon actual
     */
    public function eliminarFavicon()
    {
        if ($this->favicon_path && Storage::exists($this->favicon_path)) {
            Storage::delete($this->favicon_path);
        }

        $this->update([
            'favicon_path' => null
        ]);
    }

    /**
     * Método para eliminar el icono del mensaje actual
     */
    public function eliminarIconoMensaje()
    {
        if ($this->icono_mensaje_path && Storage::exists($this->icono_mensaje_path)) {
            Storage::delete($this->icono_mensaje_path);
        }

        $this->update([
            'icono_mensaje_path' => null
        ]);
    }

    /**
     * Método estático para obtener la configuración actual
     */
    public static function obtenerConfiguracion(?int $propertyId = null)
    {
        $propertyId = $propertyId ?? \App\Support\CurrentProperty::id();

        if ($propertyId) {
            return self::with('property')
                ->where('property_id', $propertyId)
                ->activa()
                ->first()
                ?? self::with('property')->where('property_id', $propertyId)->first();
        }

        return self::with('property')->activa()->first();
    }
}
