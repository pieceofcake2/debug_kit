/**
 * Toolbar bootstrap.
 *
 * Adapted from cakephp/debug_kit 5.x (webroot/js/modules/Start.js) for CakePHP 2 by Piece of Cake 2.
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
import Toolbar from './Toolbar.js';
import Helper from './Helper.js';

export default (() => {
  const init = () => {
    const elem = document.getElementById('__debug_kit_app');
    if (elem) {
      window.debugKitId = elem.getAttribute('data-id');
      window.debugKitBaseUrl = elem.getAttribute('data-url');
      window.debugKitWebroot = elem.getAttribute('data-webroot');
      window.debugKitPanelUrl = elem.getAttribute('data-panel-url');
      window.debugKitPanelsUrl = elem.getAttribute('data-panels-url');
    }

    return new Toolbar({
      body: document.body,
      container: document.querySelector('.js-panel-content-container'),
      toggleBtn: document.querySelector('.js-toolbar-toggle'),
      panelButtons: Array.from(document.querySelectorAll('.js-panel-button')),
      currentRequest: window.debugKitId,
      originalRequest: window.debugKitId,
      baseUrl: window.debugKitBaseUrl,
      panelUrl: window.debugKitPanelUrl,
      panelsUrl: window.debugKitPanelsUrl,
      isLocalStorageAvailable: Helper.isLocalStorageAvailable(),
    });
  };

  return {
    init,
  };
})();
