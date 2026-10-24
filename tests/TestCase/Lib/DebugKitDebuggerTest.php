<?php
/**
 * DebugKit Debugger Test Case File
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         debug_kit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 **/

require_once CakePlugin::path('DebugKit') . 'tests' . DS . 'TestCase' . DS . 'TestFireCake.php';

/**
 * Test case for the DebugKitDebugger
 *
 * @since         debug_kit 0.1
 */
class DebugKitDebuggerTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('log', false);
        $this->firecake = FireCake::getInstance('TestFireCake');
        TestFireCake::reset();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Configure::write('log', true);
        DebugTimer::clear();
        TestFireCake::reset();
    }

    /**
     * test output switch to firePHP
     *
     * @return void
     */
    public function testOutput(): void
    {
        Debugger::getInstance('DebugKitDebugger');
        Debugger::addFormat('fb', ['callback' => 'DebugKitDebugger::fireError']);
        Debugger::outputAs('fb');

        set_error_handler('ErrorHandler::handleError');
        // phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
        $foo .= '';
        restore_error_handler();

        $result = $this->firecake->sentHeaders;

        $this->assertMatchesRegularExpression('/GROUP_START/', $result['X-Wf-1-1-1-1']);
        $this->assertMatchesRegularExpression('/ERROR/', $result['X-Wf-1-1-1-2']);
        $this->assertMatchesRegularExpression('/GROUP_END/', $result['X-Wf-1-1-1-4']);

        Debugger::getInstance('Debugger');
        Debugger::outputAs('html');
    }
}
