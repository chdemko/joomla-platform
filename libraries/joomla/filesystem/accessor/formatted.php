<?php

/**
 * @package     Joomla.Platform
 * @subpackage  FileSystem
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * A File system accessor for reading/writing formatted contents
 *
 * @package     Joomla.Platform
 * @subpackage  FileSystem
 *
 * @since       12.1
 */
abstract class JFilesystemAccessorFormatted
{
	/**
	 * Read data from a file
	 *
	 * @param   JFilesystemElementFile  $file    The file to be read.
	 * @param   string                  $format  The format string.
	 *
	 * @return  array|FALSE  The data read, or FALSE on failure.
	 *
	 * @link    http://php.net/manual/en/function.fscanf.php
	 *
	 * @since   12.1
	 */
	public static function read(JFilesystemElementFile $file, $format)
	{
		$v = fscanf($file->handle, $format);
		if (!is_array($v))
		{
			$file->valid = false;
			return false;
		}
		else
		{
			return $v;
		}
	}

	/**
	 * Write data to a file
	 *
	 * @param   JFilesystemElementFile  $file    The file to be written.
	 * @param   string                  $format  The format string.
	 *
	 * @return  int|FALSE  The number of bytes written, or FALSE on failure.
	 *
	 * @link    http://php.net/manual/en/function.fwrite.php
	 *
	 * @since   12.1
	 */
	public static function write(JFilesystemElementFile $file, $format)
	{
		$args = func_get_args();
		$args[0] = $file->handle;
		return call_user_func_array('fprintf', $args);
	}
}
