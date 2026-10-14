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

App::uses('Controller', 'Controller');
App::uses('DebugKitStorage', 'DebugKit.Lib/Storage');

/**
 * Base controller of the toolbar endpoints.
 *
 * The endpoints extend Controller directly (not AppController), so that application
 * components such as Auth or Security do not interfere with the toolbar, and the
 * toolbar requests are not recorded themselves.
 */
class DebugKitController extends Controller
{
    /**
     * No models.
     *
     * @var array|false
     */
    public $uses = false;

    /**
     * Helpers
     *
     * @var array
     */
    public $helpers = ['Html'];

    /**
     * The toolbar storage, resolved in beforeFilter().
     *
     * @var DebugKitStorageInterface|null
     */
    public $storage;

    /**
     * Only allow access when debug is enabled (or the toolbar is force enabled
     * with `Configure::write('DebugKit.forceEnable', true)`).
     *
     * @return void
     * @throws NotFoundException
     */
    public function beforeFilter()
    {
        if (!Configure::read('debug') && !Configure::read('DebugKit.forceEnable')) {
            throw new NotFoundException(__d('debug_kit', 'Not available without debug mode on.'));
        }
        $this->response->header('Cache-Control', 'no-store');
        if ($this->storage === null) {
            $this->storage = DebugKitStorage::get();
        }
        if (!$this->storage) {
            throw new NotFoundException(__d('debug_kit', 'The toolbar storage is not available.'));
        }
    }
}
