# Upgrading from 2.2.x

2.3.0 replaces the toolbar but keeps the PHP API. Panel classes extending `DebugPanel`,
their elements, the component settings, `DebugTimer`, `DebugMemory`, `DebugKitDebugger`,
FireCake and the `ToolbarAccess` controller keep working. Behaviour changes:

- The toolbar is shown in an iframe in the bottom right-hand corner instead of being
  rendered into the page, and it is inserted on `Controller.shutdown` instead of
  `View.afterLayout`. Pages rendered without a layout now get the toolbar too, as long as
  they are HTML and have a `</body>` tag.
- `HtmlToolbarHelper::send()` does nothing anymore; ajax and non HTML requests still use
  FirePHP (`FirePhpToolbarHelper`) as before.
- The request history is kept in the request history storage (`tmp/debug_kit.sqlite`)
  and is shared by all clients instead of being kept per user agent in the cache. The
  `History` panel content changed accordingly. The legacy cache state is still written,
  `ToolbarComponent::loadState()` and `ToolbarAccessController::history_state()` are
  deprecated.
- `webroot/js/js_debug_toolbar.js` and `webroot/css/debug_toolbar.css` were removed, the
  toolbar uses `webroot/css/style.css` and `webroot/js/main.js`. The default values of
  `ToolbarComponent::$javascript` and `ToolbarComponent::$css` are now empty arrays; they
  list additional assets loaded into the toolbar iframe.
- The toolbar no longer ships jQuery, nor the `DEBUGKIT` JavaScript API of the 2.x
  toolbar (`DEBUGKIT.module()`, `DEBUGKIT.loader`, `DEBUGKIT.$`, `DEBUGKIT.Util.Cookie`,
  ...). Panel `css`/`javascript` files (`DebugPanel::$css`, `DebugPanel::$javascript`) are
  still loaded into the toolbar iframe, but panel JavaScript must not rely on jQuery or the
  old API: bundle what it needs and use the `initPanel` event (see
  [Panel Elements](panels.md#panel-elements)) instead of `DEBUGKIT.loader.register()`.
- The built-in elements no longer print their own `<h2>` title, the toolbar shows the
  panel title.
- `ToolbarHelper::getQueryLogs()` no longer clears the query log of the connection.
- The `debug_kit` connection is not listed by the Sql Log panel.
