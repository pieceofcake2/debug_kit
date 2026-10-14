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

/**
 * DebugKitStorageTest
 */
class DebugKitStorageTest extends CakeTestCase
{
    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        DebugKitStorage::reset();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        DebugKitStorage::reset();
        Configure::delete('DebugKit');
        Configure::write('Cache.disable', true);
        Cache::drop('debug_kit');
        parent::tearDown();
    }

    /**
     * Test that the database storage is used by default.
     *
     * @return void
     */
    public function testGetDatabase(): void
    {
        $storage = DebugKitStorage::get([]);
        $this->assertInstanceOf(DebugKitDatabaseStorage::class, $storage);
        $this->assertSame($storage, DebugKitStorage::get(['panels' => ['ignored']]));
        $this->assertSame($storage, DebugKitStorage::get());
    }

    /**
     * Test the cache storage.
     *
     * @return void
     */
    public function testGetCache(): void
    {
        Configure::write('Cache.disable', false);
        $storage = DebugKitStorage::get(['storage' => 'cache', 'cache' => ['prefix' => 'dk_test_']]);
        $this->assertInstanceOf(DebugKitCacheStorage::class, $storage);
        $this->assertSame('dk_test_', Cache::settings('debug_kit')['prefix']);
    }

    /**
     * Test that the cache storage is not available when the cache is disabled.
     *
     * @return void
     */
    public function testGetCacheDisabled(): void
    {
        Configure::write('Cache.disable', true);
        $this->assertNull(DebugKitStorage::get(['storage' => 'cache']));
    }

    /**
     * Test the fallback to the cache when the database is not available.
     *
     * @return void
     */
    public function testFallbackToCache(): void
    {
        Configure::write('Cache.disable', false);
        $settings = [
            'connection' => 'debug_kit_fallback_test',
            'database' => TMP . 'missing' . DS . 'directory' . DS . 'debug_kit.sqlite',
        ];
        $this->assertInstanceOf(DebugKitCacheStorage::class, DebugKitStorage::get($settings));
        DebugKitStorage::reset();
        $this->assertNull(DebugKitStorage::get($settings + ['storage' => 'database']));
        ConnectionManager::drop('debug_kit_fallback_test');
    }

    /**
     * Test the number of kept requests.
     *
     * @return void
     */
    public function testRequestCount(): void
    {
        $this->assertSame(DebugKitStorage::REQUEST_COUNT, DebugKitStorage::requestCount([]));
        $this->assertSame(5, DebugKitStorage::requestCount(['requestCount' => 5]));
        $this->assertSame(1, DebugKitStorage::requestCount(['requestCount' => 0]));
        Configure::write('DebugKit.requestCount', 7);
        $this->assertSame(7, DebugKitStorage::requestCount());
    }
}
