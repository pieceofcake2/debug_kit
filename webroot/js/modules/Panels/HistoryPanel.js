/**
 * History panel.
 *
 * Copied from cakephp/debug_kit 5.x (webroot/js/modules/Panels/HistoryPanel.js) and adapted for
 * CakePHP 2 by Piece of Cake 2: panels are matched by name instead of position.
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
export default (($) => {
  const pad = (value) => String(value).padStart(2, '0');
  const formatDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} `
    + `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;

  const init = (toolbar) => {
    const $historyPanel = $('.c-history-panel');
    if (!$historyPanel.length) {
      return;
    }

    if (!$('.c-history-panel > ul').length) {
      $historyPanel.append($('#list-template').html());
    }

    const listItem = $('#list-item-template').html();

    for (let i = 0; i < toolbar.ajaxRequests.length; i++) {
      const element = toolbar.ajaxRequests[i];
      // Request ID is a server-generated UUID; sanitize defensively before
      // substituting into the href/data-request attribute scaffold.
      const safeId = String(element.requestId || '').replace(/[^A-Za-z0-9-]/g, '');
      if (!safeId) {
        continue;
      }

      // Only the {id} placeholder feeds attributes; substitute it now and
      // populate the visible text via .text() so attacker-controlled values
      // (method/URL/Content-Type from cross-origin responses) cannot inject
      // HTML into the toolbar iframe (same-origin with the dev's app).
      const $row = $(listItem.replace(/\{id\}/g, safeId));
      $row.find('.c-history-panel__time').text(formatDate(new Date(element.date)));
      // bubble[0] is the static "XHR" label; the next three are method/status/type.
      const $bubbles = $row.find('.c-history-panel__bubble').not('.c-history-panel__xhr');
      $bubbles.eq(0).text(String(element.method ?? ''));
      $bubbles.eq(1).text(String(element.status ?? ''));
      $bubbles.eq(2).text(String(element.type ?? ''));
      $row.find('.c-history-panel__url').text(String(element.url ?? ''));

      $('.c-history-panel__list li:first').after($row);
    }

    const links = $('.c-history-panel__link');
    // Highlight the active request via attribute comparison rather than an
    // unquoted attribute-selector built from a runtime value.
    links.filter(function highlightActive() {
      return this.getAttribute('data-request') === String(toolbar.currentRequest);
    }).addClass('is-active');

    links.on('click', function historyLinkClick(e) {
      const el = $(this);
      e.preventDefault();
      links.removeClass('is-active');
      el.addClass('is-active');

      $.getJSON(el.attr('href'), (response) => {
        const requestId = el.attr('data-request');
        toolbar.currentRequest = requestId;
        if (requestId === toolbar.originalRequest) {
          $('body').removeClass('is-history-mode');
        } else {
          $('body').addClass('is-history-mode');
        }

        const summaries = {};
        for (let i = 0, len = response.panels.length; i < len; i++) {
          summaries[response.panels[i].panel] = response.panels[i].summary;
        }

        toolbar.$panelButtons.each(function updateButton() {
          const button = $(this);
          const name = button.attr('data-panel');
          // Don't overwrite the history panel.
          if (name === 'history') {
            return;
          }
          button.toggleClass('is-unavailable', !(name in summaries));
          let summary = button.find('.c-panel__summary');
          if (!summaries[name]) {
            summary.remove();
            return;
          }
          if (!summary.length) {
            summary = $('<span class="c-panel__summary">').appendTo(button);
          }
          summary.text(summaries[name]);
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
})(jQuery);
