<?php
/**
 * DebugKit Log Panel Test Cases
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 **/

App::uses('LogPanel', 'DebugKit.Lib/Panel');

/**
 * Class LogPanelTest
 */
class LogPanelTest extends CakeTestCase
{
    /**
     * set up
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->panel = new LogPanel();
    }

    /**
     * Test that logging configs are created.
     *
     * @return void
     */
    public function testConstructor(): void
    {
        $result = CakeLog::configured();
        $this->assertContains('debug_kit_log_panel', $result);
        $this->assertTrue(count($result) > 1, 'Default loggers were not added.');
    }

    /**
     * testBeforeRender
     *
     * @return void
     */
    public function testBeforeRender(): void
    {
        $controller = new Controller();

        CakeLog::write('error', 'Test');

        $result = $this->panel->beforeRender($controller);
        $this->assertInstanceOf('DebugKitLog', $result);
        $this->assertTrue(isset($result->logs));
        $this->assertCount(1, $result->logs['error']);
    }

    /**
     * Test the summary.
     *
     * @return void
     */
    public function testSummary(): void
    {
        $this->assertSame('0', $this->panel->summary(null));
        $log = new DebugKitLog(['panel' => $this->panel]);
        $log->write('error', 'one');
        $log->write('debug', 'two');
        $log->write('debug', 'three');
        $this->assertSame('3', $this->panel->summary($log));
    }
}
