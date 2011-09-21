<?php
/**
 * @package		Joomla.UnitTest
 * @subpackage	filesystem
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license	 GNU General Public License version 2 or later; see LICENSE
 */

require_once JPATH_PLATFORM . '/joomla/filesystem/patcher.php';
require_once JPATH_PLATFORM . '/joomla/filesystem/path.php';

/**
 * A unit test class for JPatcher
 */
class JPatcherTest extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
	}

	/**
	 * JPatcher::add add a unified diff file to the patcher
	 */
	public function testAdd()
	{
		$diff = file_get_contents(__DIR__ . '/patcher/lao2tzu.diff');
		$patcher = new JPatcher(array('root' => __DIR__ . '/patcher'));
		$this->assertTrue(
			$patcher->add($diff),
			'Line:'.__LINE__.' The patcher cannot add the unified diff string.'
		);
		$this->assertAttributeEquals(
			array(
				__DIR__ . '/patcher/lao' => array(
					'The Way that can be told of is not the eternal Way;',
					'The name that can be named is not the eternal name.',
					'The Nameless is the origin of Heaven and Earth;',
					'The Named is the mother of all things.',
					'Therefore let there always be non-being,',
					'  so we may see their subtlety,',
					'And let there always be being,',
					'  so we may see their outcome.',
					'The two are the same,',
					'But after they are produced,',
					'  they have different names.',
					'',
					'',
				),
			),
			'sources',
			$patcher,
			'Line:'.__LINE__.' The patcher cannot add the unified diff string.'
		);
		$this->assertAttributeEquals(
			array(
				__DIR__ . '/patcher/tzu' => array(
					'The Nameless is the origin of Heaven and Earth;',
					'The named is the mother of all things.',
					'',
					'Therefore let there always be non-being,',
					'  so we may see their subtlety,',
					'And let there always be being,',
					'  so we may see their outcome.',
					'The two are the same,',
					'But after they are produced,',
					'  they have different names.',
					'They both may be called deep and profound.',
					'Deeper and more profound,',
					'The door of all subtleties!',
					'',
					'',
				),
			),
			'destinations',
			$patcher,
			'Line:'.__LINE__.' The patcher cannot add the unified diff string.'
		);
		$this->assertAttributeEquals(
			array(),
			'removals',
			$patcher,
			'Line:'.__LINE__.' The patcher cannot add the unified diff string.'
		);
		$patcher->reset();
		$diff = file_get_contents(__DIR__ . '/patcher/notexist2tzu.diff');
		$this->setExpectedException('Exception','JLIB_FILESYSTEM_PATCHER_UNEXISING_SOURCE');
		$patcher->add($diff);
		
	}

	/**
	 * JPatcher::reset reset the patcher to its initial state
	 */
	public function testReset()
	{
		$diff = file_get_contents(__DIR__ . '/patcher/lao2tzu.diff');
		$patcher = new JPatcher(array('root' => __DIR__ . '/patcher'));
		$patcher->add($diff);
		$patcher->reset();
		$this->assertAttributeEquals(
			array(),
			'sources',
			$patcher,
			'Line:'.__LINE__.' The patcher has not been reset.'
		);
		$this->assertAttributeEquals(
			array(),
			'destinations',
			$patcher,
			'Line:'.__LINE__.' The patcher has not been reset.'
		);
		$this->assertAttributeEquals(
			array(),
			'removals',
			$patcher,
			'Line:'.__LINE__.' The patcher has not been reset.'
		);
	}

	/**
	 * JPatcher::patch apply the patches
	 */
	public function testPatch()
	{
		$diff = file_get_contents(__DIR__ . '/patcher/lao2tzu.diff');
		$patcher = new JPatcher(array('root' => __DIR__ . '/patcher'));
		$patcher->add($diff);
		$this->assertEquals(
			1,
			$patcher->patch(),
			'Line:'.__LINE__.' The patcher did not patch one file.'
		);
		$tzu = file_get_contents(__DIR__ . '/patcher/tzu');
		$expected = file_get_contents(__DIR__ . '/patcher/expected');
		$this->assertEquals(
			$tzu,
			$expected,
			'Line:'.__LINE__.' The patcher did not succeed in patching.'
		);
	}

	protected function tearDown()
	{
		if (file_exists(__DIR__ . '/patcher/tzu')) {
			unlink(__DIR__ . '/patcher/tzu');
		}
	}
}

