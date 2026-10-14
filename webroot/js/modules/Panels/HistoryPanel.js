/**
 * History panel.
 *
 * Adapted from cakephp/debug_kit 5.x (webroot/js/modules/Panels/HistoryPanel.js) for CakePHP 2
 * by Piece of Cake 2: panels are matched by name instead of position.
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
  const pad = (value) => String(value).padStart(2, '0');
  const formatDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} `
    + `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;

  const templateHtml = (id) => {
    const template = document.getElementById(id);
    return template ? template.textContent : '';
  };

  const toElement = (html) => {
    const template = document.createElement('template');
    template.innerHTML = html.trim();
    return template.content.firstElementChild;
  };

  const updateButtons = (toolbar, response) => {
    const summaries = {};
    for (let i = 0, len = response.panels.length; i < len; i++) {
      summaries[response.panels[i].panel] = response.panels[i].summary;
    }

    toolbar.panelButtons.forEach((button) => {
      const name = button.getAttribute('data-panel');
      // Don't overwrite the history panel.
      if (name === 'history') {
        return;
      }
      button.classList.toggle('is-unavailable', !(name in summaries));
      let summary = button.querySelector('.c-panel__summary');
      if (!summaries[name]) {
        if (summary) {
          summary.remove();
        }
        return;
      }
      if (!summary) {
        summary = document.createElement('span');
        summary.className = 'c-panel__summary';
        button.appendChild(summary);
      }
      summary.textContent = summaries[name];
    });
  };

  const init = (toolbar) => {
    const historyPanel = document.querySelector('.c-history-panel');
    if (!historyPanel) {
      return;
    }

    if (!historyPanel.querySelector(':scope > ul')) {
      historyPanel.insertAdjacentHTML('beforeend', templateHtml('list-template'));
    }

    const listItem = templateHtml('list-item-template');

    for (let i = 0; i < toolbar.ajaxRequests.length; i++) {
      const element = toolbar.ajaxRequests[i];
      // Request ID is a server-generated UUID; sanitize defensively before
      // substituting into the href/data-request attribute scaffold.
      const safeId = String(element.requestId || '').replace(/[^A-Za-z0-9-]/g, '');
      if (!safeId) {
        continue;
      }

      // Only the {id} placeholder feeds attributes; substitute it now and
      // populate the visible text via textContent so attacker-controlled values
      // (method/URL/Content-Type from cross-origin responses) cannot inject
      // HTML into the toolbar iframe (same-origin with the dev's app).
      const row = toElement(listItem.replace(/\{id\}/g, safeId));
      const first = document.querySelector('.c-history-panel__list li');
      if (!row || !first) {
        continue;
      }
      row.querySelector('.c-history-panel__time').textContent = formatDate(new Date(element.date));
      // bubble[0] is the static "XHR" label; the next three are method/status/type.
      const bubbles = row.querySelectorAll('.c-history-panel__bubble:not(.c-history-panel__xhr)');
      bubbles[0].textContent = String(element.method ?? '');
      bubbles[1].textContent = String(element.status ?? '');
      bubbles[2].textContent = String(element.type ?? '');
      row.querySelector('.c-history-panel__url').textContent = String(element.url ?? '');

      first.after(row);
    }

    const links = Array.from(document.querySelectorAll('.c-history-panel__link'));
    // Highlight the active request via attribute comparison rather than an
    // attribute selector built from a runtime value.
    links
      .filter((link) => link.getAttribute('data-request') === String(toolbar.currentRequest))
      .forEach((link) => link.classList.add('is-active'));

    links.forEach((link) => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        links.forEach((el) => el.classList.remove('is-active'));
        link.classList.add('is-active');

        fetch(link.getAttribute('href'), {
          headers: {
            Accept: 'application/json, text/javascript, */*; q=0.01',
            'X-Requested-With': 'XMLHttpRequest',
          },
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
          })
          .then((response) => {
            const requestId = link.getAttribute('data-request');
            toolbar.currentRequest = requestId;
            document.body.classList.toggle('is-history-mode', requestId !== toolbar.originalRequest);
            updateButtons(toolbar, response);
          })
          .catch(() => {
            // Keep the toolbar as it is when the request can't be loaded.
          });
      });
    });
  };

  const onEvent = (toolbar) => {
    document.addEventListener('initPanel', (e) => {
      if (e.detail === 'panelhistory') {
        init(toolbar);
      }
    });
  };

  return {
    onEvent,
  };
})();
