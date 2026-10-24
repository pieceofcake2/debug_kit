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
 * The panels table holds the rendered output of each panel of each recorded request.
 *
 * The model extends Model directly (not AppModel) so that application wide
 * behaviors and callbacks do not interfere with the toolbar storage.
 */
class DebugKitPanel extends Model
{
    /**
     * Connection used by the toolbar storage.
     *
     * @var string
     */
    public $useDbConfig = 'debug_kit';

    /**
     * Table name.
     *
     * @var string|false
     */
    public $useTable = 'panels';

    /**
     * Do not cache queries, the data changes on every request.
     *
     * @var bool
     */
    public $cacheQueries = false;

    /**
     * No associations are fetched.
     *
     * @var int
     */
    public $recursive = -1;

    /**
     * Find the panels of a request.
     *
     * @param string $requestId The request id.
     * @param bool $withContent Whether to include the rendered panel content.
     * @return array List of panel rows in the order they were saved.
     */
    public function byRequest($requestId, $withContent = false)
    {
        $fields = ['id', 'request_id', 'panel', 'title', 'element', 'summary', 'position'];
        if ($withContent) {
            $fields[] = 'content';
        }
        $rows = $this->find('all', [
            'fields' => $fields,
            'conditions' => [$this->alias . '.request_id' => $requestId],
            'order' => [$this->alias . '.position' => 'ASC'],
        ]);

        return Hash::extract($rows, '{n}.' . $this->alias);
    }
}
