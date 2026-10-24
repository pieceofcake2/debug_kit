<?php
/**
 * CakeFirePHP Test Case
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 **/

require_once CakePlugin::path('DebugKit') . 'tests' . DS . 'TestCase' . DS . 'TestFireCake.php';

/**
 * Test Case For FireCake
 *
 * @since         DebugKit 0.1
 */
class FireCakeTest extends CakeTestCase
{
    /**
     * setup test
     *
     * Fill FireCake with TestFireCake instance.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->firecake = FireCake::getInstance('TestFireCake');
        TestFireCake::reset();
    }

    /**
     * Reset the FireCake counters and headers.
     *
     * @return void
     */
    public function tearDown(): void
    {
        TestFireCake::reset();
    }

    /**
     * Test getInstance cheat.
     *
     * If this fails the rest of the test is going to fail too.
     *
     * @return void
     */
    public function testGetInstanceOverride(): void
    {
        $instance = FireCake::getInstance();
        $instance2 = FireCake::getInstance();
        $this->assertSame($instance, $instance2);
        $this->assertIsA($instance, 'FireCake');
        $this->assertIsA($instance, 'TestFireCake', 'Stored instance is not a copy of TestFireCake, test case is broken.');
    }

    /**
     * Test setOptions
     *
     * @return void
     */
    public function testSetOptions(): void
    {
        FireCake::setOptions(['includeLineNumbers' => false]);
        $this->assertEquals($this->firecake->options['includeLineNumbers'], false);
    }

    /**
     * Test Log()
     *
     * @return void
     */
    public function testLog(): void
    {
        FireCake::setOptions(['includeLineNumbers' => false]);
        FireCake::log('Testing');
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-Protocol-1']));
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Plugin-1']));
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Structure-1']));
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-Index'], 1);
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '26|[{"Type":"LOG"},"Testing"]|');

        FireCake::log('Testing', 'log-info');
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-2'], '45|[{"Type":"LOG","Label":"log-info"},"Testing"]|');
    }

    /**
     * Test info()
     *
     * @return void
     */
    public function testInfo(): void
    {
        FireCake::setOptions(['includeLineNumbers' => false]);
        FireCake::info('I have information');
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-Protocol-1']));
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Plugin-1']));
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Structure-1']));
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-Index'], 1);
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '38|[{"Type":"INFO"},"I have information"]|');

        FireCake::info('I have information', 'info-label');
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-2'], '59|[{"Type":"INFO","Label":"info-label"},"I have information"]|');
    }

    /**
     * Test info()
     *
     * @return void
     */
    public function testWarn(): void
    {
        FireCake::setOptions(['includeLineNumbers' => false]);
        FireCake::warn('A Warning');
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-Protocol-1']));
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Plugin-1']));
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Structure-1']));
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-Index'], 1);
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '29|[{"Type":"WARN"},"A Warning"]|');

        FireCake::warn('A Warning', 'Bzzz');
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-2'], '44|[{"Type":"WARN","Label":"Bzzz"},"A Warning"]|');
    }

    /**
     * Test error()
     *
     * @return void
     */
    public function testError(): void
    {
        FireCake::setOptions(['includeLineNumbers' => false]);
        FireCake::error('An error');
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-Protocol-1']));
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Plugin-1']));
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Structure-1']));
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-Index'], 1);
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '29|[{"Type":"ERROR"},"An error"]|');

        FireCake::error('An error', 'wonky');
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-2'], '45|[{"Type":"ERROR","Label":"wonky"},"An error"]|');
    }

    /**
     * Test dump()
     *
     * @return void
     */
    public function testDump(): void
    {
        FireCake::dump('mydump', ['one' => 1, 'two' => 2]);
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-2-1-1'], '28|{"mydump":{"one":1,"two":2}}|');
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Structure-2']));
    }

    /**
     * Test table() generation
     *
     * @return void
     */
    public function testTable(): void
    {
        $table[] = ['Col 1 Heading', 'Col 2 Heading'];
        $table[] = ['Row 1 Col 1', 'Row 1 Col 2'];
        $table[] = ['Row 2 Col 1', 'Row 2 Col 2'];
        $table[] = ['Row 3 Col 1', 'Row 3 Col 2'];
        FireCake::table('myTrace', $table);
        $expected = '162|[{"Type":"TABLE","Label":"myTrace"},[["Col 1 Heading","Col 2 Heading"],["Row 1 Col 1","Row 1 Col 2"],["Row 2 Col 1","Row 2 Col 2"],["Row 3 Col 1","Row 3 Col 2"]]]|';
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], $expected);
    }

    /**
     * TestStringEncoding
     *
     * @return void
     */
    public function testStringEncode(): void
    {
        $vars = [1, 2, 3];
        $result = $this->firecake->stringEncode($vars);
        $this->assertEquals($result, [1, 2, 3]);

        $this->firecake->setOptions(['maxArrayDepth' => 3]);
        $deep = [1 => [2 => [3]]];
        $result = $this->firecake->stringEncode($deep);
        $this->assertEquals($result, [1 => [2 => '** Max Array Depth (3) **']]);
    }

    /**
     * Test object encoding
     *
     * @return void
     */
    public function testStringEncodeObjects(): void
    {
        $obj = FireCake::getInstance();
        $result = $this->firecake->stringEncode($obj);

        $this->assertTrue(is_array($result));
        $this->assertTrue($result['_defaultOptions']['useNativeJsonEncode']);
        $this->assertEquals($result['_encodedObjects'][0], '** Recursion (TestFireCake) **');
    }

    /**
     * Test that maxObjectDepth limits nested objects
     *
     * @return void
     */
    public function testStringEncodeMaxObjectDepth(): void
    {
        $this->firecake->setOptions(['maxObjectDepth' => 3, 'maxArrayDepth' => 20]);

        $deep = new stdClass();
        $deep->child = new stdClass();
        $deep->child->child = new stdClass();
        $deep->child->child->child = new stdClass();

        $result = $this->firecake->stringEncode($deep);
        $expected = [
            '__className' => 'stdClass',
            'child' => [
                '__className' => 'stdClass',
                'child' => '** Max Object Depth (3) **',
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that object and array depths are tracked separately
     *
     * @return void
     */
    public function testStringEncodeMixedDepth(): void
    {
        $this->firecake->setOptions(['maxObjectDepth' => 3, 'maxArrayDepth' => 3]);

        $object = new stdClass();
        $object->list = [1 => ['a', 'b']];
        $result = $this->firecake->stringEncode($object);
        $this->assertEquals(['__className' => 'stdClass', 'list' => [1 => ['a', 'b']]], $result);

        $inner = new stdClass();
        $inner->value = 'x';
        $outer = new stdClass();
        $outer->items = [[$inner]];
        $result = $this->firecake->stringEncode($outer);
        $expected = [
            '__className' => 'stdClass',
            'items' => [[['__className' => 'stdClass', 'value' => 'x']]],
        ];
        $this->assertEquals($expected, $result);

        $wrapper = new stdClass();
        $wrapper->inner = $outer;
        $result = $this->firecake->stringEncode($wrapper);
        $expected = [
            '__className' => 'stdClass',
            'inner' => [
                '__className' => 'stdClass',
                'items' => [['** Max Object Depth (3) **']],
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test trace()
     *
     * @return void
     */
    public function testTrace(): void
    {
        FireCake::trace('myTrace');
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-Protocol-1']));
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Plugin-1']));
        $this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Structure-1']));
        $dump = $this->firecake->sentHeaders['X-Wf-1-1-1-1'];
        $this->assertMatchesRegularExpression('/"Message":"myTrace"/', $dump);
        $this->assertMatchesRegularExpression('/"Trace":\[/', $dump);
    }

    /**
     * Test enabling and disabling of FireCake output
     *
     * @return void
     */
    public function testEnableDisable(): void
    {
        FireCake::disable();
        FireCake::trace('myTrace');
        $this->assertEmpty($this->firecake->sentHeaders);

        FireCake::enable();
        FireCake::trace('myTrace');
        $this->assertFalse(empty($this->firecake->sentHeaders));
    }

    /**
     * Test correct line continuation markers on multi line headers.
     *
     * @return void
     */
    public function testMultiLineOutput(): void
    {
        FireCake::trace('myTrace');
        $this->assertGreaterThan(1, $this->firecake->sentHeaders['X-Wf-1-Index']);
        $header = $this->firecake->sentHeaders['X-Wf-1-1-1-1'];
        $this->assertEquals(substr($header, -2), '|\\');

        $endIndex = $this->firecake->sentHeaders['X-Wf-1-Index'];
        $header = $this->firecake->sentHeaders['X-Wf-1-1-1-' . $endIndex];
        $this->assertEquals(substr($header, -1), '|');
    }

    /**
     * Test inclusion of line numbers
     *
     * @return void
     */
    public function testIncludeLineNumbers(): void
    {
        FireCake::setOptions(['includeLineNumbers' => true]);
        FireCake::info('Testing');
        $result = $this->firecake->sentHeaders['X-Wf-1-1-1-1'];
        $this->assertMatchesRegularExpression('/"File"\:".*FireCakeTest.php/', $result);
        $this->assertMatchesRegularExpression('/"Line"\:\d+/', $result);
    }

    /**
     * Test Group messages
     *
     * @return void
     */
    public function testGroup(): void
    {
        FireCake::setOptions(['includeLineNumbers' => false]);
        FireCake::group('test');
        FireCake::info('my info');
        FireCake::groupEnd();
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '63|[{"Collapsed":"true","Type":"GROUP_START","Label":"test"},null]|');
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-3'], '27|[{"Type":"GROUP_END"},null]|');
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-Index'], 3);
    }

    /**
     * Test fb() parameter parsing
     *
     * @return void
     */
    public function testFbParameterParsing(): void
    {
        FireCake::setOptions(['includeLineNumbers' => false]);
        FireCake::fb('Test');
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '23|[{"Type":"LOG"},"Test"]|');

        FireCake::fb('Test', 'warn');
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-2'], '24|[{"Type":"WARN"},"Test"]|');

        FireCake::fb('Test', 'Custom label', 'warn');
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-3'], '47|[{"Type":"WARN","Label":"Custom label"},"Test"]|');

        $this->expectExceptionMessage('Incorrect parameter count for FireCake::fb()');

        try {
            set_error_handler(static function (int $errno, string $errstr): void {
                throw new Exception($errstr, $errno);
            }, E_USER_WARNING);

            FireCake::fb('Test', 'Custom label', 'warn', 'more parameters');
        } finally {
            restore_error_handler();
        }

        $this->assertFalse(FireCake::fb('Test', 'Custom label', 'warn', 'more parameters'));

        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-Index'], 3);
    }

    /**
     * Test defaulting to log if incorrect message type is used
     *
     * @return void
     */
    public function testIncorrectMessageType(): void
    {
        FireCake::setOptions(['includeLineNumbers' => false]);
        FireCake::fb('Hello World', 'foobared');
        $this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '30|[{"Type":"LOG"},"Hello World"]|');
    }

    /**
     * Test DetectClientExtension.
     *
     * @return void
     */
    public function testDetectClientExtension(): void
    {
        $back = env('HTTP_USER_AGENT');
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.0.4) Gecko/2008102920 Firefox/3.0.4 FirePHP/0.2.1';
        $this->assertTrue(FireCake::detectClientExtension());

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.0.4) Gecko/2008102920 Firefox/3.0.4 FirePHP/0.0.4';
        $this->assertFalse(FireCake::detectClientExtension());

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.0.4) Gecko/2008102920 Firefox/3.0.4';
        $this->assertFalse(FireCake::detectClientExtension());
        $_SERVER['HTTP_USER_AGENT'] = $back;
    }

    /**
     * Test of Non Native JSON encoding.
     *
     * @return void
     */
    public function testNonNativeEncoding(): void
    {
        FireCake::setOptions(['useNativeJsonEncode' => false]);
        $json = FireCake::jsonEncode(['one' => 1, 'two' => 2]);
        $this->assertEquals($json, '{"one":1,"two":2}');

        $json = FireCake::jsonEncode([1, 2, 3]);
        $this->assertEquals($json, '[1,2,3]');

        $json = FireCake::jsonEncode(FireCake::getInstance());
        $this->assertMatchesRegularExpression('/"options"\:\{"maxObjectDepth"\:\d*,/', $json);
    }
}
