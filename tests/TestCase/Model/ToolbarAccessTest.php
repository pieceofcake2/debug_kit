<?php
/**
 * DebugKit ToolbarAccess Model Test case
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 **/

/**
 * Test case for ToolbarAccess model
 *
 * @since         DebugKit 1.3
 */
class ToolbarAccessTest extends CakeTestCase
{
    /**
     * Included fixtures
     *
     * @var array
     */
    public $fixtures = ['core.post'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Model = new ToolbarAccess();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->Model);
    }

    /**
     * test that explain query returns arrays of query information.
     *
     * @return void
     */
    public function testExplainQuery(): void
    {
        $Post = new CakeTestModel(['table' => 'posts', 'alias' => 'Post']);
        $db = $Post->getDataSource();
        $sql = 'SELECT * FROM ' . $db->fullTableName('posts') . ';';
        $result = $this->Model->explainQuery($Post->useDbConfig, $sql);

        $this->assertTrue(is_array($result));
        $this->assertFalse(empty($result));
    }
}
