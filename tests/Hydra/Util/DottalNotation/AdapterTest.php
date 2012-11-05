<?php
require_once 'PHPUnit/Autoload.php';
require_once '/home/henrique/public/HydraFramework/library/Hydra/Loader.php';
$loader = new Hydra_Loader('/home/henrique/public/HydraFramework/library/');

class Hydra_Util_DottalNotation_AdapterTest extends PHPUnit_Framework_TestCase {
	private $adapter;

	public function setUp() {
		$this->adapter = new Hydra_Util_DottalNotation_Adapter();
	}

	public function testSetFromEmptyDepth1Ok() {
		$array = array();
		$this->adapter->set($array, 'a', 1);
		$this->assertEquals(1, $this->adapter->get($array, 'a'));
	}

	public function testSetFromEmptyDepth2Ok() {
		$array = array();
		$this->adapter->set($array, 'a.b', 1);
		$this->assertEquals(1, $this->adapter->get($array, 'a.b'));
	}

	public function testSetFromEmptyDepth5Ok() {
		$array = array();
		$this->adapter->set($array, 'a.b.c.d.e', 1);
		$expected = array(
			'a' => array(
				'b' => array(
					'c' => array(
						'd' => array (
							'e' => 1
						)
					)
				)
			)
		);

		$this->assertEquals($expected, $array);
	}

	public function testSetFromExistentDepth1Ok() {
		$array = array(
			'a' => 1,
			'b' => 2
		);
		$this->adapter->set($array, 'c', 3);
		$expected = array(
			'a' => 1,
			'b' => 2,
			'c' => 3
		);
		$this->assertEquals($expected, $array);
	}

	public function testSetFromExistentDepth2Ok() {
		$array = array(
			'a' => 1,
			'b' => 2
		);
		$this->adapter->set($array, 'a.c', 3);

		$expected = array(
			'a' => array(
				'c' => 3
			),
			'b' => 2
		);

		$this->assertEquals($expected, $array);
	}

	public function testRemoveDepth1Ok() {
		$array = array(
			'a' => array(
				'c' => 3
			),
			'b' => 2
		);
		$this->adapter->remove($array, 'a.c');

		$expected = array(
			'a' => array(),
			'b' => 2
		);

		$this->assertEquals($expected, $array);
	}

	public function testRemoveDepth2Ok() {
		$array = array(
			'a' => array(
				'c' => 3
			),
			'b' => 2
		);
		$this->adapter->remove($array, 'a');

		$expected = array(
			'b' => 2
		);

		$this->assertEquals($expected, $array);
	}

	public function testGetWithInvalidKey() {
		$this->setExpectedException('Hydra_Util_DottalNotation_Exception');

		$array = array(
			'a' => array(
				'c' => 3
			),
			'b' => 2
		);
		$expected = $array;

		$this->adapter->remove($array, 'b.c');

		$this->assertEquals($expected, $array);
	}
}