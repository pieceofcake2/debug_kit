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

App::uses('ConnectionManager', 'Model');
App::uses('CakeSchema', 'Model');
App::uses('DebugKitStorageInterface', 'DebugKit.Lib/Storage');
App::uses('DebugKitRequest', 'DebugKit.Model');
App::uses('DebugKitPanel', 'DebugKit.Model');

/**
 * Stores the toolbar requests in a database, by default a SQLite database in `TMP/debug_kit.sqlite`.
 *
 * The connection is created on demand with ConnectionManager::create() unless the application
 * already defines a connection with the same name. The tables are created when they are missing.
 */
class DebugKitDatabaseStorage implements DebugKitStorageInterface
{
    /**
     * Schema of the storage tables, in CakeSchema format.
     *
     * The `panel` column is part of a unique index, so it is limited to 191 characters: with
     * utf8mb4, MySQL 5.6 (and 5.7 without large index prefixes) rejects index columns longer
     * than 767 bytes, and 191 is the longest utf8mb4 VARCHAR that fits.
     *
     * @var array
     */
    public static $schema = [
        'requests' => [
            'id' => ['type' => 'string', 'length' => 36, 'null' => false, 'key' => 'primary'],
            'url' => ['type' => 'text', 'null' => false],
            'content_type' => ['type' => 'string', 'length' => 255, 'null' => true],
            'status_code' => ['type' => 'integer', 'null' => true],
            'method' => ['type' => 'string', 'length' => 16, 'null' => true],
            'requested_at' => ['type' => 'string', 'length' => 26, 'null' => false],
            'assets' => ['type' => 'text', 'null' => true],
            'indexes' => [
                'PRIMARY' => ['column' => 'id', 'unique' => 1],
                'debug_kit_requests_requested_at' => ['column' => 'requested_at', 'unique' => 0],
            ],
        ],
        'panels' => [
            'id' => ['type' => 'string', 'length' => 36, 'null' => false, 'key' => 'primary'],
            'request_id' => ['type' => 'string', 'length' => 36, 'null' => false],
            'panel' => ['type' => 'string', 'length' => 191, 'null' => false],
            'title' => ['type' => 'string', 'length' => 255, 'null' => true],
            'element' => ['type' => 'string', 'length' => 255, 'null' => true],
            'summary' => ['type' => 'string', 'length' => 255, 'null' => true],
            'position' => ['type' => 'integer', 'null' => false, 'default' => 0],
            'content' => ['type' => 'text', 'null' => true],
            'indexes' => [
                'PRIMARY' => ['column' => 'id', 'unique' => 1],
                'debug_kit_panels_request_panel' => ['column' => ['request_id', 'panel'], 'unique' => 1],
            ],
        ],
    ];

    /**
     * The requests model.
     *
     * @var DebugKitRequest
     */
    public $Request;

    /**
     * The panels model.
     *
     * @var DebugKitPanel
     */
    public $Panel;

    /**
     * Name of the connection.
     *
     * @var string
     */
    protected $_connection;

    /**
     * Create the storage for an existing connection.
     *
     * Use DebugKitDatabaseStorage::create() to have the connection set up automatically.
     *
     * @param string $connection The connection name.
     */
    public function __construct($connection = 'debug_kit')
    {
        $this->_connection = $connection;
        $db = ConnectionManager::getDataSource($connection);
        if ($db instanceof DboSource) {
            // Toolbar storage queries must not show up in the SQL log panel nor be cached.
            $db->fullDebug = false;
            $db->cacheSources = false;
        }
        $this->ensureTables();

        $this->Request = new DebugKitRequest(false, null, $connection);
        $this->Panel = new DebugKitPanel(false, null, $connection);
    }

    /**
     * Set up the connection and create the storage.
     *
     * ### Settings
     *
     * - `connection` The connection name, defaults to `debug_kit`. When the application has
     *   configured a connection with that name, it is used as is.
     * - `database` The SQLite database file used when the connection has to be created.
     *   Defaults to `TMP . 'debug_kit.sqlite'`.
     *
     * @param array $settings The settings.
     * @return DebugKitDatabaseStorage|null The storage, or null when no database is available.
     */
    public static function create(array $settings = [])
    {
        $settings += ['connection' => 'debug_kit', 'database' => TMP . 'debug_kit.sqlite'];
        $connection = $settings['connection'];

        try {
            if (!static::_hasConnection($connection)) {
                if (!static::sqliteAvailable()) {
                    return null;
                }
                ConnectionManager::create($connection, [
                    'datasource' => 'Database/Sqlite',
                    'database' => $settings['database'],
                    'persistent' => false,
                ]);
            }

            return new self($connection);
        } catch (Exception $e) {
            CakeLog::write(LOG_WARNING, sprintf(
                'DebugKit could not use the "%s" connection for the toolbar storage: %s',
                $connection,
                $e->getMessage(),
            ));
        }

        return null;
    }

    /**
     * Check whether the PDO SQLite driver is available.
     *
     * @return bool
     */
    public static function sqliteAvailable()
    {
        return class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers(), true);
    }

    /**
     * Check whether a connection is configured.
     *
     * @param string $name The connection name.
     * @return bool
     */
    protected static function _hasConnection($name)
    {
        return in_array($name, ConnectionManager::sourceList(), true) ||
            array_key_exists($name, ConnectionManager::enumConnectionObjects());
    }

    /**
     * Get the connection name.
     *
     * @return string
     */
    public function connection()
    {
        return $this->_connection;
    }

    /**
     * Create the storage tables when they do not exist.
     *
     * @return void
     */
    public function ensureTables()
    {
        $db = ConnectionManager::getDataSource($this->_connection);
        if (!$db instanceof DboSource) {
            throw new CakeException(sprintf('The "%s" connection is not a database connection.', $this->_connection));
        }
        $existing = (array)$db->listSources();
        $prefix = $db->config['prefix'] ?? '';
        $missing = [];
        foreach (array_keys(static::$schema) as $table) {
            if (!in_array($prefix . $table, $existing, true)) {
                $missing[] = $table;
            }
        }
        if (!$missing) {
            return;
        }
        $schema = new CakeSchema(['name' => 'DebugKit']);
        $schema->tables = static::tableSchema($db);
        foreach ($missing as $table) {
            $db->execute($db->createSchema($schema, $table));
        }
    }

    /**
     * Get the schema of the storage tables for a datasource.
     *
     * A TEXT column holds at most 64 KB on MySQL, which rendered panels can exceed. Giving the
     * column a length makes MySQL use the smallest text type that fits, LONGTEXT here. Other
     * databases have no such limit, and PostgreSQL and SQL Server do not accept a length.
     *
     * @param DboSource $db The datasource.
     * @return array
     */
    public static function tableSchema(DboSource $db)
    {
        $tables = static::$schema;
        if ($db instanceof Mysql) {
            $tables['panels']['content']['length'] = 4294967295;
        }

        return $tables;
    }

    /**
     * @inheritDoc
     */
    public function save(array $request, array $panels)
    {
        $db = $this->Request->getDataSource();
        $request['assets'] = json_encode($request['assets'] ?? []);
        $rows = [];
        foreach (array_values($panels) as $position => $panel) {
            $rows[] = [
                'id' => CakeText::uuid(),
                'request_id' => $request['id'],
                'panel' => $panel['panel'],
                'title' => $panel['title'] ?? null,
                'element' => $panel['element'] ?? null,
                'summary' => isset($panel['summary']) ? mb_substr((string)$panel['summary'], 0, 255) : null,
                'position' => $position,
                'content' => $panel['content'] ?? null,
            ];
        }

        try {
            $db->begin();
            $this->Request->create(false);
            $saved = (bool)$this->Request->save($request, ['validate' => false, 'callbacks' => false]);
            if ($saved && $rows) {
                $saved = (bool)$this->Panel->saveMany($rows, ['validate' => false, 'callbacks' => false, 'atomic' => false]);
            }
            if (!$saved) {
                $db->rollback();

                return false;
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            CakeLog::write(LOG_WARNING, 'DebugKit could not save the request: ' . $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function read($id)
    {
        $row = $this->Request->find('first', [
            'conditions' => [$this->Request->alias . '.id' => $id],
        ]);
        if (!$row) {
            return null;
        }
        $request = $this->_formatRequest($row[$this->Request->alias]);
        $request['panels'] = $this->Panel->byRequest($id);

        return $request;
    }

    /**
     * @inheritDoc
     */
    public function panel($requestId, $panel)
    {
        $row = $this->Panel->find('first', [
            'conditions' => [
                $this->Panel->alias . '.request_id' => $requestId,
                $this->Panel->alias . '.panel' => $panel,
            ],
        ]);

        return $row ? $row[$this->Panel->alias] : null;
    }

    /**
     * @inheritDoc
     */
    public function recent($limit)
    {
        return array_map([$this, '_formatRequest'], $this->Request->recent($limit));
    }

    /**
     * @inheritDoc
     */
    public function prune($keep)
    {
        try {
            $ids = Hash::extract($this->Request->recent($keep), '{n}.id');
            if (!$ids) {
                return;
            }
            $this->Panel->deleteAll(['NOT' => [$this->Panel->alias . '.request_id' => $ids]], false, false);
            $this->Request->deleteAll(['NOT' => [$this->Request->alias . '.id' => $ids]], false, false);
        } catch (Exception $e) {
            CakeLog::write(LOG_WARNING, 'DebugKit could not prune old requests: ' . $e->getMessage());
        }
    }

    /**
     * Decode the stored request row.
     *
     * @param array $request The request row.
     * @return array
     */
    protected function _formatRequest(array $request)
    {
        $assets = json_decode((string)($request['assets'] ?? ''), true);
        $request['assets'] = is_array($assets) ? $assets : [];
        if (isset($request['status_code'])) {
            $request['status_code'] = (int)$request['status_code'];
        }

        return $request;
    }
}
