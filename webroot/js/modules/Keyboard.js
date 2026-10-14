/**
 * Keyboard navigation.
 *
 * Copied from cakephp/debug_kit 5.x (webroot/js/modules/Keyboard.js) and adapted for CakePHP 2 by Piece of Cake 2.
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
  const loadSibling = (toolbar, sibling) => {
    if (!sibling.hasClass('c-panel') || sibling.hasClass('is-hidden')) {
      return undefined;
    }
    toolbar.$panelButtons.removeClass('is-active');
    sibling.addClass('is-active');
    return toolbar.loadPanel(sibling.attr('data-panel'));
  };

  const init = (toolbar) => {
    $(document).on('keydown', (event) => {
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
      if (event.key === 'ArrowLeft' && toolbar.isExpanded()) {
        return loadSibling(toolbar, toolbar.currentPanelButton().prev());
      }
      if (event.key === 'ArrowRight' && toolbar.isExpanded()) {
        return loadSibling(toolbar, toolbar.currentPanelButton().next());
      }
      return undefined;
    });
  };

  return {
    init,
  };
})(jQuery);
