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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ToolbarAccessController', 'DebugKit.Controller');

/**
 * Class ToolbarAccessControllerTest
 */
class ToolbarAccessControllerTest extends CakeTestCase
{
    /**
     * @var ToolbarAccessController
     */
    public $Controller;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Cache.disable', false);

        $request = new CakeRequest('/debug_kit/toolbar_access/history_state/1');
        $this->Controller = new ToolbarAccessController($request, new CakeResponse());
        $this->Controller->constructClasses();
        $this->Controller->Toolbar->cacheKey = 'toolbar_access_controller_test';
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        Cache::delete('toolbar_access_controller_test', 'debug_kit');
        Configure::write('Cache.disable', true);
        unset($this->Controller);

        parent::tearDown();
    }

    /**
     * Test that history_state loads the stored state and renders without a layout.
     *
     * @return void
     */
    public function testHistoryState(): void
    {
        $state = ['request' => ['content' => ['url' => '/posts']]];
        Cache::write('toolbar_access_controller_test', [1 => $state], 'debug_kit');
        $this->Controller->viewClass = 'Json';

        $this->assertNull($this->Controller->history_state('1'));

        $this->assertSame($state, $this->Controller->viewVars['toolbarState']);
        $this->assertTrue($this->Controller->viewVars['debugKitInHistoryMode']);
        $this->assertSame('View', $this->Controller->viewClass);
        $this->assertFalse($this->Controller->layout);
    }

    /**
     * Test that history_state without a key results in an empty state.
     *
     * @return void
     */
    public function testHistoryStateWithoutKey(): void
    {
        Cache::write('toolbar_access_controller_test', [1 => ['my data']], 'debug_kit');

        $this->assertNull($this->Controller->history_state());

        $this->assertSame([], $this->Controller->viewVars['toolbarState']);
    }
}
