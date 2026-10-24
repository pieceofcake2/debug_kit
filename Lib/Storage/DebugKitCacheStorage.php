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
 * Stores the toolbar requests in a cache configuration.
 *
 * Used as a fallback when no database (e.g. the PDO SQLite driver) is available.
 */
class DebugKitCacheStorage implements DebugKitStorageInterface
{
    /**
     * Cache key holding the index of the stored requests.
     *
     * @var string
     */
    public const INDEX_KEY = 'debug_kit_requests';

    /**
     * Cache key prefix of a stored request.
     *
     * @var string
     */
    public const REQUEST_KEY = 'debug_kit_request_';

    /**
     * The cache configuration name.
     *
     * @var string
     */
    protected $_config;

    /**
     * Constructor
     *
     * @param string $config The cache configuration name.
     */
    public function __construct($config = 'debug_kit')
    {
        $this->_config = $config;
    }

    /**
     * Get the cache configuration name.
     *
     * @return string
     */
    public function config()
    {
        return $this->_config;
    }

    /**
     * @inheritDoc
     */
    public function save(array $request, array $panels)
    {
        $request['panels'] = array_values($panels);
        if (!Cache::write($this->_key($request['id']), $request, $this->_config)) {
            return false;
        }
        unset($request['panels']);
        $index = $this->_index();
        array_unshift($index, $request);

        return (bool)Cache::write(static::INDEX_KEY, $index, $this->_config);
    }

    /**
     * @inheritDoc
     */
    public function read($id)
    {
        $request = Cache::read($this->_key($id), $this->_config);
        if (!is_array($request)) {
            return null;
        }
        foreach ($request['panels'] as $i => $panel) {
            unset($request['panels'][$i]['content']);
        }

        return $request;
    }

    /**
     * @inheritDoc
     */
    public function panel($requestId, $panel)
    {
        $request = Cache::read($this->_key($requestId), $this->_config);
        if (!is_array($request) || empty($request['panels'])) {
            return null;
        }
        foreach ($request['panels'] as $row) {
            if ($row['panel'] === $panel) {
                return $row + ['request_id' => $requestId];
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function recent($limit)
    {
        return array_slice($this->_index(), 0, max(1, (int)$limit));
    }

    /**
     * @inheritDoc
     */
    public function prune($keep)
    {
        $index = $this->_index();
        if (count($index) <= $keep) {
            return;
        }
        foreach (array_slice($index, $keep) as $request) {
            Cache::delete($this->_key($request['id']), $this->_config);
        }
        Cache::write(static::INDEX_KEY, array_slice($index, 0, $keep), $this->_config);
    }

    /**
     * Read the index of stored requests.
     *
     * @return array
     */
    protected function _index()
    {
        $index = Cache::read(static::INDEX_KEY, $this->_config);

        return is_array($index) ? $index : [];
    }

    /**
     * Get the cache key of a request.
     *
     * @param string $id The request id.
     * @return string
     */
    protected function _key($id)
    {
        return static::REQUEST_KEY . preg_replace('/[^A-Za-z0-9]/', '', (string)$id);
    }
}
