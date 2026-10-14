/**
 * Backwards compatibility layer for panel javascript written for the DebugKit 2.x toolbar.
 *
 * Panel javascript files (DebugPanel::$javascript) are loaded into the toolbar iframe.
 * Modules registered with DEBUGKIT.loader.register() are initialised every time a panel
 * is loaded. The panel content is wrapped in an element with the id `<panel>-tab`
 * like in the DebugKit 2.x toolbar.
 *
 * Ported from the DebugKit 2.x toolbar (js_debug_toolbar.js) by Piece of Cake 2.
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
/* global jQuery */
window.DEBUGKIT = window.DEBUGKIT || (function legacy($) {
  var cookieLife = 60;

  return {
    $: $,

    module: function module(name) {
      if (this[name] === undefined) {
        this[name] = {};
      }
      return this[name];
    },

    loader: {
      _startup: [],

      register: function register(module) {
        this._startup.push(module);
      },

      init: function init() {
        for (var i = 0; i < this._startup.length; i++) {
          try {
            this._startup[i].init();
          } catch (e) {
            if (window.console) {
              window.console.error(e);
            }
          }
        }
      }
    },

    Util: {
      Cookie: {
        write: function write(name, value) {
          var date = new Date();
          date.setTime(date.getTime() + (cookieLife * 24 * 60 * 60 * 1000));
          document.cookie = name + '=' + value + '; expires=' + date.toUTCString() + '; path=/';
          return true;
        },

        read: function read(name) {
          var prefix = name + '=';
          var cookies = document.cookie.split(';');
          for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i].replace(/^\s+/, '');
            if (cookie.indexOf(prefix) === 0) {
              return cookie.substring(prefix.length);
            }
          }
          return false;
        },

        del: function del(name) {
          document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
        }
      }
    }
  };
}(jQuery));
