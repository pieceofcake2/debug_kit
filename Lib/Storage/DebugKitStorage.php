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
 * Resolves the storage used for the toolbar request history.
 *
 * ### Settings
 *
 * Settings are read from the ToolbarComponent settings, which include `Configure::read('DebugKit')`.
 * The toolbar endpoints (RequestsController, PanelsController) only see `Configure::read('DebugKit')`,
 * so storage settings should be configured there.
 *
 * - `storage` `auto` (default) uses a database and falls back to the cache when no database is
 *   available, `database` or `cache` force one of them.
 * - `connection` / `database` See DebugKitDatabaseStorage::create().
 * - `cache` Cache configuration used for the cache storage (merged with the defaults of the
 *   `debug_kit` cache configuration).
 * - `requestCount` Number of requests to keep, defaults to 20.
 */
class DebugKitStorage
{
    /**
     * Name of the cache configuration used by the cache storage.
     *
     * @var string
     */
    public const CACHE_CONFIG = 'debug_kit';

    /**
     * Default number of requests kept in the storage.
     *
     * @var int
     */
    public const REQUEST_COUNT = 20;

    /**
     * Resolved storages, keyed by a hash of their settings.
     *
     * @var array<string, DebugKitStorageInterface|false>
     */
    protected static $_instances = [];

    /**
     * Get the storage for the given settings.
     *
     * @param array|null $settings The settings, defaults to `Configure::read('DebugKit')`.
     * @return DebugKitStorageInterface|null The storage or null when none is available.
     */
    public static function get($settings = null)
    {
        if ($settings === null) {
            $settings = (array)Configure::read('DebugKit');
        }
        $settings = array_intersect_key(
            $settings,
            array_flip(['storage', 'connection', 'database', 'cache', 'cacheDuration']),
        );
        $key = md5(serialize($settings));
        if (!array_key_exists($key, static::$_instances)) {
            static::$_instances[$key] = static::_create($settings) ?: false;
        }

        return static::$_instances[$key] ?: null;
    }

    /**
     * Forget the resolved storages.
     *
     * @return void
     */
    public static function reset()
    {
        static::$_instances = [];
    }

    /**
     * Get the number of requests to keep.
     *
     * @param array|null $settings The settings, defaults to `Configure::read('DebugKit')`.
     * @return int
     */
    public static function requestCount($settings = null)
    {
        if ($settings === null) {
            $settings = (array)Configure::read('DebugKit');
        }
        $count = (int)($settings['requestCount'] ?? static::REQUEST_COUNT);

        return max(1, $count);
    }

    /**
     * Create the storage.
     *
     * @param array $settings The settings.
     * @return DebugKitStorageInterface|null
     */
    protected static function _create(array $settings)
    {
        $type = $settings['storage'] ?? 'auto';
        if ($type !== 'cache') {
            $storage = DebugKitDatabaseStorage::create($settings);
            if ($storage || $type === 'database') {
                return $storage;
            }
        }

        return static::_cacheStorage($settings);
    }

    /**
     * Create the cache storage, configuring the `debug_kit` cache when needed.
     *
     * @param array $settings The settings.
     * @return DebugKitCacheStorage|null
     */
    protected static function _cacheStorage(array $settings)
    {
        if (Configure::read('Cache.disable') === true) {
            return null;
        }
        if (!Cache::config(static::CACHE_CONFIG)) {
            $cache = [
                'duration' => $settings['cacheDuration'] ?? '+4 hours',
                'engine' => 'File',
                'path' => CACHE,
            ];
            if (isset($settings['cache'])) {
                $cache = array_merge($cache, (array)$settings['cache']);
            }
            if (!Cache::config(static::CACHE_CONFIG, $cache)) {
                return null;
            }
        }

        return new DebugKitCacheStorage(static::CACHE_CONFIG);
    }
}
