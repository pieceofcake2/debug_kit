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

/**
 * Provides debug information on the SQL logs and provides links to an ajax explain interface.
 */
class SqlLogPanel extends DebugPanel
{
    /**
     * Minimum number of Rows Per Millisecond that must be returned by a query before an explain
     * is done.
     *
     * @var int
     */
    public $slowRate = 20;

    /**
     * Name of the connection used by the toolbar storage, which is not logged.
     *
     * @var string
     */
    public $storageConnection = 'debug_kit';

    /**
     * Constructor
     *
     * @param array $settings The toolbar settings.
     */
    public function __construct($settings = [])
    {
        parent::__construct();
        if (!empty($settings['connection'])) {
            $this->storageConnection = $settings['connection'];
        }
    }

    /**
     * Gets the connection names that should have logs + dumps generated.
     *
     * @param Controller $controller The controller.
     * @return array
     */
    public function beforeRender(Controller $controller)
    {
        if (!class_exists('ConnectionManager')) {
            return [];
        }
        $connections = [];

        $dbConfigs = ConnectionManager::sourceList();
        foreach ($dbConfigs as $configName) {
            if ($configName === $this->storageConnection) {
                continue;
            }
            $driver = null;
            $db = ConnectionManager::getDataSource($configName);
            if (
                (empty($db->config['driver']) && empty($db->config['datasource'])) ||
                !method_exists($db, 'getLog')
            ) {
                continue;
            }
            if (isset($db->config['datasource'])) {
                $driver = $db->config['datasource'];
            }
            $explain = false;
            $isExplainable = preg_match('/(Mysql|Postgres)$/', $driver);
            if ($isExplainable) {
                $explain = true;
            }
            $connections[$configName] = $explain;
        }

        return ['connections' => $connections, 'threshold' => $this->slowRate];
    }

    /**
     * Get the summary: number of queries and total query time.
     *
     * @param mixed $content The panel content.
     * @return string
     */
    public function summary($content = null)
    {
        if (empty($content['connections'])) {
            return '0';
        }
        $count = $time = 0;
        foreach (array_keys($content['connections']) as $configName) {
            $db = ConnectionManager::getDataSource($configName);
            if (!method_exists($db, 'getLog')) {
                continue;
            }
            $log = $db->getLog(false, false);
            $count += (int)$log['count'];
            $time += (float)$log['time'];
        }
        if (!$count) {
            return '0';
        }

        return sprintf('%d / %s ms', $count, round($time));
    }
}
