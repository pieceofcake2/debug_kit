/**
 * Collapsible lists rendered by HtmlToolbarHelper::makeNeatArray().
 *
 * Ported from the DebugKit 2.x toolbar (DEBUGKIT.toolbar.makeNeatArray) by Piece of Cake 2.
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
  const init = () => {
    const lists = $('.c-panel-content-container__content .neat-array.depth-0');
    lists.find('ul').each(function collapse() {
      const list = $(this);
      list.parent().addClass('expandable');
      if (list.hasClass('expanded')) {
        list.parent().addClass('expanded');
      } else {
        list.hide().parent().addClass('collapsed');
      }
    });
  };

  const onEvent = () => {
    $(document).on('click', '.neat-array li.expandable', function toggle(event) {
      event.stopPropagation();
      $(this).toggleClass('expanded collapsed').children('ul').toggle();
    });
    document.addEventListener('initPanel', init);
  };

  return {
    onEvent,
  };
})(jQuery);
