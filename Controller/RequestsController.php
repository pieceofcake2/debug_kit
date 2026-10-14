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

App::uses('DebugKitController', 'DebugKit.Controller');

/**
 * Renders the toolbar of a recorded request.
 *
 * The page is loaded into an iframe by `webroot/js/inject-iframe.js`.
 */
class RequestsController extends DebugKitController
{
    /**
     * View the toolbar of a request.
     *
     * @param string|null $id The request id.
     * @return void
     * @throws NotFoundException
     */
    public function view($id = null)
    {
        $toolbar = $id ? $this->storage->read($id) : null;
        if (!$toolbar) {
            throw new NotFoundException(__d('debug_kit', 'Request not found.'));
        }
        $this->set('toolbar', $toolbar);
        $this->layout = 'DebugKit.toolbar';
    }
}
