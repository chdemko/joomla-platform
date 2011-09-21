<?php
/**
 * @package     Joomla.Platform
 * @subpackage  FileSystem
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 *
 * @note        This has been derived from the PhpPatcher version 0.1.1 done by legolas558 on http://sourceforge.net/projects/phppatcher/
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.file');
jimport('joomla.methods');

define('_PHPP_INVALID_INPUT', 'Invalid input');
define('_PHPP_UNEXPECTED_EOF', 'Unexpected end of file');
define('_PHPP_UNEXPECTED_ADD_LINE', 'Unexpected add line at line %d');
define('_PHPP_UNEXPECTED_REMOVE_LINE', 'Unexpected remove line at line %d');
define('_PHPP_INVALID_DIFF', 'Invalid unified diff block');
define('_PHPP_FAILED_VERIFY', 'Failed source verification of file %s at line %d');

/**
 * A Unified Diff Format Patcher class
 *
 * @package     Joomla.Platform
 * @subpackage  FileSystem
 * @since       11.3
 */
class JPatcher {
	protected $root;
	protected $newline;
	protected $sources = array();
	protected $destinations = array();
	protected $removals = array();
	var $msg;
	
	/**
	 * Constructor
	 *
	 * @param   array   $options	An associative array used to create the patcher. Possible keys are
	 *	-'root' for defining the root of the patching process
	 *	-'newline' for defining the newline used ("\n", "\r", "\r\n") 
	 *
	 * @since   11.3
	 */
	public function __construct($options = array())
	{
		$this->root = isset($options['root']) ? $options['root'] : JPATH_ROOT . '/';
		$this->newline = isset($options['newline']) ? $options['newline'] : "\n";
	}

	/**
	 * Get the lines of a source file
	 *
	 * @param   string   $src		The path of a file
	 *
	 * @return  array	The lines of the source file
	 */
	protected function &getSource($src)
	{
		if (!isset($this->sources[$src])) {
			if (is_readable($src)) {
				$this->sources[$src] = $this->splitLines(file_get_contents($src));
			}
			else {
				$this->sources[$src] = null;
			}
		}
		return $this->sources[$src];
	}
	
	/**
	 * Get the lines of a destination file
	 *
	 * @param   string   $dst		The path of a destination file
	 * @param   string   $src		The path of a source file
	 *
	 * @return  array	The lines of the destination file
	 */
	protected function &getDestination($dst, $src)
	{
		if (!isset($this->destinations[$dst])) {
			$this->destinations[$dst] = $this->getSource($src);
		}
		return $this->destinations[$dst];
	}

	/**
	 * Separate CR or CRLF lines
	 *
	 * @param   string   $data		Input string
	 *
	 * @return  array	The lines of the inputdestination file
	 */
	protected function &splitLines(&$data)
	{
		$lines = preg_split('/(\r\n)|(\r)|(\n)/', $data);
		return $lines;
	}
	
	/**
	 * Add a unified diff string to the patcher
	 *
	 * @param   string   $udiff		Unified diff input string
	 *
	 * @return	boolean	True in case of success, false else
	 *
	 * @throw  Exception
	 */
	public function add($udiff) {
		// (1) Separate the input into lines
		$lines = $this->splitLines($udiff);
		if (!isset($lines)) {
			throw new Exception(JText::_('JLIB_FILESYSTEM_PATCHER_INVALID_INPUT'));
		}
		unset($udiff);
	
		$line = current($lines);
		do {
			if (strlen($line)<5)
				continue;
			// start recognition when a new diff block is found
			if (substr($line, 0, 4)!='--- ')
				continue;
			$p = strpos($line, "\t", 4);
			if ($p===false)	{
				$p = strlen($line);
			}
			$src = $this->root.substr($line, 4, $p-4);
			$line = next($lines);
			if (!isset($line)) {
				throw new Exception(JText::_('JLIB_FILESYSTEM_PATCHER_UNEXPECTED_EOF'));
			}
			if (substr($line, 0, 4)!='+++ ') {
				throw new Exception(JText::_('JLIB_FILESYSTEM_PATCHER_INVALID_DIFF'));
			}
			$p = strpos($line, "\t", 4);
			if ($p===false)	{
				$p = strlen($line);
			}
			$dst = $this->root.substr($line, 4, $p-4);
			
			$line = next($lines);
			if (!isset($line)) {
				throw new Exception(JText::_('JLIB_FILESYSTEM_PATCHER_UNEXPECTED_EOF'));
			}
			
			$done=0;
			while (preg_match('/@@ -(\\d+)(,(\\d+))?\\s+\\+(\\d+)(,(\\d+))?\\s+@@($)/A', $line, $m)) {
			
				if ($m[3]==='') {
					$src_size = 1;
				}
				else {
					$src_size = (int)$m[3];
				}
				if ($m[6]==='') {
					$dst_size = 1;
				}
				else {
					$dst_size = (int)$m[6];
				}
				if (!$this->apply($lines, $src, $dst, (int)$m[1], $src_size, (int)$m[4], $dst_size)) {
					return false;
				}
				$done++;
				$line = next($lines);
				if ($line === FALSE) {
					break 2;
				}
			}
			if ($done==0) {
				throw new Exception(JText::_('JLIB_FILESYSTEM_PATCHER_INVALID_DIFF'));
			}
			
		} while (FALSE !== ($line = next($lines)));
		
		//NOTE: previously opened files are still cached
		return true;
	}
	
	public function reset()
	{
		$this->sources = array();
		$this->destinations = array();
		$this->removals = array();
	}
	
	public function patch()
	{
		// Initialize the counter
		$done = 0;

		// patch each destination file
		foreach($this->destinations as $file => $content) {
			if (JFile::write($file, implode($this->newline, $content))) {
				if (isset($this->source[$file])) {
					$this->source[$file] = $content;
				}
				$done++;
			}
		}

		// remove each removed file
		foreach($this->removals as $file) {
			if (JFile::delete($file)) {
				if (isset($this->sources[$file])) {
					unset($this->sources[$file]);
				}
				$done++;
			}
		}

		// clear the destinations cache
		$this->destinations = array();

		// clear the removals
		$this->removals = array();
		return $done;
	}
	
	protected function apply(&$lines, $src, $dst, $src_line, $src_size, $dst_line, $dst_size) {
		$src_line--;
		$dst_line--;
		$line = next($lines);
		if ($line === false) {
			throw new Exception(JText::_('JLIB_FILESYSTEM_PATCHER_UNEXPECTED_EOF'));
		}
		$source = array();		// source lines (old file)
		$destin = array();		// new lines (new file)
		$src_left = $src_size;
		$dst_left = $dst_size;
		do {
			if (!isset($line{0})) {
				$source[] = '';
				$destin[] = '';
				$src_left--;
				$dst_left--;
				continue;
			}
			if ($line{0}=='-') {
				if ($src_left==0) {
					throw new Exception(JText::sprintf('JLIB_FILESYSTEM_PATCHER_REMOVE_LINE', key($lines)));
				}
				$source[] = substr($line, 1);
				$src_left--;
			}
			elseif ($line{0}=='+') {
				if ($dst_left==0) {
					throw new Exception(JText::sprintf('JLIB_FILESYSTEM_PATCHER_ADD_LINE', key($lines)));
				}
				$destin[] = substr($line, 1);
				$dst_left--;
			}
			else {
				if (!isset($line{1})) {
					$line = '';
				}
				else if ($line{0}=='\\') {
					if ($line=='\\ No newline at end of file') {
						continue;
					}
				}
				else {
					$line = substr($line, 1);
				}
				$source[] = $line;
				$destin[] = $line;
				$src_left--;
				$dst_left--;
			}
			
			if (($src_left==0) && ($dst_left==0)) {
				// now apply the patch, finally!
				if ($src_size>0) {
					$src_lines =& $this->getSource($src);
					if (!isset($src_lines)) {
						return false;
					}
				}
				if ($dst_size>0) {
					if ($src_size>0) {
						$dst_lines =& $this->getDestination($dst, $src);
						if (!isset($dst_lines)) {
							return false;
						}
						$src_bottom=$src_line+count($source);
						$dst_bottom=$dst_line+count($destin);
						
						for ($l=$src_line;$l<$src_bottom;$l++) {
							if ($src_lines[$l]!=$source[$l-$src_line]) {
								throw new Exception(JText::sprintf('JLIB_FILESYSTEM_PATCHER_FAILED_VERIFY', $src, $l));
							}
						}
						array_splice($dst_lines, $dst_line, count($source), $destin);
					} else
						$this->destinations[$dst] = $destin;
				} else
					$this->removals[] = $src;
				
				return true;
			}
		} while (false !== ($line = next($lines)));
		throw new Exception(JText::_('JLIB_FILESYSTEM_PATCHER_UNEXPECTED_EOF'));
	}
}

