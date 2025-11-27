(function () {
  function getWrap() {
    return document.querySelector('[bp-field-name="rule_parameters"]');
  }
  function getCheckbox() {
    // El switch crea hidden[name=has_rule] (0) + checkbox[name=has_rule][value=1]
    var scope = document.querySelector('[bp-field-name="has_rule"]');
    if (scope) {
      var cb = scope.querySelector('input[type="checkbox"][name="has_rule"][value="1"]');
      if (cb) return cb;
      cb = scope.querySelector('input[id^="switch_has_rule_"]');
      if (cb) return cb;
      // último recurso: usar el label for
      var lab = scope.querySelector('label[for^="switch_has_rule_"]');
      if (lab) return document.getElementById(lab.getAttribute('for'));
    }
    return document.querySelector('input[type="checkbox"][name="has_rule"][value="1"]');
  }
  function toggleByChecked(checked) {
    var wrap = getWrap();
    if (!wrap) return;
    wrap.style.display = checked ? '' : 'none';
  }
  function sync() {
    var cb = getCheckbox();
    toggleByChecked(!!(cb && cb.checked));
  }
  function bind() {
    var cb = getCheckbox();
    if (cb && !cb.dataset._ruleBound) {
      cb.addEventListener('change', sync);
      cb.dataset._ruleBound = '1';
    }
    sync();
  }

  // inicial + reintentos y observer por si Backpack rehidrata campos
  document.addEventListener('DOMContentLoaded', bind);
  window.addEventListener('load', bind);
  setTimeout(bind, 50);
  setTimeout(bind, 200);
  setTimeout(bind, 600);

  var form = document.querySelector('form');
  if (form && window.MutationObserver) {
    new MutationObserver(bind).observe(form, { childList: true, subtree: true });
  }
})();
