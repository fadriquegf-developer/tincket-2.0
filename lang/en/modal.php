<?php

return [
    'modal' => [
        'instructions' => <<<HTML
<h3 class="mb-3 fw-semibold">How to prepare the client import CSV</h3>

<ol class="list-decimal ps-4 space-y-2">
  <li><strong>File format</strong><br>
      Type: <code>CSV</code> (UTF-8) &nbsp;|&nbsp; Separator: <code>,</code>
  </li>

  <li><strong>Required headers</strong><br>
      Paste this first line exactly:<br>
      <code>name,surname,email,phone,mobile_phone,locale,date_birth,dni,province,city,address,postal_code,newsletter</code>
  </li>

  <li><strong>Minimum required fields</strong>
      <ul class="list-disc ps-4">
        <li><code>email</code> must be valid and unique.</li>
        <li>At least <code>name</code> or <code>surname</code> must be filled in.</li>
      </ul>
  </li>

  <li><strong>Format for each column</strong>
      <table class="table table-sm mt-2">
        <thead class="table-light"><tr><th>Column</th><th>Example / format</th></tr></thead>
        <tbody>
          <tr><td><code>email</code></td><td>user@domain.com</td></tr>
          <tr><td><code>date_birth</code></td><td><kbd>dd/mm/yyyy</kbd> → 23/04/1985</td></tr>
          <tr><td><code>newsletter</code></td><td>1, 0, true or false</td></tr>
          <tr><td><code>locale</code></td><td>ca, es, en…</td></tr>
          <tr><td>Numeric</td><td>No separators (600123456)</td></tr>
        </tbody>
      </table>
  </li>

  <li><strong>Initial passwords</strong>
      <ul class="list-disc ps-4">
        <li>If you include <code>dni</code>, it will be used as temporary password.</li>
        <li>If empty, the password will be <strong>123456</strong>.</li>
      </ul>
  </li>

  <li><strong>Avoid common errors</strong>
      <ul class="list-disc ps-4">
        <li>Do not add or change columns.</li>
        <li>No extra line breaks or merged cells.</li>
        <li>Duplicate/incorrect emails are skipped and logged.</li>
      </ul>
  </li>

  <li><strong>Save and upload</strong><br>
      Save as "CSV (comma delimited) – UTF-8" and upload it with <em>Import clients</em>.
  </li>
</ol>

<p class="mt-3 fw-medium">Upon completion you will see a summary with created and discarded clients.</p>
HTML
    ],
];