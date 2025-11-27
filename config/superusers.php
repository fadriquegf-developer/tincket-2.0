<?php

return [
    // Usar variable de entorno para mayor flexibilidad
    'ids' => array_map('intval', explode(',', env('SUPERUSER_IDS', '1'))),
];
