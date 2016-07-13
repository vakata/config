<?php
namespace vakata\kvstore\test;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
	protected static $storage = null;

	public static function setUpBeforeClass() {
	}
	public static function tearDownAfterClass() {
	}
	protected function setUp() {
	}
	protected function tearDown() {
	}

	public function testCreate() {
		$data = [ 'initial' => 1 ];
		self::$storage = new \vakata\kvstore\Storage($data);
		$this->assertEquals(1, self::$storage->get('initial'));
		$data['reference'] = 2;
		$this->assertEquals(2, self::$storage->get('reference'));
		$this->assertEquals(true, self::$storage->set('reference2', 3));
		$this->assertEquals(3, $data['reference2']);
	}
	/**
	 * @depends testCreate
	 */
	public function testSet() {
		$this->assertEquals('value', self::$storage->set('simple', 'value'));
		$this->assertEquals('value', self::$storage->get('simple'));
		$this->assertEquals('overwrite', self::$storage->set('simple', 'overwrite'));
		$this->assertEquals('overwrite', self::$storage->get('simple'));
		$this->assertEquals(['nested' => 'value'], self::$storage->set('complex', ['nested' => 'value']));
		$this->assertEquals(['nested' => 'value'], self::$storage->get('complex'));
		$this->assertEquals('value', self::$storage->get('complex.nested'));
		$this->assertEquals(['nested' => ['overwrite' => 'ok']], self::$storage->set('complex', ['nested' => ['overwrite' => 'ok']]));
		$this->assertEquals('ok', self::$storage->get('complex.nested.overwrite'));
	}
	/**
	 * @depends testSet
	 */
	public function testGet() {
		$this->assertEquals('ok', self::$storage->get('complex/nested/overwrite', null, '/'));
		$this->assertEquals(null, self::$storage->get('complex.nested.overwrite', null, '/'));
		$this->assertEquals('default', self::$storage->get('complex.nested.overwrite2', 'default'));
	}
	/**
	 * @depends testSet
	 */
	public function testDel() {
		$this->assertEquals(null, self::$storage->del('complex2'));
		$this->assertEquals(null, self::$storage->del('complex.nested2.nested2'));
		$this->assertEquals('ok', self::$storage->del('complex.nested.overwrite'));
		$this->assertEquals([], self::$storage->get('complex.nested'));
		$this->assertEquals(['nested'=>[]], self::$storage->del('complex'));
		$this->assertEquals(null, self::$storage->get('complex'));
	}
}
