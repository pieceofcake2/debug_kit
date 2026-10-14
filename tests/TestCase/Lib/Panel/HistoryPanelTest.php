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

App::uses('HistoryPanel', 'DebugKit.Lib/Panel');

/**
 * Class HistoryPanelTest
 */
class HistoryPanelTest extends CakeTestCase
{
    /**
     * Test that the history panel is empty when the Toolbar component is not loaded.
     *
     * @return void
     */
    public function testBeforeRenderWithoutToolbarComponent(): void
    {
        $panel = new HistoryPanel([]);

        $this->assertSame([], $panel->beforeRender(new Controller()));
    }

    /**
     * Test the summary.
     *
     * @return void
     */
    public function testSummary(): void
    {
        $panel = new HistoryPanel(['history' => 3]);
        $this->assertSame(3, $panel->history);
        $this->assertSame('0 xhr', $panel->summary());
    }
}
