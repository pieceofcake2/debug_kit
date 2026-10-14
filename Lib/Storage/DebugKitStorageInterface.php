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
 * Storage for the requests recorded by the toolbar and the rendered panels of each request.
 *
 * A request is an array with the keys `id`, `url`, `method`, `status_code`, `content_type`,
 * `requested_at` and `assets` (an array with `css` and `javascript` lists).
 * A panel is an array with the keys `panel`, `title`, `element`, `summary` and `content`
 * (the rendered HTML of the panel).
 */
interface DebugKitStorageInterface
{
    /**
     * Save a request and its panels.
     *
     * @param array $request The request data.
     * @param array $panels List of panel data.
     * @return bool Success.
     */
    public function save(array $request, array $panels);

    /**
     * Read a request and the list of its panels (without the panel content).
     *
     * @param string $id The request id.
     * @return array|null The request with a `panels` key, or null when not found.
     */
    public function read($id);

    /**
     * Read a single panel of a request, including its rendered content.
     *
     * @param string $requestId The request id.
     * @param string $panel The panel name.
     * @return array|null The panel, or null when not found.
     */
    public function panel($requestId, $panel);

    /**
     * Get the most recent requests, newest first.
     *
     * @param int $limit The maximum number of requests.
     * @return array List of requests (without panels).
     */
    public function recent($limit);

    /**
     * Remove all but the newest requests.
     *
     * @param int $keep The number of requests to keep.
     * @return void
     */
    public function prune($keep);
}
