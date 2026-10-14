/**
 * SQL Log panel: runs EXPLAIN for a query.
 *
 * Ported from the DebugKit 2.x toolbar (DEBUGKIT.sqlLog) by Piece of Cake 2.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
export default (() => {
  const showError = (target) => {
    const message = document.createElement('p');
    message.className = 'c-flash c-flash--warning';
    message.textContent = 'Could not fetch EXPLAIN for query.';
    target.replaceChildren(message);
  };

  const onEvent = () => {
    document.addEventListener('click', (event) => {
      const link = event.target instanceof Element ? event.target.closest('#sql_log-tab .sql-explain-link') : null;
      if (!link || !link.form) {
        return;
      }
      event.preventDefault();
      const { form } = link;
      const ds = form.querySelector('input[name*=ds]');
      const dbName = (ds && ds.value) || 'default';
      const target = document.getElementById(`sql-log-explain-${dbName}`);

      fetch(form.action, {
        method: 'POST',
        body: new URLSearchParams(new FormData(form)),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }
          return response.text();
        })
        .then((html) => {
          if (target) {
            target.innerHTML = html;
          }
        })
        .catch(() => {
          if (target) {
            showError(target);
          }
        });
    });
  };

  return {
    onEvent,
  };
})();
