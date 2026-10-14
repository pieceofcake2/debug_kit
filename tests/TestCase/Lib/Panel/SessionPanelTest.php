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

App::uses('SessionPanel', 'DebugKit.Lib/Panel');

/**
 * Class SessionPanelTest
 */
class SessionPanelTest extends CakeTestCase
{
    /**
     * Test that the session panel is empty when the Toolbar component is not loaded.
     *
     * @return void
     */
    public function testBeforeRenderWithoutToolbarComponent(): void
    {
        $panel = new SessionPanel();

        $this->assertSame([], $panel->beforeRender(new Controller()));
    }
}
