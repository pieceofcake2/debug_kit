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
 * Tests for the panel summaries displayed in the toolbar.
 */
class PanelSummaryTest extends CakeTestCase
{
    /**
     * Test that panels have no summary by default.
     *
     * @return void
     */
    public function testDefaultSummary(): void
    {
        $panel = new DebugPanel();
        $this->assertSame('', $panel->summary(['anything']));
    }

    /**
     * Test the timer summary.
     *
     * @return void
     */
    public function testTimerSummary(): void
    {
        $panel = new TimerPanel();
        $this->assertMatchesRegularExpression('#^\d+\.\d{2} s / [\d.]+ [KMG]?B$#', $panel->summary());
    }

    /**
     * Test the variables summary.
     *
     * @return void
     */
    public function testVariablesSummary(): void
    {
        $panel = new VariablesPanel();
        $controller = new Controller(new CakeRequest('/'));
        $controller->set(['one' => 1, 'two' => 2]);
        $this->assertSame('2', $panel->summary($panel->beforeRender($controller)));
        $this->assertSame('0', $panel->summary(null));
    }

    /**
     * Test the include summary.
     *
     * @return void
     */
    public function testIncludeSummary(): void
    {
        $panel = new IncludePanel();
        $content = ['core' => ['Model' => ['a', 'b']], 'app' => [], 'plugins' => ['DebugKit' => ['Lib' => ['c']]], 'paths' => ['x', 'y']];
        $this->assertSame('3', $panel->summary($content));
        $this->assertSame('0', $panel->summary(null));
    }
}
