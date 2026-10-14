/**
 * DebugKit toolbar entry point.
 *
 * Adapted from cakephp/debug_kit 5.x (webroot/js/main.js) for CakePHP 2 by Piece of Cake 2.
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
import Start from './modules/Start.js';
import Keyboard from './modules/Keyboard.js';
import NeatArray from './modules/NeatArray.js';
import HistoryPanel from './modules/Panels/HistoryPanel.js';
import SqlLogPanel from './modules/Panels/SqlLogPanel.js';

const init = () => {
  const toolbar = Start.init();
  toolbar.initialize();
  Keyboard.init(toolbar);

  // Init Panels
  NeatArray.onEvent();
  SqlLogPanel.onEvent();

  // Init Panels with a reference to the toolbar
  HistoryPanel.onEvent(toolbar);
};

// Module scripts are deferred, the document may already be parsed.
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
