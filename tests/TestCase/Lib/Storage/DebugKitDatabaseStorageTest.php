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
App::uses('Mysql', 'Model/Datasource/Database');
App::uses('Sqlite', 'Model/Datasource/Database');

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
     * Test that the panel content column is large enough on MySQL.
     *
     * @return void
     */
    public function testTableSchema(): void
    {
        $mysql = $this->getMockBuilder('Mysql')->disableOriginalConstructor()->getMock();
        $tables = DebugKitDatabaseStorage::tableSchema($mysql);
        $this->assertSame(4294967295, $tables['panels']['content']['length']);
        $this->assertStringContainsString('`content` text(4294967295)', (new Mysql([], false))->buildColumn(['name' => 'content'] + $tables['panels']['content']));

        $sqlite = $this->getMockBuilder('Sqlite')->disableOriginalConstructor()->getMock();
        $this->assertSame(DebugKitDatabaseStorage::$schema, DebugKitDatabaseStorage::tableSchema($sqlite));
    }

    /**
     * Test that every indexed string column fits in the 767 byte index limit of MySQL 5.6 with utf8mb4.
     *
     * @return void
     */
    public function testIndexedColumnsFitInnoDbKeyLimit(): void
    {
        $mysql = $this->getMockBuilder('Mysql')->disableOriginalConstructor()->getMock();
        foreach (DebugKitDatabaseStorage::tableSchema($mysql) as $table => $fields) {
            foreach ($fields['indexes'] as $name => $index) {
                foreach ((array)$index['column'] as $column) {
                    $field = $fields[$column];
                    if ($field['type'] !== 'string') {
                        continue;
                    }
                    $this->assertLessThanOrEqual(
                        767,
                        $field['length'] * 4,
                        sprintf('%s.%s in the %s index is too long for utf8mb4 on MySQL 5.6', $table, $column, $name),
                    );
                }
            }
        }
    }

    /**
     * Test that the storage tables can be created with utf8mb4 on MySQL.
     *
     * MySQL 5.6 rejects an index column longer than 767 bytes ("Specified key was too long").
     *
     * @return void
     */
    public function testCreateTablesWithUtf8mb4OnMysql(): void
    {
        $db = ConnectionManager::getDataSource('test');
        $this->skipIf(!$db instanceof Mysql, 'This test requires MySQL.');

        $prefix = 'debug_kit_utf8mb4_';
        $tables = [];
        foreach (DebugKitDatabaseStorage::tableSchema($db) as $table => $fields) {
            $fields['tableParameters'] = ['charset' => 'utf8mb4', 'collate' => 'utf8mb4_unicode_ci', 'engine' => 'InnoDB'];
            $tables[$prefix . $table] = $fields;
        }
        $schema = new CakeSchema(['name' => 'DebugKitUtf8mb4']);
        $schema->tables = $tables;
        try {
            foreach (array_keys($tables) as $table) {
                $this->assertTrue($db->execute($db->createSchema($schema, $table)));
            }
        } finally {
            foreach (array_keys($tables) as $table) {
                $db->execute('DROP TABLE IF EXISTS ' . $db->fullTableName($table));
            }
        }
    }

    /**
     * Test that panels larger than 64 KB are stored in the test database.
     *
     * @return void
     */
    public function testSaveLargePanel(): void
    {
        $config = ConnectionManager::getDataSource('test')->config;
        $config['prefix'] = ($config['prefix'] ?? '') . 'debug_kit_large_';
        ConnectionManager::create('debug_kit_large_test', $config);
        $db = ConnectionManager::getDataSource('debug_kit_large_test');
        try {
            $storage = new DebugKitDatabaseStorage('debug_kit_large_test');
            $id = '33333333-3333-4333-8333-333333333333';
            $content = str_repeat('<p>large panel</p>', 10000);
            $this->assertGreaterThan(65535, strlen($content));
            $panels = [['panel' => 'large', 'title' => 'Large', 'element' => 'large_panel', 'content' => $content]];

            $this->assertTrue($storage->save($this->_request($id), $panels));
            $this->assertSame($content, $storage->panel($id, 'large')['content']);
        } finally {
            foreach (['panels', 'requests'] as $table) {
                if (in_array($config['prefix'] . $table, (array)$db->listSources(), true)) {
                    $db->execute('DROP TABLE ' . $db->fullTableName($config['prefix'] . $table, true, false));
                }
            }
            $db->disconnect();
            ConnectionManager::drop('debug_kit_large_test');
        }
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
