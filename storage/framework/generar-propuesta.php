<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$html = file_get_contents(dirname(__DIR__, 2) . '/resources/views/pdf/propuesta_comercial.blade.php');
$dompdf->loadHtml($html);
$dompdf->setPaper('letter');
$dompdf->render();

$path = dirname(__DIR__, 2) . '/Propuesta-Hotel-Kleos.pdf';
file_put_contents($path, $dompdf->output());
echo $path . ' ' . filesize($path) . ' pages=' . $dompdf->getCanvas()->get_page_count() . PHP_EOL;
