<?php

return [

    /*
    | direct: Laravel imprime en este equipo (misma PC que la impresora).
    | queue:  encola el ticket; el agente local (print-agent/run.php) lo imprime.
    */
    'mode' => env('PRINT_MODE', 'queue'),

    'agent_poll_interval' => (int) env('PRINT_AGENT_POLL_INTERVAL', 3),

    'agent_request_timeout' => (int) env('PRINT_AGENT_REQUEST_TIMEOUT', 30),

    'agent_offline_umbral' => (int) env('PRINT_AGENT_OFFLINE_UMBRAL', 20),

];
