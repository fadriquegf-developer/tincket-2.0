<?php

return [
    'modal' => [
        'instructions' => <<<HTML
<h3 class="mb-3 fw-semibold">Com preparar el CSV d'importació de clients</h3>

<ol class="list-decimal ps-4 space-y-2">
  <li><strong>Format del fitxer</strong><br>
      Tipus: <code>CSV</code> (UTF-8) &nbsp;|&nbsp; Separador: <code>,</code>
  </li>

  <li><strong>Capçaleres obligatòries</strong><br>
      Enganxa aquesta primera línia exactament:<br>
      <code>name,surname,email,phone,mobile_phone,locale,date_birth,dni,province,city,address,postal_code,newsletter</code>
  </li>

  <li><strong>Camps mínims requerits</strong>
      <ul class="list-disc ps-4">
        <li><code>email</code> ha de ser vàlid i únic.</li>
        <li>Cal omplir com a mínim <code>name</code> o <code>surname</code>.</li>
      </ul>
  </li>

  <li><strong>Format de cada columna</strong>
      <table class="table table-sm mt-2">
        <thead class="table-light"><tr><th>Columna</th><th>Exemple / format</th></tr></thead>
        <tbody>
          <tr><td><code>email</code></td><td>usuari@domini.com</td></tr>
          <tr><td><code>date_birth</code></td><td><kbd>dd/mm/yyyy</kbd> → 23/04/1985</td></tr>
          <tr><td><code>newsletter</code></td><td>1, 0, true o false</td></tr>
          <tr><td><code>locale</code></td><td>ca, es, en…</td></tr>
          <tr><td>Numèrics</td><td>Sense separadors (600123456)</td></tr>
        </tbody>
      </table>
  </li>

  <li><strong>Contrasenyes inicials</strong>
      <ul class="list-disc ps-4">
        <li>Si inclous <code>dni</code>, s'usarà com a contrasenya temporal.</li>
        <li>Si està buit, la contrasenya serà <strong>123456</strong>.</li>
      </ul>
  </li>

  <li><strong>Evita errors habituals</strong>
      <ul class="list-disc ps-4">
        <li>No afegeixis ni canviïs columnes.</li>
        <li>Sense salts de línia extres ni cel·les combinades.</li>
        <li>Els emails duplicats/incorrectes s'ometen i queden al log.</li>
      </ul>
  </li>

  <li><strong>Desa i puja</strong><br>
      Desa com “CSV (delimitat per comes) – UTF-8” i puja'l amb <em>Importar clients</em>.
  </li>
</ol>

<p class="mt-3 fw-medium">En acabar veuràs un resum amb els clients creats i descartats.</p>
HTML
    ],
];
