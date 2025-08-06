<?php

return [
  'modal' => [
    'instructions' => <<<HTML
<h3 class="mb-3 fw-semibold">Como preparar o CSV de importación de clientes</h3>

<ol class="list-decimal ps-4 space-y-2">
<li><strong>Formato do ficheiro</strong><br>
    Tipo: <code>CSV</code> (UTF-8) &nbsp;|&nbsp; Separador: <code>,</code>
</li>

<li><strong>Cabezallos obrigatorios</strong><br>
    Copia esta primeira liña tal cal:<br>
    <code>name,surname,email,phone,mobile_phone,locale,date_birth,dni,province,city,address,postal_code,newsletter</code>
</li>

<li><strong>Campos mínimos requiridos</strong>
    <ul class="list-disc ps-4">
      <li><code>email</code> debe ser válido e único.</li>
      <li>Cubre polo menos <code>name</code> ou <code>surname</code>.</li>
    </ul>
</li>

<li><strong>Formato de cada columna</strong>
    <table class="table table-sm mt-2">
      <thead class="table-light"><tr><th>Columna</th><th>Exemplo / formato</th></tr></thead>
      <tbody>
        <tr><td><code>email</code></td><td>usuario@dominio.com</td></tr>
        <tr><td><code>date_birth</code></td><td><kbd>dd/mm/yyyy</kbd> → 23/04/1985</td></tr>
        <tr><td><code>newsletter</code></td><td>1, 0, true ou false</td></tr>
        <tr><td><code>locale</code></td><td>gl, es, en…</td></tr>
        <tr><td>Numéricos</td><td>Sen separadores (600123456)</td></tr>
      </tbody>
    </table>
</li>

<li><strong>Contrasinais iniciais</strong>
    <ul class="list-disc ps-4">
      <li>Se inclúes <code>dni</code>, usarase como contrasinal temporal.</li>
      <li>Se está baleiro, o contrasinal será <strong>123456</strong>.</li>
    </ul>
</li>

<li><strong>Evita erros habituais</strong>
    <ul class="list-disc ps-4">
      <li>Non engadas nin renomes columnas.</li>
      <li>Sen saltos de liña extra nin celas combinadas.</li>
      <li>Os emails duplicados/formato incorrecto omítense e quedan no log.</li>
    </ul>
</li>

<li><strong>Garda e sobe</strong><br>
    Garda como “CSV (delimitado por comas) – UTF-8” e súbeo con <em>Importar clientes</em>.
</li>
</ol>

<p class="mt-3 fw-medium">Ao rematar verás un resumo cos clientes creados e descartados.</p>
HTML
  ],
];
