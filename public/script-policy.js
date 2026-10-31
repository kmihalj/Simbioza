/* EN: Delegated confirmations and form changes keep executable code out of HTML attributes.
 * HR: Zajedničke potvrde i promjene obrazaca uklanjaju izvršivi kod iz HTML atributa. */
(() => {
  'use strict';
  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const message = event.submitter?.dataset.hphConfirm || form.dataset.hphConfirm;
    if (message && !window.confirm(message)) {
      event.preventDefault();
      event.stopImmediatePropagation();
    }
  }, true);
  document.addEventListener('change', (event) => {
    const field = event.target;
    if (!(field instanceof HTMLInputElement) || !field.hasAttribute('data-hph-autosubmit')) return;
    field.form?.requestSubmit();
  });
})();
