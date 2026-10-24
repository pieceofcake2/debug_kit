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

/**
 * DebugKitCacheStorageTest
 */
class DebugKitCacheStorageTest extends CakeTestCase
{
    /**
     * @var DebugKitCacheStorage
     */
    public $storage;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Cache.disable', false);
        Cache::config('debug_kit_cache_storage_test', ['engine' => 'File', 'path' => TMP . 'tests' . DS]);
        Cache::clear(false, 'debug_kit_cache_storage_test');
        $this->storage = new DebugKitCacheStorage('debug_kit_cache_storage_test');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        Cache::clear(false, 'debug_kit_cache_storage_test');
        Cache::drop('debug_kit_cache_storage_test');
        Configure::write('Cache.disable', true);
        parent::tearDown();
    }

    /**
     * Test save, read, panel, recent and prune.
     *
     * @return void
     */
    public function testStorage(): void
    {
        $this->assertSame('debug_kit_cache_storage_test', $this->storage->config());
        for ($i = 1; $i <= 3; $i++) {
            $request = [
                'id' => "request-$i",
                'url' => "/page/$i",
                'method' => 'GET',
                'status_code' => 200,
                'content_type' => 'text/html',
                'requested_at' => "2025-01-01 00:00:0$i.000000",
                'assets' => [],
            ];
            $panels = [['panel' => 'timer', 'title' => 'Timer', 'element' => 'timer_panel', 'summary' => '', 'content' => "<p>$i</p>"]];
            $this->assertTrue($this->storage->save($request, $panels));
        }

        $this->assertSame(['request-3', 'request-2'], array_column($this->storage->recent(2), 'id'));
        $read = $this->storage->read('request-2');
        $this->assertSame('/page/2', $read['url']);
        $this->assertArrayNotHasKey('content', $read['panels'][0]);
        $this->assertSame('<p>2</p>', $this->storage->panel('request-2', 'timer')['content']);
        $this->assertSame('request-2', $this->storage->panel('request-2', 'timer')['request_id']);
        $this->assertNull($this->storage->panel('request-2', 'missing'));
        $this->assertNull($this->storage->panel('missing', 'timer'));
        $this->assertNull($this->storage->read('missing'));

        $this->storage->prune(5);
        $this->assertCount(3, $this->storage->recent(10));
        $this->storage->prune(2);
        $this->assertCount(2, $this->storage->recent(10));
        $this->assertNull($this->storage->read('request-1'));
    }
}
