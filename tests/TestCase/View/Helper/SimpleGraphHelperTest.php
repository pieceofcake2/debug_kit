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

App::uses('View', 'View');
App::uses('SimpleGraphHelper', 'DebugKit.View/Helper');

/**
 * Class SimpleGraphHelperTest
 */
class SimpleGraphHelperTest extends CakeTestCase
{
    /**
     * @var SimpleGraphHelper
     */
    public $Graph;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Graph = new SimpleGraphHelper(new View(new Controller()));
    }

    /**
     * Test rendering a bar with the default settings.
     *
     * @return void
     */
    public function testBar(): void
    {
        $result = $this->Graph->bar(10, 50);

        $expected = [
            'div' => ['class' => 'debug-kit-graph-bar', 'style' => 'width: 350px;'],
            ['div' => [
                'class' => 'debug-kit-graph-bar-value',
                'style' => 'margin-left: 175px; width: 35px',
                'title' => 'Starting 50ms into the request, taking 10ms',
            ]],
            ' ',
            '/div',
            '/div',
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * Test rendering a percentage bar with custom settings.
     *
     * @return void
     */
    public function testBarPercentage(): void
    {
        $result = $this->Graph->bar(25, 10, ['max' => 50, 'width' => 200, 'valueType' => 'percentage']);

        $expected = [
            'div' => ['class' => 'debug-kit-graph-bar', 'style' => 'width: 200px;'],
            ['div' => [
                'class' => 'debug-kit-graph-bar-value',
                'style' => 'margin-left: 0px; width: 100px',
                'title' => 'Starting 10ms into the request, taking 25ms',
            ]],
            ' ',
            '/div',
            '/div',
        ];
        $this->assertTags($result, $expected);
    }
}
