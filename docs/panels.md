# Panels

## Toolbar Panels

The toolbar is made up of several panels, which are shown by clicking the CakePHP icon
in the bottom right-hand corner of the page. Each panel consists of a panel class and a
view element. Typically a panel collects and displays a single type of information, such
as logs or request information. You can choose which panels are shown and add your own.

### Built-in Panels

- **History**: Lists the previous requests (including redirects and ajax requests) and
  shows their panels in the toolbar, useful when debugging actions with redirects.
- **Session**: Displays the information currently in the session.
- **Request**: Displays information about the current request: GET, POST, Cake
  parameters, current route information, and cookies if `CookieComponent` is in your
  controller's components.
- **Sql Log**: Displays the SQL log for each database connection.
- **Timer**: Displays any timers that were set during the request (see
  `DebugKitDebugger`), the memory used at component callbacks, and the peak memory use.
- **Log**: Displays the log entries written during the request.
- **Variables**: Displays the view variables set in the controller.
- **Environment**: Displays environment variables related to PHP and CakePHP.
- **Include**: Displays the included files, grouped by type.

## Developing Your Own Panels

### Panel Classes

Panel classes are placed in the `Panel` directory inside a `Lib` path. The file name must
match the class name, so `MyCustomPanel` is expected in `src/Lib/Panel/MyCustomPanel.php`
(legacy layout: `app/Lib/Panel/MyCustomPanel.php`). Panels must extend `DebugPanel`:

```php
App::uses('DebugPanel', 'DebugKit.Lib');

class MyCustomPanel extends DebugPanel
{
}
```

See also the example panel in
`tests/test_app/Plugin/DebugkitTestPlugin/Lib/Panel/PluginTestPanel.php`.

Panels can define the `css` and `javascript` properties (arrays) to include additional
CSS or JavaScript in the toolbar. They are loaded into the toolbar iframe, not into your
page:

```php
class MyCustomPanel extends DebugPanel
{
    public $javascript = [
        '/my_plugin/js/custom_panel.js',
    ];
}
```

### Callbacks

Panels have two callbacks that let them inspect the current request:

- `startup(Controller $controller)` is called during the component's `startup()`.
- `beforeRender(Controller $controller)` is called during the component's
  `beforeRender()`. Its return value is passed to the panel element as `$content`, so you
  do not need to set view variables yourself.

```php
/**
 * Grabs the request parameters.
 *
 * @param \Controller $controller The controller.
 * @return array
 */
public function beforeRender(Controller $controller)
{
    return $controller->request->params;
}
```

Panels can also return a short summary, which is shown next to the panel's button in the
toolbar (for example the number of queries of the Sql Log panel):

```php
/**
 * @param mixed $content The content returned by beforeRender().
 * @return string
 */
public function summary($content = null)
{
    return (string)count($content);
}
```

### Panel Elements

Each panel has a view element that renders the content returned by the panel. The element
name is the underscored class name: `SessionPanel` uses `session_panel.ctp`, and
`SqlLogPanel` uses `sql_log_panel.ctp`. Put the elements in your `Elements` directory.

The element is rendered at the end of the request, with the view of the request when the
page is rendered with the HTML toolbar, and the rendered HTML is stored. The toolbar shows
it inside `<div class="panel-content" id="<panel>-tab">`, where `<panel>` is the
underscored panel name. The toolbar does not include jQuery or any other library, so
panel JavaScript must bundle what it needs. Every time a panel has been loaded, an
`initPanel` event is dispatched on `document` with the panel name prefixed with `panel` as
`detail` (for example `panelmy_custom`):

```js
document.addEventListener('initPanel', (event) => {
  if (event.detail !== 'panelmy_custom') {
    return;
  }
  document.querySelectorAll('#my_custom-tab .my-row').forEach((row) => {
    // ...
  });
});
```

`<script>` elements of the panel content are run when the panel is loaded.

Panels pick up their title and element name by convention. To change them, set these
properties:

- `$title`: the title used for the panel's button in the toolbar.
- `$elementName`: the element used to render the panel.

### Panels in Plugins

Panels provided by a plugin work the same way, but must set `$plugin` to the plugin name
so that their elements can be found:

```php
class MyCustomPanel extends DebugPanel
{
    public $plugin = 'MyPlugin';
}
```

Use plugin dot notation to load a plugin panel:

```php
public $components = [
    'DebugKit.Toolbar' => ['panels' => ['MyPlugin.MyCustom']],
];
```

This loads all default panels and the custom panel from `MyPlugin`.
