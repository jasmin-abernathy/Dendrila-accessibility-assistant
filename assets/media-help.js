(function () {
  'use strict';
  // Give a short, plain-language explanation next to the normal WP media fields.
  // Never intercept uploads or overwrite any existing information.
  const entries = [
    {
      selector: '.media-modal .setting[data-setting="alt"], .attachment-details .setting[data-setting="alt"]',
      marker: 'daa-alt-explained',
      message: 'Décrivez ce que l’image apporte à la page. Si elle ne sert qu’à décorer, laissez le texte alternatif vide. Pour valider votre choix, ouvrez « Accessibilité » dans l’administration.'
    },
    {
      selector: '.media-modal .setting[data-setting="caption"]',
      marker: 'daa-caption-explained',
      message: 'La légende est affichée sous l’image. Elle sert à donner du contexte ou un crédit, mais ne remplace pas le texte alternatif.'
    },
    {
      selector: '.media-modal .setting[data-setting="description"]',
      marker: 'daa-description-explained',
      message: 'La description donne des informations supplémentaires. Elle ne remplace pas le texte alternatif.'
    }
  ];
  let scheduled = false;
  function describe() {
    scheduled = false;
    entries.forEach(function (entry) {
      document.querySelectorAll(entry.selector).forEach(function (field) {
        if (field.querySelector('.' + entry.marker)) { return; }
        const p = document.createElement('p');
        p.className = 'description daa-media-help ' + entry.marker;
        p.textContent = entry.message;
        field.appendChild(p);
      });
    });
  }
  function queue() {
    if (scheduled) { return; }
    scheduled = true;
    window.requestAnimationFrame(describe);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      queue();
      new MutationObserver(queue).observe(document.body, {childList: true, subtree: true});
    }, {once: true});
  } else {
    queue();
    new MutationObserver(queue).observe(document.body, {childList: true, subtree: true});
  }
}());
