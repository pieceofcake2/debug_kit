<?php
/**
 * Toolbar Abstract Helper Test Case
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 **/

$path = CakePlugin::path('DebugKit');
require_once $path . 'tests' . DS . 'TestCase' . DS . 'TestFireCake.php';

/**
 * Class FirePhpToolbarHelperTestCase
 *
 * @since         DebugKit 0.1
 */
class FirePhpToolbarHelperTest extends CakeTestCase
{
    /**
     * setUp
     *
     * @return void
     **/
    public function setUp(): void
    {
        parent::setUp();

        Router::connect('/:controller/:action');
        Router::connect('/', ['controller' => 'pages', 'action' => 'display', 'home']);
        Router::parse('/');

        $this->Controller = new Controller($this->getMock('CakeRequest'), new CakeResponse());
        $this->View = new View($this->Controller);
        $this->Toolbar = new ToolbarHelper($this->View, ['output' => 'DebugKit.FirePhpToolbar']);
        $this->Toolbar->FirePhpToolbar = new FirePhpToolbarHelper($this->View);

        $this->firecake = FireCake::getInstance('TestFireCake');
        TestFireCake::reset();
    }

    /**
     * Start test - switch view paths
     *
     * @return void
     **/
    public static function setupBeforeClass(): void
    {
        // The views of the core's test app are in templates/ since pieceofcake2/cakephp 2.13, in View/ before
        $coreTestApp = CORE_TESTS . DS . 'test_app' . DS;
        App::build([
            'View' => [
                $coreTestApp . (is_dir($coreTestApp . 'templates') ? 'templates' : 'View') . DS,
                APP . 'Plugin' . DS . 'DebugKit' . DS . 'View' . DS,
                CAKE_CORE_INCLUDE_PATH . DS . 'Cake' . DS . 'View' . DS,
            ]], true);
    }

    /**
     * End Test
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        App::build();
    }

    /**
     * TearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->Toolbar, $this->Controller);
        TestFireCake::reset();
    }

    /**
     * Test neat array (dump)creation
     *
     * @return void
     */
    public function testMakeNeatArray(): void
    {
        $this->Toolbar->makeNeatArray([1, 2, 3]);
        $result = $this->firecake->sentHeaders;
        $this->assertTrue(isset($result['X-Wf-1-1-1-1']));
        $this->assertMatchesRegularExpression('/\[1,2,3\]/', $result['X-Wf-1-1-1-1']);
    }

    /**
     * Test afterlayout element rendering
     *
     * @return void
     */
    public function testAfterLayout(): void
    {
        $this->Controller->viewPath = 'Posts';
        $request = new CakeRequest('/posts/index');
        $request->addParams(Router::parse($request->url));
        $request->addPaths([
            'webroot' => '/',
            'base' => '/',
            'here' => '/posts/index',
        ]);
        $this->Controller->setRequest($request);
        $this->Controller->layout = 'default';
        $this->Controller->uses = null;
        $this->Controller->components = ['DebugKit.Toolbar'];
        $this->Controller->constructClasses();
        $this->Controller->Components->trigger('startup', [$this->Controller]);
        $this->Controller->Components->trigger('beforeRender', [$this->Controller]);
        $result = $this->Controller->render();
        $this->assertDoesNotMatchRegularExpression('/debug-toolbar/', (string)$result);
        $result = $this->firecake->sentHeaders;
        $this->assertTrue(is_array($result));
    }

    /**
     * test starting a panel
     *
     * @return void
     **/
    public function testPanelStart(): void
    {
        $this->Toolbar->panelStart('My Panel', 'my_panel');
        $result = $this->firecake->sentHeaders;
        $this->assertMatchesRegularExpression('/GROUP_START.+My Panel/', $result['X-Wf-1-1-1-1']);
    }

    /**
     * test ending a panel
     *
     * @return void
     **/
    public function testPanelEnd(): void
    {
        $this->Toolbar->panelEnd();
        $result = $this->firecake->sentHeaders;
        $this->assertMatchesRegularExpression('/GROUP_END/', $result['X-Wf-1-1-1-1']);
    }
}
