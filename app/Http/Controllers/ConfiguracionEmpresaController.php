<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEmpresa;
use App\Support\CurrentProperty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionEmpresaController extends Controller
{
    public function index(): Response
    {
        $config = ConfiguracionEmpresa::obtenerConfiguracion();

        return Inertia::render('ConfiguracionEmpresa/Index', [
            'config' => $config,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre_empresa' => ['required', 'string', 'max:255'],
            'nombre_corto' => ['nullable', 'string', 'max:50'],
            'nombre_largo' => ['nullable', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string'],
            'ciudad' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:255'],
            'color_primario' => ['nullable', 'string', 'max:7'],
            'color_secundario' => ['nullable', 'string', 'max:7'],
            'tema_modo' => ['nullable', 'in:claro,oscuro,sistema'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $config = ConfiguracionEmpresa::obtenerConfiguracion()
            ?? new ConfiguracionEmpresa(['property_id' => CurrentProperty::id(), 'activo' => true]);

        $config->fill(collect($data)->except('logo')->all());

        if ($request->hasFile('logo')) {
            $config->logo_path = $request->file('logo')->store('logos', 'public');
            $config->logo_original_name = $request->file('logo')->getClientOriginalName();
            $config->logo_mime_type = $request->file('logo')->getClientMimeType();
            $config->logo_size_bytes = $request->file('logo')->getSize();
        }

        $config->save();

        return back()->with('success', 'Configuración guardada.');
    }
}
