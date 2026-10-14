<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * Copyright (c) Piece of Cake 2
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DebugKitStorage', 'DebugKit.Lib/Storage');
App::uses('RequestsController', 'DebugKit.Controller');
App::uses('PanelsController', 'DebugKit.Controller');

/**
 * Tests for the toolbar endpoints: RequestsController and PanelsController.
 */
class DebugKitEndpointsTest extends ControllerTestCase
{
    /**
     * Id of the stored request.
     *
     * @var string
     */
    public const REQUEST_ID = '33333333-3333-4333-8333-333333333333';

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Router::reload();
        require CAKE . 'Config' . DS . 'routes.php';
        DebugKitStorage::reset();
        $storage = DebugKitStorage::get([]);
        $storage->Panel->deleteAll(['1 = 1'], false, false);
        $storage->Request->deleteAll(['1 = 1'], false, false);
        $storage->save([
            'id' => self::REQUEST_ID,
            'url' => '/posts',
            'method' => 'GET',
            'status_code' => 200,
            'content_type' => 'text/html',
            'requested_at' => '2025-01-01 00:00:00.000000',
            'assets' => ['css' => ['/my_plugin/css/panel.css'], 'javascript' => ['/my_plugin/js/panel.js']],
        ], [
            ['panel' => 'history', 'title' => 'History', 'element' => 'history_panel', 'summary' => '0 xhr', 'content' => '<p>history</p>'],
            ['panel' => 'sql_log', 'title' => 'Sql Log', 'element' => 'sql_log_panel', 'summary' => '2 / 1 ms', 'content' => '<p>queries</p>'],
        ]);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        Configure::write('debug', 2);
        Configure::delete('DebugKit');
        DebugKitStorage::reset();
        parent::tearDown();
    }

    /**
     * Test rendering the toolbar.
     *
     * @return void
     */
    public function testRequestsView(): void
    {
        $result = $this->testAction('/debug_kit/requests/view/' . self::REQUEST_ID, ['return' => 'contents', 'method' => 'get']);

        $this->assertStringContainsString('<ul id="toolbar" class="c-toolbar">', $result);
        $this->assertStringContainsString('data-panel="sql_log"', $result);
        $this->assertStringContainsString('<span class="c-panel__summary">2 / 1 ms</span>', $result);
        $this->assertStringContainsString('debug_kit/css/style.css', $result);
        $this->assertStringContainsString('my_plugin/css/panel.css', $result);
        $this->assertStringContainsString('my_plugin/js/panel.js', $result);
        $this->assertStringContainsString('debug_kit/js/jquery.js', $result);
        $this->assertMatchesRegularExpression(
            '#<script id="__debug_kit_app" type="module" src="[^"]*debug_kit/js/main.js[^"]*" data-id="' . self::REQUEST_ID . '"#',
            $result,
        );
        $this->assertStringContainsString('debug_kit/panels/view" data-panels-url="', $result);
        $this->assertSame('no-store', $this->controller->response->header()['Cache-Control']);
    }

    /**
     * Test that a missing request is a 404.
     *
     * @return void
     */
    public function testRequestsViewNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->testAction('/debug_kit/requests/view/missing', ['method' => 'get']);
    }

    /**
     * Test that the endpoints are not available without debug mode.
     *
     * @return void
     */
    public function testNotAvailableWithoutDebug(): void
    {
        Configure::write('debug', 0);
        $this->expectException(NotFoundException::class);
        $this->testAction('/debug_kit/requests/view/' . self::REQUEST_ID, ['method' => 'get']);
    }

    /**
     * Test that forceEnable makes the endpoints available without debug mode.
     *
     * @return void
     */
    public function testForceEnableWithoutDebug(): void
    {
        Configure::write('debug', 0);
        Configure::write('DebugKit.forceEnable', true);
        $result = $this->testAction('/debug_kit/panels/view/' . self::REQUEST_ID . '/sql_log', ['return' => 'contents', 'method' => 'get']);
        $this->assertStringContainsString('<p>queries</p>', $result);
    }

    /**
     * Test the error when there is no storage.
     *
     * @return void
     */
    public function testNoStorage(): void
    {
        Configure::write('DebugKit.storage', 'cache');
        Configure::write('Cache.disable', true);
        $this->expectException(NotFoundException::class);
        $this->testAction('/debug_kit/panels/view/' . self::REQUEST_ID . '/sql_log', ['method' => 'get']);
    }

    /**
     * Test viewing a panel.
     *
     * @return void
     */
    public function testPanelsView(): void
    {
        $result = $this->testAction('/debug_kit/panels/view/' . self::REQUEST_ID . '/sql_log', ['return' => 'contents', 'method' => 'get']);

        $this->assertStringContainsString('<h2 class="c-panel__title">Sql Log</h2>', $result);
        $this->assertStringContainsString('id="sql_log-tab" data-request-id="' . self::REQUEST_ID . '"', $result);
        $this->assertStringContainsString('<p>queries</p>', $result);
        $this->assertStringNotContainsString('<html', $result);
    }

    /**
     * Test viewing a panel of the latest request.
     *
     * @return void
     */
    public function testPanelsViewLatest(): void
    {
        $result = $this->testAction('/debug_kit/panels/view/latest/history', ['return' => 'contents', 'method' => 'get']);
        $this->assertStringContainsString('<p>history</p>', $result);
    }

    /**
     * Test viewing a missing panel.
     *
     * @return void
     */
    public function testPanelsViewNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->testAction('/debug_kit/panels/view/' . self::REQUEST_ID . '/missing', ['method' => 'get']);
    }

    /**
     * Test listing the panels of a request.
     *
     * @return void
     */
    public function testPanelsIndex(): void
    {
        $result = $this->testAction('/debug_kit/panels/index/' . self::REQUEST_ID, ['return' => 'contents', 'method' => 'get']);
        $result = json_decode($result, true);

        $this->assertSame([
            ['request_id' => self::REQUEST_ID, 'panel' => 'history', 'title' => 'History', 'summary' => '0 xhr'],
            ['request_id' => self::REQUEST_ID, 'panel' => 'sql_log', 'title' => 'Sql Log', 'summary' => '2 / 1 ms'],
        ], $result['panels']);
    }

    /**
     * Test listing the panels of a missing request.
     *
     * @return void
     */
    public function testPanelsIndexNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->testAction('/debug_kit/panels/index/missing', ['method' => 'get']);
    }
}
