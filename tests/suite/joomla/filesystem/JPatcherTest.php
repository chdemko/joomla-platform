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
		$patcher = new JPatcher(array('root' => __DIR__ . '/patcher'));
		$diff = file_get_contents(__DIR__ . '/patcher/lao2tzu.diff');
		$patcher->add($diff);
		$this->assertTrue(
			$patcher->add($diff),
			'Line:'.__LINE__.' The patcher cannot add the unified diff string.'
		);
		$patcher->patch();
	}

	/**
	 * JPatcher::reset reset the patcher to its initial state
	 */
	public function testReset()
	{
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	/**
	 * JPatcher::patch apply the patches
	 */
	public function testPatch()
	{
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	protected function tearDown()
	{
	}
}

