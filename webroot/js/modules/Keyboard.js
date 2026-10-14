/**
 * Keyboard navigation.
 *
 * Adapted from cakephp/debug_kit 5.x (webroot/js/modules/Keyboard.js) for CakePHP 2 by Piece of Cake 2.
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
  const loadSibling = (toolbar, sibling) => {
    if (!sibling || !sibling.classList.contains('c-panel') || sibling.classList.contains('is-hidden')) {
      return undefined;
    }
    toolbar.panelButtons.forEach((button) => button.classList.remove('is-active'));
    sibling.classList.add('is-active');
    return toolbar.loadPanel(sibling.getAttribute('data-panel'));
  };

  const init = (toolbar) => {
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        // Close active panel
        if (toolbar.isExpanded()) {
          return toolbar.hide();
        }
        // Collapse the toolbar
        if (toolbar.state() === 'toolbar') {
          return toolbar.toggle();
        }
      }
      const current = toolbar.currentPanelButton();
      if (event.key === 'ArrowLeft' && toolbar.isExpanded()) {
        return loadSibling(toolbar, current && current.previousElementSibling);
      }
      if (event.key === 'ArrowRight' && toolbar.isExpanded()) {
        return loadSibling(toolbar, current && current.nextElementSibling);
      }
      return undefined;
    });
  };

  return {
    init,
  };
})();
