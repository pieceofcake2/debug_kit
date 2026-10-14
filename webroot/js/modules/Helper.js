/**
 * Helper functions.
 *
 * Copied from cakephp/debug_kit 5.x (webroot/js/modules/Helper.js).
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
  const isLocalStorageAvailable = () => {
    if (!window.localStorage) {
      return false;
    }
    try {
      window.localStorage.setItem('testKey', '1');
      window.localStorage.removeItem('testKey');
      return true;
    } catch (error) {
      return false;
    }
  };

  return {
    isLocalStorageAvailable,
  };
})();
