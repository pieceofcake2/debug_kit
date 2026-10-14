/**
 * Toolbar.
 *
 * Adapted from cakephp/debug_kit 5.x (webroot/js/modules/Toolbar.js) for CakePHP 2 by
 * Piece of Cake 2: panels are addressed by request id and panel name.
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

/**
 * Runs the scripts of HTML inserted with innerHTML, in document order.
 *
 * @param {Element} container The element the HTML was inserted into
 * @returns {Promise<void>}
 */
const runScripts = async (container) => {
  const executable = /^$|^module$|javascript|ecmascript/i;
  const scripts = Array.from(container.querySelectorAll('script'))
    .filter((script) => executable.test((script.getAttribute('type') || '').trim()));
  for (const old of scripts) {
    const script = document.createElement('script');
    for (const attribute of Array.from(old.attributes)) {
      script.setAttribute(attribute.name, attribute.value);
    }
    script.text = old.text;
    const loaded = script.src
      ? new Promise((resolve) => {
        script.addEventListener('load', resolve);
        script.addEventListener('error', resolve);
      })
      : null;
    old.replaceWith(script);
    if (loaded) {
      // eslint-disable-next-line no-await-in-loop
      await loaded;
    }
  }
};

// The width of the content box, excluding the padding.
const contentWidth = (el) => {
  const style = window.getComputedStyle(el);
  return el.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight);
};

export default class Toolbar {
  constructor(options) {
    this.body = options.body;
    this.container = options.container;
    this.toggleBtn = options.toggleBtn;
    this.panelButtons = options.panelButtons;
    this.currentRequest = options.currentRequest;
    this.originalRequest = options.originalRequest;
    this.baseUrl = options.baseUrl;
    this.panelUrl = options.panelUrl;
    this.panelsUrl = options.panelsUrl;
    this.isLocalStorageAvailable = options.isLocalStorageAvailable;

    this.currentPanelName = null;
    this.currentState = 0;
    this.states = [
      'collapse',
      'toolbar',
    ];

    this.ajaxRequests = [];
  }

  initialize() {
    this.mouseListeners();
    this.loadState();

    window.addEventListener('message', (event) => {
      this.onMessage(event);
    }, false);
  }

  toggle() {
    const state = this.nextState();
    this.updateButtons(state);
    this.updateToolbarState(state);
    window.parent.postMessage(state, window.location.origin);
  }

  hide() {
    this.container.classList.remove('is-active');
    const button = this.currentPanelButton();
    if (button) {
      button.classList.remove('is-active');
    }

    // Shrink the iframe back to 40px height
    setTimeout(() => {
      window.parent.postMessage(this.state(), window.location.origin);
    }, 250);
  }

  isExpanded() {
    return this.container.classList.contains('is-active');
  }

  // ========== STATES ==========

  state() {
    return this.states[this.currentState];
  }

  nextState() {
    this.currentState++;
    if (this.currentState === this.states.length) {
      this.currentState = 0;
    }
    this.saveState();
    return this.state();
  }

  saveState() {
    if (!this.isLocalStorageAvailable) {
      return;
    }
    window.localStorage.setItem('toolbar_state', this.currentState);
  }

  loadState() {
    if (this.isLocalStorageAvailable) {
      let old = window.localStorage.getItem('toolbar_state');
      if (!old) {
        old = '0';
      }
      if (old === '0') {
        this.hide();
      }
      if (old === '1') {
        this.toggle();
      }
    }
  }

  // ========== UPDATE ELEMENTES DEPENDING ON STATE ==========

  updateToolbarState(state) {
    if (state === 'toolbar') {
      this.body.classList.add('is-active');
    }
    if (state === 'collapse') {
      this.body.classList.remove('is-active');
    }
  }

  updateButtons(state) {
    if (state === 'toolbar') {
      this.panelButtons.forEach((button) => button.classList.remove('is-hidden'));
    }
    if (state === 'collapse') {
      this.panelButtons.forEach((button) => button.classList.add('is-hidden'));
    }
  }

  // ========== PANELS ==========

  /**
   * The button of the current panel.
   *
   * @returns {Element|null}
   */
  currentPanelButton() {
    return this.panelButton(this.currentPanelName);
  }

  /**
   * The button of a panel.
   *
   * @param name The panel name
   * @returns {Element|null}
   */
  panelButton(name) {
    return this.panelButtons.find((el) => el.getAttribute('data-panel') === name) || null;
  }

  currentPanel() {
    return this.currentPanelName;
  }

  /**
   * The request a panel is loaded for. The history panel always shows the original request.
   *
   * @param name The panel name
   * @returns {string}
   */
  requestFor(name) {
    return name === 'history' ? this.originalRequest : this.currentRequest;
  }

  panelUrlFor(name, requestId) {
    const id = requestId || this.requestFor(name);
    return `${this.panelUrl}/${encodeURIComponent(id)}/${encodeURIComponent(name)}`;
  }

  /**
   * Responsible for loading a specific panel
   *
   * @param name The panel name, also used for the global `initPanel` custom event
   * @param requestId Optional request id, defaults to the request the panel belongs to
   */
  loadPanel(name, requestId) {
    if (!name) {
      return;
    }

    const url = this.panelUrlFor(name, requestId);
    const contentArea = this.container.querySelector('.c-panel-content-container__content');
    const loaders = document.querySelectorAll('.o-loader');

    const timer = setTimeout(() => {
      loaders.forEach((loader) => loader.classList.add('is-loading'));
    }, 500);
    const done = () => {
      clearTimeout(timer);
      loaders.forEach((loader) => loader.classList.remove('is-loading'));
    };

    this.currentPanelName = name;
    window.parent.postMessage('expand', window.location.origin);

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then((response) => response.text().then((html) => ({ ok: response.ok, html })))
      .catch(() => ({ ok: false, html: '' }))
      .then(async ({ ok, html }) => {
        done();
        if (!ok) {
          contentArea.innerHTML = html;
          this.container.classList.add('is-active');
          window.parent.postMessage('error', window.location.origin);
          return;
        }

        // Slide panel into place
        this.container.classList.add('is-active');
        contentArea.innerHTML = html;
        await runScripts(contentArea);

        // This initializes the panel specific JS logic (if there is any)
        document.dispatchEvent(new CustomEvent('initPanel', { detail: `panel${name}` }));
      });
  }

  // ========== LISTENERS ==========

  mouseListeners() {
    document.addEventListener('click', (event) => {
      const target = event.target instanceof Element ? event.target : null;
      if (!target) {
        return;
      }
      if (target.closest('.js-toolbar-scroll-left')) {
        this.scroll('left');
      }
      if (target.closest('.js-toolbar-scroll-right')) {
        this.scroll('right');
      }
      const loadPanel = target.closest('.js-toolbar-load-panel');
      if (loadPanel) {
        this.loadPanel(loadPanel.getAttribute('data-panel'), loadPanel.getAttribute('data-request'));
      }
      if (target.closest('.js-panel-close')) {
        this.hide();
      }
    });

    this.panelButtons.forEach((button) => {
      button.addEventListener('click', (e) => {
        this.panelButtons.forEach((el) => el.classList.remove('is-active'));
        e.preventDefault();
        e.stopPropagation();
        const name = button.getAttribute('data-panel');
        const samePanel = this.currentPanel() === name;

        // If the current panel is open and its tab has been clicked => close it
        if (this.isExpanded() && samePanel) {
          this.hide();
          return;
        }

        button.classList.add('is-active');
        this.loadPanel(name);
      });
    });

    this.toggleBtn.addEventListener('click', () => {
      this.toggle();
    });
  }

  scroll(direction) {
    const scrollValue = direction === 'left' ? -300 : 300;
    const buttons = this.panelButtons;
    const firstButton = buttons[0];
    const lastButton = buttons[buttons.length - 1];
    if (!firstButton) {
      return;
    }

    // If the toolbar is scrolled to the left, don't go farther.
    const firstLeft = firstButton.offsetLeft - parseFloat(window.getComputedStyle(firstButton).marginLeft);
    if (direction === 'right' && firstLeft === 0) {
      return;
    }

    const buttonWidth = contentWidth(lastButton);
    // If the last button's right side is left of the cake button, don't scroll further.
    if (
      direction === 'left'
      && lastButton.getBoundingClientRect().left + buttonWidth < this.toggleBtn.getBoundingClientRect().left
    ) {
      return;
    }

    buttons.forEach((button) => {
      const from = parseFloat(button.style.left) || 0;
      const to = from + scrollValue;
      button.style.left = `${to}px`;
      button.animate(
        [{ left: `${from}px` }, { left: `${to}px` }],
        { duration: 400, easing: 'ease-in-out' },
      );
    });
  }

  // ========== AJAX related functionality ==========

  onMessage(event) {
    // Only accept messages from the parent window that loaded the toolbar
    // iframe; the toolbar is served same-origin so origin must match too.
    if (event.origin !== window.location.origin) {
      return;
    }
    if (event.source !== window.parent) {
      return;
    }
    if (typeof (event.data) === 'string' && event.data.indexOf('ajax-completed$$') === 0) {
      this.onRequest(JSON.parse(event.data.split('$$')[1]));
    }
  }

  onRequest(request) {
    this.ajaxRequests.push(request);
    const button = this.panelButton('history');
    const summary = button ? button.querySelector('.c-panel__summary') : null;
    if (summary) {
      summary.textContent = `${this.ajaxRequests.length} xhr`;
    }
  }
}
