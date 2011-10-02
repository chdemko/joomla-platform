<?php
/**
 * @package     Joomla.Platform
 * @subpackage  FileSystem
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 *
 * @author		legolas558
 * @note        This has been derived from the PhpPatcher version 0.1.1 done by legolas558 on http://sourceforge.net/projects/phppatcher/
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.file');
jimport('joomla.factory');
jimport('joomla.methods');

// Loading the language file
JFactory::getLanguage()->load(
	'lib_joomla_filesystem_patcher',
	JPATH_PLATFORM . '/joomla/filesystem/meta',
	null,
	false,
	false
) ||
JFactory::getLanguage()->load(
	'lib_joomla_filesystem_patcher',
	JPATH_BASE,
	null,
	false,
	false
) ||
JFactory::getLanguage()->load(
	'lib_joomla_filesystem_patcher',
	JPATH_PLATFORM . '/joomla/filesystem/meta'
) ||
JFactory::getLanguage()->load(
	'lib_joomla_filesystem_patcher',
	JPATH_BASE
);

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
		$this->root = (isset($options['root']) ? $options['root'] : JPATH_ROOT) . '/';
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
		unset($udiff);
	
		$line = current($lines);
		do {
			// start recognition when a new diff block is found
			if (!preg_match('/^---\\s+(\\S+)\s+\\d{1,4}-\\d{1,2}-\\d{1,2}\\s+\\d{1,2}:\\d{1,2}:\\d{1,2}(\\.\\d+)?\\s+(\+|-)\\d{4}/A', $line, $m)) {
				continue;
			}
			$src = $this->root.$m[1];
			$line = next($lines);
			if (!isset($line)) {
				throw new Exception(JText::_('JLIB_FILESYSTEM_PATCHER_UNEXPECTED_EOF'));
			}
			if (!preg_match('/^\\+\\+\\+\\s+(\\S+)\s+\\d{1,4}-\\d{1,2}-\\d{1,2}\\s+\\d{1,2}:\\d{1,2}:\\d{1,2}(\\.\\d+)?\\s+(\+|-)\\d{4}/A', $line, $m)) {
				throw new Exception(JText::_('JLIB_FILESYSTEM_PATCHER_INVALID_DIFF'));
			}
			$dst = $this->root.$m[1];
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
				$this->apply($lines, $src, $dst, (int)$m[1], $src_size, (int)$m[4], $dst_size);
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

	/**
	 * Reset the pacher
	 */	
	public function reset()
	{
		$this->sources = array();
		$this->destinations = array();
		$this->removals = array();
	}
	
	/**
	 * Apply the patch
	 *
	 * @return integer the number of files patched
	 */	
	public function patch()
	{
		// Initialize the counter
		$done = 0;

		// patch each destination file
		foreach($this->destinations as $file => $content) {
			if (JFile::write($file, implode($this->newline, $content))) {
				if (isset($this->sources[$file])) {
					$this->sources[$file] = $content;
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
	
	/**
	 * Apply the patch
	 */	
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
			if (!isset($line[0])) {
				$source[] = '';
				$destin[] = '';
				$src_left--;
				$dst_left--;
				continue;
			}
			if ($line[0]=='-') {
				if ($src_left==0) {
					throw new Exception(JText::sprintf('JLIB_FILESYSTEM_PATCHER_REMOVE_LINE', key($lines)));
				}
				$source[] = substr($line, 1);
				$src_left--;
			}
			elseif ($line[0]=='+') {
				if ($dst_left==0) {
					throw new Exception(JText::sprintf('JLIB_FILESYSTEM_PATCHER_ADD_LINE', key($lines)));
				}
				$destin[] = substr($line, 1);
				$dst_left--;
			}
			else {
				if (!isset($line[1])) {
					$line = '';
				}
				elseif ($line[0]=='\\') {
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
						throw new Exception(JText::sprintf('JLIB_FILESYSTEM_PATCHER_UNEXISING_SOURCE', $src));
					}
				}
				if ($dst_size>0) {
					if ($src_size>0) {
						$dst_lines =& $this->getDestination($dst, $src);
						$src_bottom=$src_line+count($source);
						$dst_bottom=$dst_line+count($destin);
						
						for ($l=$src_line;$l<$src_bottom;$l++) {
							if ($src_lines[$l]!=$source[$l-$src_line]) {
								throw new Exception(JText::sprintf('JLIB_FILESYSTEM_PATCHER_FAILED_VERIFY', $src, $l));
							}
						}
						array_splice($dst_lines, $dst_line, count($source), $destin);
					}
					else {
						$this->destinations[$dst] = $destin;
					}
				}
				else {
					$this->removals[] = $src;
				}
				return;
			}
		} while (false !== ($line = next($lines)));
		throw new Exception(JText::_('JLIB_FILESYSTEM_PATCHER_UNEXPECTED_EOF'));
	}
}

