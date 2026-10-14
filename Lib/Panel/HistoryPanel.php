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

App::uses('DebugPanel', 'DebugKit.Lib');

/**
 * Provides debug information on previous requests.
 */
class HistoryPanel extends DebugPanel
{
    /**
     * Number of history elements to keep
     *
     * @var int
     */
    public $history = 5;

    /**
     * Constructor
     *
     * @param array $settings Array of settings.
     * @return \HistoryPanel
     */
    public function __construct($settings)
    {
        if (isset($settings['history'])) {
            $this->history = $settings['history'];
        }
    }

    /**
     * beforeRender callback function
     *
     * Lists the previous requests kept in the toolbar storage.
     *
     * @param Controller $controller The controller.
     * @return array contents for panel, with the `current` request and the previous `requests`.
     */
    public function beforeRender(Controller $controller)
    {
        if (!isset($controller->Toolbar) || !($controller->Toolbar instanceof ToolbarComponent)) {
            return [];
        }
        $toolbar = $controller->Toolbar;
        $current = [
            'id' => $toolbar->requestId,
            'url' => $controller->request->here(),
            'method' => $controller->request->method(),
            'status_code' => $controller->response->statusCode(),
            'content_type' => $controller->response->type(),
            'requested_at' => date('Y-m-d H:i:s', (int)((float)env('REQUEST_TIME_FLOAT') ?: time())),
        ];
        $requests = [];
        $storage = $toolbar->storage();
        if ($storage) {
            foreach ($storage->recent($this->history + 1) as $request) {
                if ($request['id'] !== $toolbar->requestId) {
                    $requests[] = $request;
                }
            }
        }

        return [
            'current' => $current,
            'requests' => array_slice($requests, 0, max(0, (int)$this->history)),
        ];
    }

    /**
     * Get the summary: number of ajax requests, updated by the toolbar.
     *
     * @param mixed $content The panel content.
     * @return string
     */
    public function summary($content = null)
    {
        return '0 xhr';
    }
}
