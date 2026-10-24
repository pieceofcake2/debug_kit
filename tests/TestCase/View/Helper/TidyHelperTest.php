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

/**
 * Class TidyHelperTest
 */
class TidyHelperTest extends CakeTestCase
{
    /**
     * @var View
     */
    public $View;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->View = new View(new Controller());
    }

    /**
     * Test that report() processes the rendered view content when no html is given.
     *
     * @return void
     */
    public function testReportUsesViewContent(): void
    {
        $this->View->assign('content', '<p>content</p>');
        $Tidy = $this->getMockBuilder('TidyHelper')
            ->setConstructorArgs([$this->View])
            ->onlyMethods(['tidyErrors'])
            ->getMock();
        $Tidy->expects($this->once())
            ->method('tidyErrors')
            ->with('<p>content</p>')
            ->willReturn('');

        $result = $Tidy->report();
        $this->assertSame('<p>No markup errors found</p>', $result);
    }

    /**
     * Test that tidyErrors() puts every tag on its own line in the output.
     *
     * @return void
     */
    public function testTidyErrorsNormalizesOutput(): void
    {
        $Tidy = new TidyHelper($this->View);
        $out = '';
        $Tidy->tidyErrors("<p>  <b>bold</b>\n</p>", $out);

        $this->assertSame("<p>\n<b>bold</b>\n</p>", $out);
    }
}
