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
export default (() => {
  const init = () => {
    const lists = document.querySelectorAll('.c-panel-content-container__content .neat-array.depth-0');
    lists.forEach((root) => {
      root.querySelectorAll('ul').forEach((list) => {
        const item = list.parentElement;
        item.classList.add('expandable');
        if (list.classList.contains('expanded')) {
          item.classList.add('expanded');
        } else {
          list.style.display = 'none';
          item.classList.add('collapsed');
        }
      });
    });
  };

  const onEvent = () => {
    document.addEventListener('click', (event) => {
      const item = event.target instanceof Element ? event.target.closest('.neat-array li.expandable') : null;
      if (!item) {
        return;
      }
      event.stopPropagation();
      item.classList.toggle('expanded');
      item.classList.toggle('collapsed');
      Array.from(item.children)
        .filter((child) => child.tagName === 'UL')
        .forEach((list) => {
          list.style.display = list.style.display === 'none' ? '' : 'none';
        });
    });
    document.addEventListener('initPanel', init);
  };

  return {
    onEvent,
  };
})();
