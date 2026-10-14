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

App::uses('DebugKitDatabaseStorage', 'DebugKit.Lib/Storage');

/**
 * DebugKitDatabaseStorageTest
 */
class DebugKitDatabaseStorageTest extends CakeTestCase
{
    /**
     * @var DebugKitDatabaseStorage
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
        $this->storage = new DebugKitDatabaseStorage('debug_kit');
        $this->storage->Panel->deleteAll(['1 = 1'], false, false);
        $this->storage->Request->deleteAll(['1 = 1'], false, false);
    }

    /**
     * Build a request.
     *
     * @param string $id The id.
     * @param string $time The request time.
     * @return array
     */
    protected function _request(string $id, string $time = '2025-01-01 00:00:00.000000'): array
    {
        return [
            'id' => $id,
            'url' => '/posts?page=2',
            'method' => 'GET',
            'status_code' => 200,
            'content_type' => 'text/html',
            'requested_at' => $time,
            'assets' => ['css' => ['/css/panel.css'], 'javascript' => []],
        ];
    }

    /**
     * Build the panels of a request.
     *
     * @return array
     */
    protected function _panels(): array
    {
        return [
            ['panel' => 'timer', 'title' => 'Timer', 'element' => 'timer_panel', 'summary' => '1 s', 'content' => '<p>timer</p>'],
            ['panel' => 'request', 'title' => 'Request', 'element' => 'request_panel', 'summary' => '', 'content' => '<p>request</p>'],
        ];
    }

    /**
     * Test saving and reading a request.
     *
     * @return void
     */
    public function testSaveAndRead(): void
    {
        $id = '11111111-1111-4111-8111-111111111111';
        $this->assertTrue($this->storage->save($this->_request($id), $this->_panels()));

        $result = $this->storage->read($id);
        $this->assertSame('/posts?page=2', $result['url']);
        $this->assertSame(200, $result['status_code']);
        $this->assertSame(['css' => ['/css/panel.css'], 'javascript' => []], $result['assets']);
        $this->assertSame(['timer', 'request'], array_column($result['panels'], 'panel'));
        $this->assertArrayNotHasKey('content', $result['panels'][0]);

        $panel = $this->storage->panel($id, 'request');
        $this->assertSame('<p>request</p>', $panel['content']);
        $this->assertSame($id, $panel['request_id']);

        $this->assertNull($this->storage->panel($id, 'missing'));
        $this->assertNull($this->storage->read('missing'));
    }

    /**
     * Test that a duplicate request is not saved.
     *
     * @return void
     */
    public function testSaveDuplicateFails(): void
    {
        $id = '11111111-1111-4111-8111-111111111111';
        $this->assertTrue($this->storage->save($this->_request($id), $this->_panels()));
        $panels = $this->_panels();
        $panels[] = $panels[0];

        $this->assertFalse($this->storage->save($this->_request('22222222-2222-4222-8222-222222222222'), $panels));
        $this->assertNull($this->storage->read('22222222-2222-4222-8222-222222222222'));
    }

    /**
     * Test recent requests and pruning.
     *
     * @return void
     */
    public function testRecentAndPrune(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $id = sprintf('%08d-0000-4000-8000-000000000000', $i);
            $this->storage->save($this->_request($id, "2025-01-01 00:00:0$i.000000"), $this->_panels());
        }
        $recent = $this->storage->recent(2);
        $this->assertSame(
            ['00000004-0000-4000-8000-000000000000', '00000003-0000-4000-8000-000000000000'],
            array_column($recent, 'id'),
        );
        $this->assertIsArray($recent[0]['assets']);

        $this->storage->prune(2);
        $this->assertCount(2, $this->storage->recent(10));
        $this->assertNull($this->storage->read('00000001-0000-4000-8000-000000000000'));
        $this->assertSame(4, $this->storage->Panel->find('count'));

        $this->storage->prune(10);
        $this->assertCount(2, $this->storage->recent(10));
    }

    /**
     * Test that the storage connection does not log queries.
     *
     * @return void
     */
    public function testConnectionSettings(): void
    {
        $db = ConnectionManager::getDataSource('debug_kit');
        $this->assertFalse($db->fullDebug);
        $this->assertFalse($db->cacheSources);
        $this->assertSame('debug_kit', $this->storage->connection());
    }

    /**
     * Test creating a SQLite database file.
     *
     * @return void
     */
    public function testCreate(): void
    {
        $file = TMP . 'tests' . DS . 'debug_kit_storage_test.sqlite';
        if (file_exists($file)) {
            unlink($file);
        }
        $storage = DebugKitDatabaseStorage::create([
            'connection' => 'debug_kit_storage_test',
            'database' => $file,
        ]);
        try {
            $this->assertInstanceOf(DebugKitDatabaseStorage::class, $storage);
            $this->assertFileExists($file);
            $sources = ConnectionManager::getDataSource('debug_kit_storage_test')->listSources();
            $this->assertContains('requests', $sources);
            $this->assertContains('panels', $sources);

            // An existing connection is reused.
            $again = DebugKitDatabaseStorage::create(['connection' => 'debug_kit_storage_test']);
            $this->assertInstanceOf(DebugKitDatabaseStorage::class, $again);
        } finally {
            ConnectionManager::getDataSource('debug_kit_storage_test')->disconnect();
            ConnectionManager::drop('debug_kit_storage_test');
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Test that an unusable database results in no storage.
     *
     * @return void
     */
    public function testCreateFailure(): void
    {
        $storage = DebugKitDatabaseStorage::create([
            'connection' => 'debug_kit_storage_fail',
            'database' => TMP . 'missing' . DS . 'directory' . DS . 'debug_kit.sqlite',
        ]);
        ConnectionManager::drop('debug_kit_storage_fail');
        $this->assertNull($storage);
        $this->assertTrue(DebugKitDatabaseStorage::sqliteAvailable());
    }
}
