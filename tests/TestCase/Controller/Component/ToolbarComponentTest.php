<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppController', 'Controller');
App::uses('ToolbarComponent', 'DebugKit.Controller/Component');
App::uses('DebugMemory', 'DebugKit.Lib');
App::uses('DebugTimer', 'DebugKit.Lib');
App::uses('DebugKitStorage', 'DebugKit.Lib/Storage');
App::uses('DebugPanel', 'DebugKit.Lib');

/**
 * Class TestToolbarComponent
 *
 * @since         DebugKit 2.1
 */
class TestToolbarComponent extends ToolbarComponent
{
    /**
     * Load Panels of Toolbar
     *
     * @param array $panels The panels to load.
     * @param array $settings The settings.
     * @return void
     */
    public function loadPanels($panels, $settings = []): void
    {
        $this->_loadPanels($panels, $settings);
    }

    /**
     * Set the storage.
     *
     * @param DebugKitStorageInterface|false|null $storage The storage.
     * @return void
     */
    public function setStorage($storage): void
    {
        $this->_storage = $storage;
    }
}

/**
 * Panel failing to render.
 */
class BrokenTestPanel extends DebugPanel
{
    /**
     * @var string
     */
    public $elementName = 'broken_test_panel';

    /**
     * @param mixed $content The content.
     * @return string
     */
    public function summary($content = null)
    {
        throw new RuntimeException('summary failed');
    }
}

/**
 * ToolbarComponentTestCase Test case
 */
class ToolbarComponentTest extends CakeTestCase
{
    /**
     * fixtures
     *
     * @var array
     */
    public $fixtures = ['core.article'];

    /**
     * url for test
     *
     * @var string
     */
    public $url;

    /**
     * Start test callback
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Router::connect('/', ['controller' => 'pages', 'action' => 'display', 'home']);
        $this->_server = $_SERVER;
        $this->_get = $_GET;
        $this->_paths = [];
        $this->_paths['plugins'] = App::path('plugins');
        $this->_paths['views'] = App::path('views');
        $this->_paths['vendors'] = App::path('vendors');
        $this->_paths['controllers'] = App::path('controllers');
        Configure::write('Cache.disable', false);

        $this->url = '/';
    }

    /**
     * endTest
     *
     * @return void
     */
    public function tearDown(): void
    {
        $_SERVER = $this->_server;
        $_GET = $this->_get;

        parent::tearDown();

        App::build([
            'plugins' => $this->_paths['plugins'],
            'views' => $this->_paths['views'],
            'controllers' => $this->_paths['controllers'],
            'vendors' => $this->_paths['vendors'],
        ], true);
        Configure::write('Cache.disable', true);

        unset($this->Controller);
        ClassRegistry::flush();
        if (class_exists('DebugMemory')) {
            DebugMemory::clear();
        }
        if (class_exists('DebugTimer')) {
            DebugTimer::clear();
        }
        Router::reload();
    }

    /**
     * loading test controller
     *
     * @param array $settings The settings.
     * @return Controller The loaded Controller.
     */
    protected function _loadController($settings = []): Controller
    {
        $request = new CakeRequest($this->url);
        $request->addParams(Router::parse($this->url));
        $this->Controller = new Controller($request, new CakeResponse());
        $this->Controller->uses = null;
        $this->Controller->components = ['Toolbar' => $settings + ['className' => 'TestToolbar']];
        $this->Controller->constructClasses();
        $this->Controller->Components->trigger('initialize', [$this->Controller]);

        return $this->Controller;
    }

    /**
     * test Loading of panel classes
     *
     * @return void
     */
    public function testLoadPanels(): void
    {
        $this->_loadController();

        $this->Controller->Toolbar->loadPanels(['session', 'request']);
        $this->assertInstanceOf('SessionPanel', $this->Controller->Toolbar->panels['session']);
        $this->assertInstanceOf('RequestPanel', $this->Controller->Toolbar->panels['request']);

        $this->Controller->Toolbar->loadPanels(['history'], ['history' => 10]);
        $this->assertEquals($this->Controller->Toolbar->panels['history']->history, 10);
    }

    /**
     * Test exceptions on bad panel names
     *
     * @return void
     */
    public function testLoadPanelsError(): void
    {
        $this->_loadController();

        $this->expectExceptionMessageMatches('/^Could not load DebugToolbar panel /');

        try {
            set_error_handler(static function (int $errno, string $errstr): void {
                throw new Exception($errstr, $errno);
            }, E_USER_WARNING);

            $this->Controller->Toolbar->loadPanels(['randomNonExisting', 'request']);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * test Loading of panel classes from a plugin
     *
     * @return void
     */
    public function testLoadPluginPanels(): void
    {
        $debugKitPath = CakePlugin::path('DebugKit');
        $noDir = (empty($debugKitPath) || !file_exists($debugKitPath));
        if ($noDir) {
            $this->markTestAsSkipped('Could not find DebugKit in plugin paths');
        }

        App::build([
            'Plugin' => [$debugKitPath . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS],
        ]);

        CakePlugin::load('DebugkitTestPlugin');
        $this->_loadController();
        $this->Controller->Toolbar->loadPanels(['DebugkitTestPlugin.PluginTest']);
        $this->assertInstanceOf(
            'PluginTestPanel',
            $this->Controller->Toolbar->panels['plugin_test'],
        );
    }

    /**
     * test loading of vendor panels from test_app folder
     *
     * @return void
     */
    public function testLibPanels(): void
    {
        $debugKitPath = CakePlugin::path('DebugKit');
        $noDir = (empty($debugKitPath) || !file_exists($debugKitPath));
        if ($noDir) {
            $this->markTestAsSkipped('Could not find DebugKit in plugin paths');
        }

        App::build([
            'Lib' => [$debugKitPath . 'tests' . DS . 'test_app' . DS . 'Lib' . DS],
        ]);
        $this->_loadController([
            'panels' => ['test'],
            'className' => 'DebugKit.Toolbar',
        ]);
        $this->assertTrue(isset($this->Controller->Toolbar->panels['test']));
        $this->assertInstanceOf('TestPanel', $this->Controller->Toolbar->panels['test']);
    }

    /**
     * test construct
     *
     * @return void
     */
    public function testConstruct(): void
    {
        $this->_loadController();

        $this->assertFalse(empty($this->Controller->Toolbar->panels));

        $memory = DebugMemory::getAll();
        $this->assertTrue(isset($memory['Component initialization']));

        $events = $this->Controller->getEventManager();
        $this->assertNotEmpty($events->listeners('Controller.initialize'));
        $this->assertNotEmpty($events->listeners('Controller.startup'));
        $this->assertNotEmpty($events->listeners('Controller.beforeRender'));
        $this->assertNotEmpty($events->listeners('Controller.shutdown'));
        $this->assertNotEmpty($events->listeners('View.beforeRender'));
        $this->assertNotEmpty($events->listeners('View.afterRender'));
        $this->assertNotEmpty($events->listeners('View.beforeLayout'));
        $this->assertNotEmpty($events->listeners('View.afterLayout'));
    }

    /**
     * test initialize w/ custom panels and defaults
     *
     * @return void
     */
    public function testInitializeCustomPanelsWithDefaults(): void
    {
        $this->_loadController([
            'panels' => ['test'],
        ]);

        $expected = [
            'history', 'session', 'request', 'sql_log', 'timer',
            'log', 'variables', 'environment', 'include', 'test',
        ];
        $this->assertEquals($expected, array_keys($this->Controller->Toolbar->panels));
    }

    /**
     * test syntax for removing panels
     *
     * @return void
     */
    public function testInitializeRemovingPanels(): void
    {
        $this->_loadController([
            'panels' => [
                'session' => false,
                'history' => false,
            ],
        ]);

        $expected = ['request', 'sql_log', 'timer', 'log', 'variables', 'environment', 'include'];
        $this->assertEquals($expected, array_keys($this->Controller->Toolbar->panels));
    }

    /**
     * ensure that Toolbar is not enabled when debug == 0 on initialize
     *
     * @return void
     */
    public function testDebugDisableOnInitialize(): void
    {
        $_debug = Configure::read('debug');
        Configure::write('debug', 0);
        $this->_loadController();
        Configure::write('debug', $_debug);

        $this->assertFalse($this->Controller->Components->enabled('Toolbar'));
    }

    /**
     * test that passing in forceEnable will enable the toolbar even if debug = 0
     *
     * @return void
     */
    public function testForceEnable(): void
    {
        $_debug = Configure::read('debug');
        Configure::write('debug', 0);
        $this->_loadController([
            'forceEnable' => true,
        ]);
        Configure::write('debug', $_debug);

        $this->assertTrue($this->Controller->Components->enabled('Toolbar'));
    }

    /**
     * Test disabling autoRunning of toolbar
     *
     * @return void
     */
    public function testAutoRunSettingFalse(): void
    {
        $this->_loadController([
            'autoRun' => false,
        ]);
        $this->assertFalse($this->Controller->Components->enabled('Toolbar'));
    }

    /**
     * test autorun = false with query string param
     *
     * @return void
     */
    public function testAutoRunSettingWithQueryString(): void
    {
        $this->url = '/?debug=1';
        $_GET['debug'] = 1;
        $this->_loadController([
            'autoRun' => false,
        ]);
        $this->assertTrue($this->Controller->Components->enabled('Toolbar'));
    }

    /**
     * test startup
     *
     * @return void
     */
    public function testStartup(): void
    {
        $this->_loadController([
            'panels' => ['timer'],
        ]);
        $MockPanel = $this->getMock('DebugPanel');
        $MockPanel->expects($this->once())->method('startup');
        $this->Controller->Toolbar->panels['timer'] = $MockPanel;

        $this->Controller->Toolbar->startup($this->Controller);

        $timers = DebugTimer::getAll();
        $this->assertTrue(isset($timers['controllerAction']));
        $memory = DebugMemory::getAll();
        $this->assertTrue(isset($memory['Controller action start']));
    }

    /**
     * Test that cache config generation works.
     *
     * @return void
     */
    public function testCacheConfigGeneration(): void
    {
        $this->_loadController();
        $this->Controller->Components->trigger('startup', [$this->Controller]);

        $results = Cache::config('debug_kit');
        $this->assertTrue(is_array($results));
    }

    /**
     * test state saving of toolbar
     *
     * @return void
     */
    public function testStateSaving(): void
    {
        $this->_loadController();
        $configName = 'debug_kit';
        $this->Controller->Toolbar->cacheKey = 'toolbar_history';

        $this->Controller->Components->trigger('startup', [$this->Controller]);
        $this->Controller->set('test', 'testing');
        $this->Controller->Components->trigger('beforeRender', [$this->Controller]);

        $result = Cache::read('toolbar_history', $configName);
        $this->assertEquals($result[0]['variables']['content']['test'], 'testing');
        Cache::delete('toolbar_history', $configName);
    }

    /**
     * Test that unserializable exceptions are replaced with a readable description when saving state
     *
     * @return void
     */
    public function testStateSavingReplacesExceptions(): void
    {
        $this->_loadController();
        $configName = 'debug_kit';
        $this->Controller->Toolbar->cacheKey = 'toolbar_history';

        $exception = new RuntimeException('Something went wrong');
        $this->Controller->Components->trigger('startup', [$this->Controller]);
        $this->Controller->set('error', $exception);
        $this->Controller->Components->trigger('beforeRender', [$this->Controller]);

        $result = Cache::read('toolbar_history', $configName);
        $expected = sprintf(
            'Unserializable object - RuntimeException. Error: Something went wrong in %s, line %s',
            $exception->getFile(),
            $exception->getLine(),
        );
        $this->assertSame($expected, $result[0]['variables']['content']['error']);
        Cache::delete('toolbar_history', $configName);
    }

    /**
     * Test Before Render callback
     *
     * @return void
     */
    public function testBeforeRender(): void
    {
        $this->_loadController([
            'panels' => ['timer', 'session'],
        ]);
        $MockPanel = $this->getMock('DebugPanel');
        $MockPanel->expects($this->once())->method('beforeRender');
        $this->Controller->Toolbar->panels['timer'] = $MockPanel;
        $this->Controller->Toolbar->beforeRender($this->Controller);

        $this->assertTrue(isset($this->Controller->helpers['DebugKit.Toolbar']));
        $this->assertEquals($this->Controller->helpers['DebugKit.Toolbar']['output'], 'DebugKit.HtmlToolbar');
        $this->assertEquals($this->Controller->helpers['DebugKit.Toolbar']['cacheConfig'], 'debug_kit');
        $this->assertTrue(isset($this->Controller->helpers['DebugKit.Toolbar']['cacheKey']));

        $this->assertTrue(isset($this->Controller->viewVars['debugToolbarPanels']));
        $vars = $this->Controller->viewVars['debugToolbarPanels'];

        $expected = [
            'plugin' => 'DebugKit',
            'elementName' => 'session_panel',
            'content' => $this->Controller->Toolbar->Session->read(),
            'disableTimer' => true,
            'title' => '',
        ];
        $this->assertEquals($expected, $vars['session']);

        $memory = DebugMemory::getAll();
        $this->assertTrue(isset($memory['Controller render start']));
    }

    /**
     * test that vars are gathered and state is saved on beforeRedirect
     *
     * @return void
     */
    public function testBeforeRedirect(): void
    {
        $this->_loadController([
            'panels' => ['session', 'history'],
        ]);

        $configName = 'debug_kit';
        $this->Controller->Toolbar->cacheKey = 'toolbar_history';
        Cache::delete('toolbar_history', $configName);

        DebugTimer::start('controllerAction', 'testing beforeRedirect');
        $MockPanel = $this->getMock('DebugPanel');
        $MockPanel->expects($this->once())->method('beforeRender');
        $this->Controller->Toolbar->panels['session'] = $MockPanel;
        $this->Controller->Toolbar->beforeRedirect($this->Controller, '/another/url');

        $result = Cache::read('toolbar_history', $configName);
        $this->assertTrue(isset($result[0]['session']));
        $this->assertFalse(isset($result[0]['history']));

        $timers = DebugTimer::getAll();
        $this->assertTrue(isset($timers['controllerAction']));
    }

    /**
     * test that loading state (accessing cache) works.
     *
     * @return void
     */
    public function testLoadState(): void
    {
        $this->_loadController();
        $this->Controller->Toolbar->cacheKey = 'toolbar_history';

        $data = [0 => ['my data']];
        Cache::write('toolbar_history', $data, 'debug_kit');
        $result = $this->Controller->Toolbar->loadState(0);
        $this->assertEquals($result, $data[0]);
    }

    /**
     * Test that the history panel links do not adopt the prefix of the current request.
     *
     * @return void
     */
    public function testHistoryUrlGenerationWithPrefixes(): void
    {
        $this->url = '/debugkit_url_with_prefixes_test';
        Router::connect($this->url, [
            'controller' => 'posts',
            'action' => 'edit',
            'admin' => 1,
            'prefix' => 'admin',
            'plugin' => 'cms',
        ]);
        $this->_loadController();
        $this->Controller->Components->trigger('startup', [$this->Controller]);
        $this->Controller->Components->trigger('beforeRender', [$this->Controller]);

        $result = $this->Controller->Toolbar->panels['history']->beforeRender($this->Controller);
        $this->assertSame($this->Controller->Toolbar->requestId, $result['current']['id']);

        Router::setRequestInfo($this->Controller->request);
        $view = new View($this->Controller);
        $html = $view->element('history_panel', ['content' => $result], ['plugin' => 'DebugKit']);
        $this->assertStringContainsString(
            '/debug_kit/panels/index/' . $this->Controller->Toolbar->requestId,
            $html,
        );
        $this->assertStringNotContainsString('/admin/', $html);
    }

    /**
     * Test that the FireCake toolbar is used on AJAX requests
     *
     * @return void
     */
    public function testAjaxToolbar(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->_loadController();
        $this->Controller->Components->trigger('startup', [$this->Controller]);
        $this->Controller->Components->trigger('beforeRender', [$this->Controller]);
        $this->assertEquals($this->Controller->helpers['DebugKit.Toolbar']['output'], 'DebugKit.FirePhpToolbar');
    }

    /**
     * Test that the toolbar does not interfere with requestAction
     *
     * @return void
     */
    public function testNoRequestActionInterference(): void
    {
        $debugKitPath = CakePlugin::path('DebugKit');
        $noDir = (empty($debugKitPath) || !file_exists($debugKitPath));
        if ($noDir) {
            $this->markTestAsSkipped('Could not find DebugKit in plugin paths');
        }

        App::build([
            'Controller' => $debugKitPath . 'tests' . DS . 'test_app' . DS . 'Controller' . DS,
            'View' => [
                $debugKitPath . 'tests' . DS . 'test_app' . DS . 'View' . DS,
                CAKE_CORE_INCLUDE_PATH . DS . 'Cake' . DS . 'View' . DS,
            ],
            'plugins' => $this->_paths['plugins'],
        ]);
        Router::reload();
        $this->_loadController();

        $result = $this->Controller->requestAction('/debug_kit_test/request_action_return', ['return']);
        $this->assertEquals($result, 'I am some value from requestAction.');

        $result = $this->Controller->requestAction('/debug_kit_test/request_action_render', ['return']);
        $this->assertEquals($result, 'I have been rendered.');
    }

    /**
     * Get the toolbar storage, emptied.
     *
     * @return DebugKitDatabaseStorage
     */
    protected function _storage(): DebugKitDatabaseStorage
    {
        DebugKitStorage::reset();
        $storage = DebugKitStorage::get([]);
        $storage->Panel->deleteAll(['1 = 1'], false, false);
        $storage->Request->deleteAll(['1 = 1'], false, false);

        return $storage;
    }

    /**
     * Render the current controller with a layout.
     *
     * @return void
     */
    protected function _render(): void
    {
        $debugKitPath = CakePlugin::path('DebugKit');
        App::build([
            'View' => [
                $debugKitPath . 'tests' . DS . 'test_app' . DS . 'View' . DS,
                CAKE_CORE_INCLUDE_PATH . DS . 'Cake' . DS . 'Test' . DS . 'test_app' . DS . 'View' . DS,
            ],
        ]);
        $this->Controller->Components->trigger('startup', [$this->Controller]);
        $this->Controller->viewPath = 'DebugKitTest';
        $this->Controller->layout = 'default';
        $this->Controller->render('request_action_render');
    }

    /**
     * Test recording a request and injecting the toolbar on shutdown.
     *
     * @return void
     */
    public function testShutdownRecordsAndInjects(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $storage = $this->_storage();
        $this->_loadController();
        $this->Controller->set('test', 'testing');
        $this->_render();
        $this->Controller->Components->trigger('shutdown', [$this->Controller]);

        $id = $this->Controller->Toolbar->requestId;
        $this->assertSame($id, $this->Controller->response->header()['X-DEBUGKIT-ID']);
        $body = $this->Controller->response->body();
        $this->assertMatchesRegularExpression('#<script id="__debug_kit_script" data-id="' . $id . '"[^>]+></script>\s*</body>#', $body);
        $this->assertStringContainsString('debug_kit/requests/view/' . $id, $body);

        $request = $storage->read($id);
        $this->assertSame('GET', $request['method']);
        $this->assertSame(200, $request['status_code']);
        $this->assertSame('text/html', $request['content_type']);
        $this->assertSame(
            ['history', 'session', 'request', 'sql_log', 'timer', 'log', 'variables', 'environment', 'include'],
            array_column($request['panels'], 'panel'),
        );
        $this->assertSame('Sql Log', $request['panels'][3]['title']);
        $this->assertSame('0 xhr', $request['panels'][0]['summary']);

        $variables = $storage->panel($id, 'variables');
        $this->assertStringContainsString('testing', $variables['content']);
        $this->assertStringContainsString('neat-array', $variables['content']);
        // Rendered with the view of the request, which has the DebugTimer helper loaded.
        $this->assertStringContainsString('DebugTimer', $variables['content']);
        $timer = $storage->panel($id, 'timer');
        $this->assertStringContainsString('debug-kit-graph-bar', $timer['content']);

        // Recorded only once.
        $this->assertFalse($this->Controller->Toolbar->record($this->Controller));
    }

    /**
     * Test that the history panel lists the previous requests.
     *
     * @return void
     */
    public function testHistoryListsPreviousRequests(): void
    {
        $storage = $this->_storage();
        $this->_loadController(['panels' => ['history']]);
        $this->Controller->Toolbar->beforeRedirect($this->Controller, '/', 301);
        $first = $this->Controller->Toolbar->requestId;
        $this->assertSame(301, $storage->read($first)['status_code']);
        $this->tearDown();
        $this->setUp();

        $this->_loadController(['panels' => ['history']]);
        $this->Controller->Components->trigger('beforeRender', [$this->Controller]);
        $this->Controller->Toolbar->record($this->Controller);
        $panel = $storage->panel($this->Controller->Toolbar->requestId, 'history');
        $this->assertStringContainsString('data-request="' . $first . '"', $panel['content']);
        $this->assertStringContainsString('data-request="' . $this->Controller->Toolbar->requestId . '"', $panel['content']);
    }

    /**
     * Test that the toolbar is not injected into non HTML responses.
     *
     * @return void
     */
    public function testNoInjectionForNonHtml(): void
    {
        $this->_storage();
        $this->_loadController();
        $this->Controller->response->type('json');
        $this->Controller->response->body('{"html": "</body>"}');
        $this->Controller->Components->trigger('shutdown', [$this->Controller]);

        $this->assertSame('{"html": "</body>"}', $this->Controller->response->body());
        $this->assertArrayHasKey('X-DEBUGKIT-ID', $this->Controller->response->header());
    }

    /**
     * Test that the toolbar is not injected into ajax responses.
     *
     * @return void
     */
    public function testNoInjectionForAjax(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->_storage();
        $this->_loadController();
        $this->Controller->response->body('<html><body></body></html>');
        $this->Controller->Components->trigger('shutdown', [$this->Controller]);

        $this->assertSame('<html><body></body></html>', $this->Controller->response->body());
        $this->assertArrayHasKey('X-DEBUGKIT-ID', $this->Controller->response->header());
    }

    /**
     * Test injection edge cases.
     *
     * @return void
     */
    public function testInjectToolbarEdgeCases(): void
    {
        $this->_loadController();
        $toolbar = $this->Controller->Toolbar;

        $this->Controller->response->body('<p>no body tag</p>');
        $this->assertFalse($toolbar->injectToolbar($this->Controller));

        $this->Controller->response->body(null);
        $this->assertFalse($toolbar->injectToolbar($this->Controller));

        $this->Controller->request->params['ext'] = 'xml';
        $this->Controller->response->body('<body></body>');
        $this->assertFalse($toolbar->injectToolbar($this->Controller));

        $this->Controller->request->params['ext'] = 'html';
        $this->assertTrue($toolbar->injectToolbar($this->Controller));
    }

    /**
     * Test that requestAction and DebugKit requests are not recorded.
     *
     * @return void
     */
    public function testRecordSkipped(): void
    {
        $this->_storage();
        $this->_loadController();
        $this->Controller->request->params['requested'] = 1;
        $this->assertFalse($this->Controller->Toolbar->record($this->Controller));

        $this->_loadController();
        $this->Controller->request->params['plugin'] = 'debug_kit';
        $this->assertFalse($this->Controller->Toolbar->record($this->Controller));

        $this->_loadController();
        $this->Controller->Toolbar->enabled = false;
        $this->assertFalse($this->Controller->Toolbar->record($this->Controller));
    }

    /**
     * Test recording without a storage.
     *
     * @return void
     */
    public function testRecordWithoutStorage(): void
    {
        $this->_loadController();
        $this->Controller->Toolbar->setStorage(false);
        $this->assertNull($this->Controller->Toolbar->storage());
        $this->Controller->response->body('<body></body>');
        $this->Controller->Components->trigger('shutdown', [$this->Controller]);

        $this->assertSame('<body></body>', $this->Controller->response->body());
        $this->assertArrayNotHasKey('X-DEBUGKIT-ID', $this->Controller->response->header());
    }

    /**
     * Test recording when the storage fails.
     *
     * @return void
     */
    public function testRecordStorageFailure(): void
    {
        $this->_loadController(['panels' => ['history' => false]]);
        $storage = $this->getMockBuilder(DebugKitStorageInterface::class)->getMock();
        $storage->expects($this->once())->method('save')->willReturn(false);
        $storage->expects($this->never())->method('prune');
        $this->Controller->Toolbar->setStorage($storage);

        $this->assertFalse($this->Controller->Toolbar->record($this->Controller));
    }

    /**
     * Test that panel errors do not break the recording.
     *
     * @return void
     */
    public function testRecordPanelErrors(): void
    {
        $storage = $this->_storage();
        $this->_loadController(['panels' => ['history' => false]]);
        $this->Controller->Toolbar->panels = ['broken_test' => new BrokenTestPanel()];
        $this->Controller->Toolbar->record($this->Controller);

        $panel = $storage->panel($this->Controller->Toolbar->requestId, 'broken_test');
        $this->assertSame('', $panel['summary']);
        $this->assertStringContainsString('Could not render the panel', $panel['content']);
    }

    /**
     * Test that the panels are rendered with a separate view for non HTML toolbars.
     *
     * @return void
     */
    public function testRecordUsesSeparateViewForAjax(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $storage = $this->_storage();
        $this->_loadController();
        $this->Controller->set('test', 'testing');
        $this->_render();
        $this->assertSame('FirePhpToolbar', $this->Controller->View->Toolbar->getName());
        $this->Controller->Toolbar->record($this->Controller);

        $panel = $storage->panel($this->Controller->Toolbar->requestId, 'request');
        $this->assertStringContainsString('neat-array', $panel['content']);
        $variables = $storage->panel($this->Controller->Toolbar->requestId, 'variables');
        $this->assertStringNotContainsString('DebugTimer', $variables['content']);
    }
}
