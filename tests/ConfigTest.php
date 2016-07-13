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
		self::$storage = new \vakata\config\Config($data);
		$this->assertEquals(1, self::$storage->get('initial'));
		$data['reference'] = 2;
		$this->assertEquals(null, self::$storage->get('reference'));
	}
	/**
	 * @depends testCreate
	 */
	public function testSet() {
		$this->assertEquals('value', self::$storage->set('simple', 'value'));
		$this->assertEquals('value', self::$storage->get('simple'));
		$this->assertEquals('overwrite', self::$storage->set('simple', 'overwrite'));
		$this->assertEquals('overwrite', self::$storage->get('simple'));
	}
	/**
	 * @depends testSet
	 */
	public function testFile() {
		self::$storage->fromFile(__DIR__ . '/test.env');
		$this->assertEquals('config', self::$storage->get('TEST'));
		$this->assertEquals(1, self::$storage->get('TESTINT1'));
		$this->assertEquals('1', self::$storage->get('TESTINT2'));
		$this->assertEquals(__DIR__ . '/1', self::$storage->get('REPLACE1'));
		$this->assertEquals(__DIR__ . '/1/2', self::$storage->get('REPLACE2'));
		$this->assertEquals('${UNDEF}/3', self::$storage->get('REPLACE3'));
	}
	public function testDir() {
		self::$storage->fromDir(__DIR__ . '/test');
		$this->assertEquals('1', self::$storage->get('VAL1'));
		$this->assertEquals(2, self::$storage->get('VAL2'));
		$this->assertEquals(null, self::$storage->get('VAL3'));
	}
	public function testDirDeep() {
		self::$storage->fromDir(__DIR__ . '/test', true);
		$this->assertEquals('1', self::$storage->get('VAL1'));
		$this->assertEquals(2, self::$storage->get('VAL2'));
		$this->assertEquals('3', self::$storage->get('VAL3'));
	}
	/**
	 * @depends testDirDeep
	 */
	public function testExport() {
		self::$storage->export();
		$this->assertEquals('1', $_SERVER['VAL1']);
		$this->assertEquals(2, constant('VAL2'));
		$this->assertEquals('2', $_ENV['VAL2']);
		$this->assertEquals('config', getenv('TEST'));
	}
	/**
	 * @depends testExport
	 */
	public function testExportOverwrite() {
		$this->assertEquals('1', $_SERVER['VAL1']);
		self::$storage->set('VAL1', 'overwrite');
		$this->assertEquals('overwrite', self::$storage->get('VAL1'));
		self::$storage->export();
		$this->assertEquals('1', $_SERVER['VAL1']);
		self::$storage->export(true);
		$this->assertEquals('overwrite', $_SERVER['VAL1']);
	}
	/**
	 * @depends testSet
	 */
	public function testDel() {
		$this->assertEquals('overwrite', self::$storage->del('simple'));
		$this->assertEquals(null, self::$storage->get('simple'));
	}
}
