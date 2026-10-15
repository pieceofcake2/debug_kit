# Configuration

Settings are passed in the component declaration like any other component settings:

```php
public $components = [
    'DebugKit.Toolbar' => [/* settings */],
];
```

## Controlling Panels

The `panels` key sets which panels are loaded and in which order:

```php
public $components = [
    'DebugKit.Toolbar' => ['panels' => ['MyCustom', 'timer' => false]],
];
```

This adds your custom panel `MyCustomPanel` and removes the built-in Timer panel.

Other keys are passed to the panels' constructors. For example, the built-in History
panel uses the `history` key to set the number of past requests it lists:

```php
public $components = [
    'DebugKit.Toolbar' => ['history' => 10],
];
```

The `panels` key itself is not passed to the panel constructors.

## forceEnable

`forceEnable` shows the toolbar regardless of `Configure::read('debug')`. This is useful
when profiling an application in production mode:

```php
public $components = [
    'DebugKit.Toolbar' => ['forceEnable' => true],
];
```

## autoRun

`autoRun` controls whether the toolbar runs automatically. Set it to `false` to enable the
toolbar only when the URL has the `?debug=true` query string:

```php
public $components = [
    'DebugKit.Toolbar' => ['autoRun' => false],
];
```

## forceEnable without debug mode

The toolbar endpoints (`/debug_kit/requests/...`, `/debug_kit/panels/...`) are only
available in debug mode. To use `forceEnable` with `debug` set to 0, also write the
setting to `Configure` so that the endpoints are enabled:

```php
Configure::write('DebugKit.forceEnable', true);
```

## Request History Storage

The recorded requests and their rendered panels are stored in a SQLite database,
`TMP/debug_kit.sqlite` (usually `tmp/debug_kit.sqlite`). The `debug_kit` connection is
created with `ConnectionManager::create()` and the tables are created automatically. Only
the latest 20 requests are kept.

When the PDO SQLite driver is not available (or the database cannot be created), the
requests are stored in the `debug_kit` cache configuration instead (see
[Cache Engine](#cache-engine)). When neither is available, the request is not recorded
and the toolbar is not shown.

| Setting | Default | Description |
| --- | --- | --- |
| `requestCount` | `20` | Number of requests to keep. |
| `storage` | `auto` | `auto` uses the database and falls back to the cache, `database` or `cache` force one of them. |
| `connection` | `debug_kit` | Connection name. If your `database.php` defines a connection with this name, it is used (and the tables are created in it) instead of the SQLite file. On MySQL, the panel content column is created as `LONGTEXT`, because `TEXT` holds at most 64 KB. |
| `database` | `TMP . 'debug_kit.sqlite'` | SQLite file used when the connection is created. |

The toolbar endpoints only read `Configure::read('DebugKit')`, so put storage settings
there (the component reads it too) rather than in the component settings:

```php
Configure::write('DebugKit', [
    'requestCount' => 50,
    'database' => TMP . 'debug_kit' . DS . 'requests.sqlite',
]);
```

## Cache Engine

DebugKit uses the File cache engine for its internal caching by default: the legacy
toolbar state (`ToolbarComponent::loadState()`, `ToolbarHelper::readCache()`) and the
request history when the [cache storage](#request-history-storage) is used. Use the
`cache` key to use another engine. Any engine supported by `Cache::config()` works:

```php
public $components = [
    'DebugKit.Toolbar' => [
        'cache' => ['engine' => 'Memcached', 'servers' => ['127.0.0.1:11211']],
    ],
];
```
