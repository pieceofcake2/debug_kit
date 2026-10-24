<?php
/**
 * SqlLogPanelTest
 *
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class SqlLogPanelTest
 *
 * @since         DebugKit 2.1
 */
class SqlLogPanelTest extends CakeTestCase
{
    /**
     * fixtures.
     *
     * @var array
     */
    public $fixtures = ['core.article'];

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->panel = new SqlLogPanel();
    }

    /**
     * test the parsing of source list.
     *
     * @return void
     */
    public function testBeforeRender(): void
    {
        $Article = ClassRegistry::init('Article');
        $Article->find('first', ['conditions' => ['Article.id' => 1]]);

        $controller = new Controller();
        $result = $this->panel->beforeRender($controller);

        $this->assertTrue(isset($result['connections'][$Article->useDbConfig]));
        $this->assertTrue(isset($result['threshold']));
    }

    /**
     * Test the summary and that the toolbar storage connection is not listed.
     *
     * @return void
     */
    public function testSummary(): void
    {
        $Article = ClassRegistry::init('Article');
        $db = $Article->getDataSource();
        $db->fullDebug = true;
        $Article->find('first');

        $result = $this->panel->beforeRender(new Controller());
        $this->assertArrayNotHasKey('debug_kit', $result['connections']);
        $this->assertMatchesRegularExpression('#^\d+ / \d+ ms$#', $this->panel->summary($result));
        $this->assertSame('0', $this->panel->summary([]));
        $this->assertSame('0', $this->panel->summary(['connections' => ['debug_kit' => false]]));

        $panel = new SqlLogPanel(['connection' => 'other']);
        $this->assertSame('other', $panel->storageConnection);
    }
}
