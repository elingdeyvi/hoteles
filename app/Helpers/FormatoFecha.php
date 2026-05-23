<?php

if (! function_exists('fechaLetras'))
{
    function fechaLetras($f)
    {
        if ($f === '0000-00-00 00:00:00') {
            return 'No se asignado fecha';
        }
        setlocale(LC_ALL, "es_ES.utf8", "es_ES", "esp");
        $diassemanaN = array(
            "Domingo", "Lunes", "Martes", "Miércoles",
            "Jueves", "Viernes", "Sábado"
        );
        $mesesN = array(
            1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio",
            "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        );
        $diasemana = date('w', strtotime($f));
        $mes = date('n', strtotime($f));
        // $diasemana = strftime("%A", strtotime($f));
        $diames    = date("d", strtotime($f));
        // $mes       = strftime("%B", strtotime($f));
        $anio      = date("Y", strtotime($f));
        return $diassemanaN[$diasemana] . " {$diames} de " . $mesesN[$mes] . " de {$anio}";
    }
}
