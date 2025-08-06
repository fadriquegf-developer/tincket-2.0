<?php

return [
    'modal' => [
        'instructions' => <<<HTML
<h3 class="mb-3 fw-semibold">Cómo preparar el CSV de importación de clientes</h3>

<ol class="list-decimal ps-4 space-y-2">
  <li><strong>Formato del archivo</strong><br>
      Tipo: <code>CSV</code> (UTF-8) &nbsp;|&nbsp; Separador: <code>,</code>
  </li>

  <li><strong>Encabezados obligatorios</strong><br>
      Copia esta primera línea tal cual:<br>
      <code>name,surname,email,phone,mobile_phone,locale,date_birth,dni,province,city,address,postal_code,newsletter</code>
  </li>

  <li><strong>Campos mínimos requeridos</strong>
      <ul class="list-disc ps-4">
        <li><code>email</code> debe ser válido y único.</li>
        <li>Rellena al menos <code>name</code> o <code>surname</code>.</li>
      </ul>
  </li>

  <li><strong>Formato de cada columna</strong>
      <table class="table table-sm mt-2">
        <thead class="table-light"><tr><th>Columna</th><th>Ejemplo / formato</th></tr></thead>
        <tbody>
          <tr><td><code>email</code></td><td>usuario@dominio.com</td></tr>
          <tr><td><code>date_birth</code></td><td><kbd>dd/mm/yyyy</kbd> → 23/04/1985</td></tr>
          <tr><td><code>newsletter</code></td><td>1, 0, true o false</td></tr>
          <tr><td><code>locale</code></td><td>es, en, ca…</td></tr>
          <tr><td>Números</td><td>Sin separadores (600123456)</td></tr>
        </tbody>
      </table>
  </li>

  <li><strong>Contraseñas iniciales</strong>
      <ul class="list-disc ps-4">
        <li>Si hay valor en <code>dni</code>, se usará como contraseña temporal.</li>
        <li>Si <code>dni</code> está vacío, la contraseña será <strong>123456</strong>.</li>
      </ul>
  </li>

  <li><strong>Evita errores comunes</strong>
      <ul class="list-disc ps-4">
        <li>No añadas ni renombres columnas.</li>
        <li>Sin saltos de línea extra ni celdas combinadas.</li>
        <li>Los emails duplicados/formato inválido se omitirán y quedarán en el log.</li>
      </ul>
  </li>

  <li><strong>Guarda y sube</strong><br>
      Guarda como “CSV (delimitado por comas) – UTF-8” y súbelo con <em>Importar clientes</em>.
  </li>
</ol>

<p class="mt-3 fw-medium">Al finalizar verás un resumen con los clientes creados y descartados.</p>
HTML
    ],
];
