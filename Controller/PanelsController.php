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
 * Provides access to the panels of the recorded requests.
 */
class PanelsController extends DebugKitController
{
    /**
     * List the panels of a request as JSON.
     *
     * @param string|null $requestId The request id.
     * @return void
     * @throws NotFoundException
     */
    public function index($requestId = null)
    {
        $request = $requestId ? $this->storage->read($requestId) : null;
        if (!$request) {
            throw new NotFoundException(__d('debug_kit', 'Request not found.'));
        }
        $panels = [];
        foreach ($request['panels'] as $panel) {
            $panels[] = [
                'request_id' => $request['id'],
                'panel' => $panel['panel'],
                'title' => $panel['title'],
                'summary' => (string)$panel['summary'],
            ];
        }
        $this->set(['panels' => $panels, '_serialize' => ['panels']]);
        $this->viewClass = 'Json';
    }

    /**
     * View the rendered content of a panel.
     *
     * `latest` can be used as the request id to view the panel of the most recent request.
     *
     * @param string|null $requestId The request id.
     * @param string|null $panel The panel name.
     * @return void
     * @throws NotFoundException
     */
    public function view($requestId = null, $panel = null)
    {
        if ($requestId === 'latest') {
            $recent = $this->storage->recent(1);
            $requestId = $recent ? $recent[0]['id'] : null;
        }
        $data = $requestId && $panel ? $this->storage->panel($requestId, $panel) : null;
        if (!$data) {
            throw new NotFoundException(__d('debug_kit', 'Panel not found.'));
        }
        $this->set('panel', $data + ['request_id' => $requestId]);
        $this->layout = false;
    }
}
