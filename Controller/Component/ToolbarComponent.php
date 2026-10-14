<?php
/**
 * DebugKit DebugToolbar Component
 *
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DebugTimer', 'DebugKit.Lib');
App::uses('DebugMemory', 'DebugKit.Lib');
App::uses('DebugKitStorage', 'DebugKit.Lib/Storage');
App::uses('CakeText', 'Utility');
App::uses('View', 'View');

/**
 * Class ToolbarComponent
 *
 * @since         DebugKit 0.1
 */
class ToolbarComponent extends Component implements CakeEventListener
{
    /**
     * Settings for the Component
     *
     * - forceEnable - Force the toolbar to display even if debug == 0. Default = false
     * - autoRun - Automatically display the toolbar. If set to false, toolbar display can be triggered by adding
     *    `?debug=true` to your URL.
     * - history - Number of previous requests listed by the History panel.
     * - requestCount - Number of requests kept in the toolbar storage. Default = 20
     * - storage - `auto` (default), `database` or `cache`. See DebugKitStorage.
     * - connection - Connection name used by the database storage. Default = `debug_kit`
     * - database - SQLite file used when the `debug_kit` connection is created. Default = `TMP/debug_kit.sqlite`
     * - cache - Cache configuration used by the legacy toolbar state and the cache storage.
     *
     * @var array
     */
    public $settings = [
        'forceEnable' => false,
        'autoRun' => true,
    ];

    /**
     * Controller instance reference
     *
     * @var object
     */
    public $controller;

    /**
     * Components used by DebugToolbar
     *
     * @var array
     */
    public $components = ['RequestHandler', 'Session'];

    /**
     * The default panels the toolbar uses.
     * which panels are used can be configured when attaching the component
     *
     * @var array
     */
    protected $_defaultPanels = [
        'DebugKit.History',
        'DebugKit.Session',
        'DebugKit.Request',
        'DebugKit.SqlLog',
        'DebugKit.Timer',
        'DebugKit.Log',
        'DebugKit.Variables',
        'DebugKit.Environment',
        'DebugKit.Include',
    ];

    /**
     * Loaded panel objects.
     *
     * @var array
     */
    public $panels = [];

    /**
     * Additional javascript files loaded into the toolbar, merged with the javascript files of the panels.
     *
     * The toolbar itself is rendered by the DebugKit.Requests controller inside an iframe and
     * loads its own assets.
     *
     * @var array
     */
    public $javascript = [];

    /**
     * Additional CSS files loaded into the toolbar, merged with the CSS files of the panels.
     *
     * @var array
     */
    public $css = [];

    /**
     * CacheKey used for the cache file.
     *
     * @var string
     */
    public $cacheKey = 'toolbar_cache';

    /**
     * Duration of the debug kit history cache
     *
     * @var string
     */
    public $cacheDuration = '+4 hours';

    /**
     * Status whether component is enable or disable
     *
     * @var bool
     */
    public $enabled = true;

    /**
     * Id of the current request in the toolbar storage.
     *
     * @var string|null
     */
    public $requestId = null;

    /**
     * Panel variables gathered for the current request.
     *
     * @var array|null
     */
    protected $_panelVars = null;

    /**
     * The view rendering the current request, if any.
     *
     * @var View|null
     */
    protected $_view = null;

    /**
     * Whether the current request has been recorded.
     *
     * @var bool
     */
    protected $_recorded = false;

    /**
     * The resolved toolbar storage, false when none is available.
     *
     * @var DebugKitStorageInterface|false|null
     */
    protected $_storage = null;

    /**
     * Constructor
     *
     * If debug is off the component will be disabled and not do any further time tracking
     * or load the toolbar helper.
     *
     * @param ComponentCollection $collection The collection.
     * @param array $settings The settings.
     * @return \ToolbarComponent
     */
    public function __construct(ComponentCollection $collection, $settings = [])
    {
        $settings = array_merge((array)Configure::read('DebugKit'), $settings);
        $panels = $this->_defaultPanels;
        if (isset($settings['panels'])) {
            $panels = $this->_makePanelList($settings['panels']);
            unset($settings['panels']);
        }
        $this->controller = $collection->getController();

        parent::__construct($collection, array_merge($this->settings, (array)$settings));

        if (
            !Configure::read('debug') &&
            empty($this->settings['forceEnable'])
        ) {
            $this->enabled = false;

            return;
        }
        if (
            $this->settings['autoRun'] === false &&
            !isset($this->controller->request->query['debug'])
        ) {
            $this->enabled = false;

            return;
        }

        $this->controller->getEventManager()->attach($this);
        $this->requestId = CakeText::uuid();

        DebugMemory::record(__d('debug_kit', 'Component initialization'));

        $this->cacheKey .= $this->Session->read('Config.userAgent');
        if (
            in_array('DebugKit.History', $panels) ||
            (isset($settings['history']) && $settings['history'] !== false)
        ) {
            $this->_createCacheConfig();
        }

        $this->_loadPanels($panels, $settings);
    }

    /**
     * Register all the timing handlers for core events.
     *
     * @return array
     */
    public function implementedEvents()
    {
        $before = (fn($name) => function () use ($name) {
                DebugTimer::start($name, $name);
        });
        $after = (fn($name) => function () use ($name) {
                DebugTimer::stop($name);
        });

        return [
            'Controller.initialize' => [
                ['priority' => 0, 'callable' => $before('Event: Controller.initialize')],
                ['priority' => 999, 'callable' => $after('Event: Controller.initialize')],
            ],
            'Controller.startup' => [
                ['priority' => 0, 'callable' => $before('Event: Controller.startup')],
                ['priority' => 999, 'callable' => $after('Event: Controller.startup')],
            ],
            'Controller.beforeRender' => [
                ['priority' => 0, 'callable' => $before('Event: Controller.beforeRender')],
                ['priority' => 999, 'callable' => $after('Event: Controller.beforeRender')],
            ],
            'Controller.shutdown' => [
                ['priority' => 0, 'callable' => $before('Event: Controller.shutdown')],
                ['priority' => 999, 'callable' => $after('Event: Controller.shutdown')],
            ],
            'View.beforeRender' => [
                ['priority' => 0, 'callable' => $before('Event: View.beforeRender')],
                ['priority' => 0, 'callable' => function (CakeEvent $event) {
                    $view = $event->subject();
                    if ($view instanceof View) {
                        $this->_view = $view;
                    }
                }],
                ['priority' => 999, 'callable' => $after('Event: View.beforeRender')],
            ],
            'View.afterRender' => [
                ['priority' => 0, 'callable' => $before('Event: View.afterRender')],
                ['priority' => 999, 'callable' => $after('Event: View.afterRender')],
            ],
            'View.beforeLayout' => [
                ['priority' => 0, 'callable' => $before('Event: View.beforeLayout')],
                ['priority' => 999, 'callable' => $after('Event: View.beforeLayout')],
            ],
            'View.afterLayout' => [
                ['priority' => 0, 'callable' => $before('Event: View.afterLayout')],
                ['priority' => 999, 'callable' => $after('Event: View.afterLayout')],
            ],
        ];
    }

    /**
     * Initialize callback.
     * If automatically disabled, tell component collection about the state.
     *
     * @param Controller $controller The controller
     * @return void
     */
    public function initialize(Controller $controller)
    {
        if (!$this->enabled) {
            $this->_Collection->disable('Toolbar');
        }
    }

    /**
     * Go through user panels and remove default panels as indicated.
     *
     * @param array $userPanels The list of panels ther user has added removed.
     * @return array Array of panels to use.
     */
    protected function _makePanelList($userPanels)
    {
        $panels = $this->_defaultPanels;
        foreach ($userPanels as $key => $value) {
            if (is_numeric($key)) {
                $panels[] = $value;
            }
            if (is_string($key) && $value === false) {
                $index = array_search($key, $panels);
                if ($index !== false) {
                    unset($panels[$index]);
                }
                // Compatibility for when panels were not
                // required to have a plugin prefix.
                $alternate = 'DebugKit.' . ucfirst($key);
                $index = array_search($alternate, $panels);
                if ($index !== false) {
                    unset($panels[$index]);
                }
            }
        }

        return $panels;
    }

    /**
     * Component Startup
     *
     * @param Controller $controller The controller
     * @return void
     */
    public function startup(Controller $controller)
    {
        $panels = array_keys($this->panels);
        foreach ($panels as $panelName) {
            $this->panels[$panelName]->startup($controller);
        }
        DebugTimer::start(
            'controllerAction',
            __d('debug_kit', 'Controller action'),
        );
        DebugMemory::record(
            __d('debug_kit', 'Controller action start'),
        );
    }

    /**
     * beforeRedirect callback
     *
     * @param Controller $controller The controller
     * @param array|string $url The URL.
     * @param int|null $status The status.
     * @param bool $exit Will the script exit.
     * @return array|null
     */
    public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true)
    {
        if (!class_exists('DebugTimer')) {
            return null;
        }
        DebugTimer::stop('controllerAction');
        DebugTimer::start(
            'processToolbar',
            __d('debug_kit', 'Processing toolbar state'),
        );
        $vars = $this->_gatherVars($controller);
        $this->_saveState($controller, $vars);
        $this->_setPanelVars($vars);
        $this->record($controller, is_numeric($status) ? (int)$status : 302);
        DebugTimer::stop('processToolbar');

        return null;
    }

    /**
     * beforeRender callback
     *
     * Calls beforeRender on all the panels and set the aggregate to the controller.
     *
     * @param Controller $controller The controller.
     * @return void
     */
    public function beforeRender(Controller $controller)
    {
        if (!class_exists('DebugTimer')) {
            return;
        }
        DebugTimer::stop('controllerAction');

        DebugTimer::start(
            'processToolbar',
            __d('debug_kit', 'Processing toolbar data'),
        );
        $vars = $this->_gatherVars($controller);
        $this->_saveState($controller, $vars);
        $vars = $this->_setPanelVars($vars);

        $controller->set([
            'debugToolbarPanels' => $vars,
            'debugToolbarJavascript' => $this->javascript,
            'debugToolbarCss' => $this->css,
        ]);

        $isHtml = (
            !isset($controller->request->params['ext']) ||
            $controller->request->params['ext'] === 'html'
        );

        if (!$controller->request->is('ajax') && $isHtml) {
            $format = 'Html';
        } else {
            $format = 'FirePhp';
        }

        $controller->helpers[] = 'DebugKit.DebugTimer';
        $controller->helpers['DebugKit.Toolbar'] = [
            'output' => sprintf('DebugKit.%sToolbar', $format),
            'cacheKey' => $this->cacheKey,
            'cacheConfig' => 'debug_kit',
            'forceEnable' => $this->settings['forceEnable'],
        ];

        DebugTimer::stop('processToolbar');
        DebugMemory::record(__d('debug_kit', 'Controller render start'));
    }

    /**
     * Shutdown callback
     *
     * Records the request in the toolbar storage and injects the toolbar loader script into
     * HTML responses.
     *
     * @param Controller $controller The controller.
     * @return void
     */
    public function shutdown(Controller $controller)
    {
        if ($this->record($controller)) {
            $this->injectToolbar($controller);
        }
    }

    /**
     * Get the toolbar storage.
     *
     * @return DebugKitStorageInterface|null The storage, or null when none is available.
     */
    public function storage()
    {
        if ($this->_storage === null) {
            $this->_storage = DebugKitStorage::get($this->settings) ?: false;
        }

        return $this->_storage ?: null;
    }

    /**
     * Render the panels of the current request and save them in the toolbar storage.
     *
     * Requests are recorded once. `requestAction()` requests and requests to the DebugKit
     * plugin are not recorded. On success the `X-DEBUGKIT-ID` header is set on the response.
     *
     * @param Controller $controller The controller.
     * @param int|null $statusCode The response status code, defaults to the status of the response.
     * @return bool Whether the request has been recorded.
     */
    public function record(Controller $controller, $statusCode = null)
    {
        if ($this->_recorded || !$this->_shouldRecord($controller)) {
            return false;
        }
        $this->_recorded = true;

        $storage = $this->storage();
        if (!$storage) {
            return false;
        }
        if ($this->_panelVars === null) {
            $this->_setPanelVars($this->_gatherVars($controller));
        }

        $view = $this->_panelView($controller);
        $panels = [];
        foreach ($this->panels as $name => $panel) {
            if (!isset($this->_panelVars[$name])) {
                continue;
            }
            $info = $this->_panelVars[$name];
            $title = empty($info['title']) ? Inflector::humanize($name) : $info['title'];
            $panels[] = [
                'panel' => $name,
                'title' => $title,
                'element' => $info['elementName'],
                'summary' => $this->_panelSummary($panel, $info['content']),
                'content' => $this->_renderPanel($view, $info),
            ];
        }

        $request = $controller->request;
        $requestTime = (float)env('REQUEST_TIME_FLOAT') ?: microtime(true);
        $data = [
            'id' => $this->requestId,
            'url' => $request->here(),
            'method' => $request->method(),
            'status_code' => $statusCode ?: $controller->response->statusCode(),
            'content_type' => $controller->response->type(),
            'requested_at' => date('Y-m-d H:i:s', (int)$requestTime) .
                sprintf('.%06d', ($requestTime - floor($requestTime)) * 1000000),
            'assets' => [
                'css' => array_values(array_filter($this->css)),
                'javascript' => array_values(array_filter($this->javascript)),
            ],
        ];
        if (!$storage->save($data, $panels)) {
            return false;
        }
        $storage->prune(max(DebugKitStorage::requestCount($this->settings), $this->_historyCount() + 1));
        $controller->response->header('X-DEBUGKIT-ID', $this->requestId);

        return true;
    }

    /**
     * Inject the toolbar loader script before the closing body tag of HTML responses.
     *
     * Ajax, requestAction and non HTML responses are left untouched.
     *
     * @param Controller $controller The controller.
     * @return bool Whether the script has been injected.
     */
    public function injectToolbar(Controller $controller)
    {
        $request = $controller->request;
        $response = $controller->response;
        if (
            $request->is('ajax') ||
            $request->is('requested') ||
            (!empty($request->params['ext']) && $request->params['ext'] !== 'html') ||
            $response->type() !== 'text/html'
        ) {
            return false;
        }
        // The body is null when nothing has been rendered.
        $body = (string)$response->body();
        $pos = strripos($body, '</body>');
        if ($pos === false) {
            return false;
        }
        $script = sprintf(
            '<script id="__debug_kit_script" data-id="%s" data-url="%s" data-toolbar-url="%s" type="module" src="%s"></script>',
            h($this->requestId),
            h(Router::url('/', true)),
            h($this->toolbarUrl()),
            h($this->_assetUrl($request, 'js/inject-iframe.js')),
        );
        $response->body(substr($body, 0, $pos) . $script . substr($body, $pos));

        return true;
    }

    /**
     * Get the URL of the toolbar of the current request.
     *
     * @return string
     */
    public function toolbarUrl()
    {
        $url = ['plugin' => 'debug_kit', 'controller' => 'requests', 'action' => 'view', $this->requestId];
        foreach (Router::prefixes() as $prefix) {
            $url[$prefix] = false;
        }

        return Router::url($url);
    }

    /**
     * Set the gathered panel variables of the current request.
     *
     * The javascript and CSS files of the panels are merged into the `javascript` and `css` properties.
     *
     * @param array $vars The gathered variables.
     * @return array The panel variables.
     */
    protected function _setPanelVars(array $vars)
    {
        $this->javascript = array_unique(array_merge($this->javascript, $vars['javascript']));
        $this->css = array_unique(array_merge($this->css, $vars['css']));
        unset($vars['javascript'], $vars['css']);
        $this->_panelVars = $vars;

        return $vars;
    }

    /**
     * Check whether the current request should be recorded.
     *
     * @param Controller $controller The controller.
     * @return bool
     */
    protected function _shouldRecord(Controller $controller)
    {
        $request = $controller->request;
        if (
            !$this->enabled ||
            $this->requestId === null ||
            $request->is('requested')
        ) {
            return false;
        }
        $plugin = $request->params['plugin'] ?? null;

        return !$plugin || Inflector::camelize((string)$plugin) !== 'DebugKit';
    }

    /**
     * Get the number of requests listed by the history panel.
     *
     * @return int
     */
    protected function _historyCount()
    {
        if (isset($this->panels['history']->history)) {
            return (int)$this->panels['history']->history;
        }

        return 0;
    }

    /**
     * Get the view used to render the panels.
     *
     * The view of the current request is used when it renders with the HTML toolbar so that
     * panels can introspect it. Otherwise a separate view is created.
     *
     * @param Controller $controller The controller.
     * @return View
     */
    protected function _panelView(Controller $controller)
    {
        $view = $this->_view;
        if ($view !== null && $view->Helpers->loaded('Toolbar')) {
            $toolbar = $view->Helpers->load('Toolbar');
            if ($toolbar instanceof ToolbarHelper && $toolbar->getName() === 'HtmlToolbar') {
                return $view;
            }
        }

        $view = new View(null);
        $view->request = $controller->request;
        $view->response = $controller->response;
        $view->viewVars = $controller->viewVars;
        $view->helpers = [
            'Html',
            'Form',
            'Number',
            'DebugKit.SimpleGraph',
            'DebugKit.Toolbar' => [
                'output' => 'DebugKit.HtmlToolbar',
                'cacheKey' => $this->cacheKey,
                'cacheConfig' => 'debug_kit',
                'forceEnable' => $this->settings['forceEnable'],
            ],
        ];
        $view->loadHelpers();

        return $view;
    }

    /**
     * Render the element of a panel.
     *
     * @param View $view The view.
     * @param array $info The panel variables.
     * @return string The rendered panel.
     */
    protected function _renderPanel(View $view, array $info)
    {
        $level = ob_get_level();
        try {
            return (string)$view->element($info['elementName'], $info, [
                'plugin' => empty($info['plugin']) ? null : Inflector::camelize($info['plugin']),
            ]);
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            return sprintf(
                '<p class="c-flash c-flash--warning">%s</p>',
                h(__d('debug_kit', 'Could not render the panel: %s', $e->getMessage())),
            );
        }
    }

    /**
     * Get the summary of a panel.
     *
     * @param DebugPanel $panel The panel.
     * @param mixed $content The content returned by the panel's beforeRender().
     * @return string
     */
    protected function _panelSummary(DebugPanel $panel, $content)
    {
        try {
            return (string)$panel->summary($content);
        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Get the URL of a DebugKit asset.
     *
     * @param CakeRequest $request The request.
     * @param string $path The path in the plugin webroot.
     * @return string
     */
    protected function _assetUrl(CakeRequest $request, $path)
    {
        $url = $request->webroot . 'debug_kit/' . $path;
        $file = CakePlugin::path('DebugKit') . 'webroot' . DS . str_replace('/', DS, $path);
        if (is_file($file)) {
            $url .= '?' . filemtime($file);
        }

        return $url;
    }

    /**
     * Load a toolbar state from cache
     *
     * @param string|int $key The key.
     * @return array The toolbar state.
     * @deprecated 2.3.0 The toolbar history is kept in the toolbar storage, see storage().
     *   The legacy cache based state is still written for backwards compatibility.
     */
    public function loadState($key)
    {
        $history = Cache::read($this->cacheKey, 'debug_kit');

        return $history[$key] ?? [];
    }

    /**
     * Create the cache config for the history
     *
     * @return void
     */
    protected function _createCacheConfig()
    {
        if (Configure::read('Cache.disable') === true || Cache::config('debug_kit')) {
            return;
        }
        $cache = [
            'duration' => $this->cacheDuration,
            'engine' => 'File',
            'path' => CACHE,
        ];
        if (isset($this->settings['cache'])) {
            $cache = array_merge($cache, $this->settings['cache']);
        }
        Cache::config('debug_kit', $cache);
    }

    /**
     * Collects the panel contents
     *
     * @param Controller $controller The controller.
     * @return array Array of all panel beforeRender().
     */
    protected function _gatherVars(Controller $controller)
    {
        $vars = ['javascript' => [], 'css' => []];
        $panels = array_keys($this->panels);

        foreach ($panels as $panelName) {
            $panel = $this->panels[$panelName];
            $panelName = Inflector::underscore($panelName);
            $vars[$panelName]['content'] = $panel->beforeRender($controller);
            $elementName = Inflector::underscore($panelName) . '_panel';
            if (isset($panel->elementName)) {
                $elementName = $panel->elementName;
            }
            $vars[$panelName]['elementName'] = $elementName;
            $vars[$panelName]['plugin'] = $panel->plugin;
            $vars[$panelName]['title'] = $panel->title;
            $vars[$panelName]['disableTimer'] = true;

            if (!empty($panel->javascript)) {
                $vars['javascript'] = array_merge($vars['javascript'], (array)$panel->javascript);
            }
            if (!empty($panel->css)) {
                $vars['css'] = array_merge($vars['css'], (array)$panel->css);
            }
        }

        return $vars;
    }

    /**
     * Load Panels used in the debug toolbar
     *
     * @param array $panels The panels.
     * @param array $settings The settings.
     * @return void
     */
    protected function _loadPanels($panels, $settings)
    {
        foreach ($panels as $panel) {
            $className = ucfirst($panel) . 'Panel';
            [$plugin, $className] = pluginSplit($className, true);

            App::uses($className, $plugin . 'Panel');
            if (!class_exists($className)) {
                trigger_error(__d('debug_kit', 'Could not load DebugToolbar panel %s', $panel), E_USER_WARNING);
                continue;
            }
            $panelObj = new $className($settings);
            if ($panelObj instanceof DebugPanel) {
                [, $panel] = pluginSplit($panel);
                $this->panels[Inflector::underscore($panel)] = $panelObj;
            }
        }
    }

    /**
     * Save the current state of the toolbar varibles to the cache file.
     *
     * This legacy state is used by ToolbarComponent::loadState(), ToolbarHelper::readCache() and
     * ToolbarAccessController::history_state(). The toolbar itself uses the toolbar storage.
     *
     * @param Controller $controller Controller instance
     * @param array $vars Vars to save.
     * @return void
     */
    protected function _saveState(Controller $controller, $vars)
    {
        $config = Cache::config('debug_kit');
        if (empty($config) || !isset($this->panels['history'])) {
            return;
        }
        $history = Cache::read($this->cacheKey, 'debug_kit');
        if (empty($history)) {
            $history = [];
        }
        if (count($history) == $this->panels['history']->history) {
            array_pop($history);
        }

        if (isset($vars['variables']['content'])) {
            // Remove unserializable native objects.
            array_walk_recursive($vars['variables']['content'], function (&$item) {
                if (
                    $item instanceof Closure ||
                    $item instanceof PDO ||
                    $item instanceof SimpleXmlElement
                ) {
                    $item = 'Unserializable object - ' . $item::class;
                } elseif ($item instanceof Exception) {
                    $item = sprintf(
                        'Unserializable object - %s. Error: %s in %s, line %s',
                        $item::class,
                        $item->getMessage(),
                        $item->getFile(),
                        $item->getLine(),
                    );
                }

                return $item;
            });
        }
        unset($vars['history']);
        array_unshift($history, $vars);
        Cache::write($this->cacheKey, $history, 'debug_kit');
    }
}
