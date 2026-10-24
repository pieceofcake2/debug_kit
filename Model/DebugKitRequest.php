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
 * The requests table tracks basic information about each request recorded by the toolbar.
 *
 * The model extends Model directly (not AppModel) so that application wide
 * behaviors and callbacks do not interfere with the toolbar storage.
 */
class DebugKitRequest extends Model
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
    public $useTable = 'requests';

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
     * Get the most recent requests.
     *
     * @param int $limit Maximum number of requests.
     * @return array List of request rows, newest first.
     */
    public function recent($limit)
    {
        $rows = $this->find('all', [
            'order' => [$this->alias . '.requested_at' => 'DESC'],
            'limit' => max(1, (int)$limit),
        ]);

        return Hash::extract($rows, '{n}.' . $this->alias);
    }
}
