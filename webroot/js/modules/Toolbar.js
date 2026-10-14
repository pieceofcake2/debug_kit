/**
 * Toolbar.
 *
 * Copied from cakephp/debug_kit 5.x (webroot/js/modules/Toolbar.js) and adapted for
 * CakePHP 2 by Piece of Cake 2: panels are addressed by request id and panel name.
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
export default class Toolbar {
  constructor(options) {
    this.$body = options.body;
    this.$container = options.container;
    this.$toggleBtn = options.toggleBtn;
    this.$panelButtons = options.panelButtons;
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

    const self = this;
    window.addEventListener('message', (event) => {
      self.onMessage(event);
    }, false);
  }

  toggle() {
    const state = this.nextState();
    this.updateButtons(state);
    this.updateToolbarState(state);
    window.parent.postMessage(state, window.location.origin);
  }

  hide() {
    this.$container.removeClass('is-active');
    this.currentPanelButton().removeClass('is-active');
    const that = this;

    // Shrink the iframe back to 40px height
    setTimeout(() => {
      window.parent.postMessage(that.state(), window.location.origin);
    }, 250);
  }

  isExpanded() {
    return this.$container.hasClass('is-active');
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
      this.$body.addClass('is-active');
    }
    if (state === 'collapse') {
      this.$body.removeClass('is-active');
    }
  }

  updateButtons(state) {
    if (state === 'toolbar') {
      this.$panelButtons.removeClass('is-hidden');
    }
    if (state === 'collapse') {
      this.$panelButtons.addClass('is-hidden');
    }
  }

  // ========== PANELS ==========

  currentPanelButton() {
    return this.$panelButtons.filter((i, el) => el.getAttribute('data-panel') === this.currentPanelName);
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
    const contentArea = this.$container.find('.c-panel-content-container__content');
    const that = this;
    const loader = $('.o-loader');

    const timer = setTimeout(() => {
      loader.addClass('is-loading');
    }, 500);

    this.currentPanelName = name;
    window.parent.postMessage('expand', window.location.origin);

    $.get(url, (response) => {
      clearTimeout(timer);
      loader.removeClass('is-loading');

      // Slide panel into place
      that.$container.addClass('is-active');
      contentArea.html(response);

      // This initializes the panel specific JS logic (if there is any)
      document.dispatchEvent(new CustomEvent('initPanel', { detail: `panel${name}` }));
      that.initLegacyModules();
    })
      .fail((response) => {
        clearTimeout(timer);
        contentArea.html(response.responseText);
        $('.o-loader').removeClass('is-loading');
        $('.c-panel-content-container').addClass('is-active');
        window.parent.postMessage('error', window.location.origin);
      });
  }

  // Run the javascript modules registered by panels with DEBUGKIT.loader.register().
  initLegacyModules() {
    if (window.DEBUGKIT && window.DEBUGKIT.loader) {
      window.DEBUGKIT.loader.init();
    }
  }

  // ========== LISTENERS ==========

  mouseListeners() {
    const that = this;

    $(document).on('click', '.js-toolbar-scroll-left', () => {
      that.scroll('left');
    });
    $(document).on('click', '.js-toolbar-scroll-right', () => {
      that.scroll('right');
    });
    $(document).on('click', '.js-toolbar-load-panel', function onLoadPanel() {
      that.loadPanel($(this).attr('data-panel'), $(this).attr('data-request'));
    });
    $(document).on('click', '.js-panel-close', () => {
      that.hide();
    });

    this.$panelButtons.on('click', function onClick(e) {
      that.$panelButtons.removeClass('is-active');
      e.preventDefault();
      e.stopPropagation();
      const name = $(this).attr('data-panel');
      const samePanel = that.currentPanel() === name;

      // If the current panel is open and its tab has been clicked => close it
      if (that.isExpanded() && samePanel) {
        that.hide();
        return false;
      }

      $(this).addClass('is-active');
      that.loadPanel(name);
      return true;
    });

    this.$toggleBtn.on('click', () => {
      that.toggle();
    });
  }

  scroll(direction) {
    const scrollValue = 300;
    const operator = direction === 'left' ? '-=' : '+=';
    const buttons = this.$panelButtons;
    const firstButton = buttons.first();
    const lastButton = buttons.last();

    // If the toolbar is scrolled to the left, don't go farther.
    if (direction === 'right' && firstButton.position().left === 0) {
      return;
    }

    const buttonWidth = lastButton.width();
    // If the last button's right side is left of the cake button, don't scroll further.
    if (direction === 'left' && lastButton.offset().left + buttonWidth < this.$toggleBtn.offset().left) {
      return;
    }
    const css = { left: operator + scrollValue };
    $(this.$panelButtons).animate(css);
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
    this.$panelButtons
      .filter((i, el) => el.getAttribute('data-panel') === 'history')
      .find('.c-panel__summary')
      .text(`${this.ajaxRequests.length} xhr`);
  }
}
