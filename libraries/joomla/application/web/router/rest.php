<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Application
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * RESTful Web application router class for the Joomla Platform.
 *
 * @package     Joomla.Platform
 * @subpackage  Application
 * @since       12.3
 */
class JApplicationWebRouterRest extends JApplicationWebRouterBase
{
	/**
	 * @var    array  An array of HTTP Method => controller suffix pairs for routing the request.
	 * @since  12.3
	 */
	protected $suffixMap = array(
		'GET' => 'Get',
		'POST' => 'Create',
		'PUT' => 'Update',
		'PATCH' => 'Update',
		'DELETE' => 'Delete',
		'HEAD' => 'Head',
		'OPTIONS' => 'Options'
	);

	/**
	 * Find and execute the appropriate controller based on a given route.
	 *
	 * @param   string  $route  The route string for which to find and execute a controller.
	 *
	 * @return  void
	 *
	 * @since   12.3
	 * @throws  InvalidArgumentException
	 * @throws  RuntimeException
	 */
	public function execute($route)
	{
		// Get the controller name based on the route patterns and requested route.
		$name = $this->parseRoute($route);

		// Append the HTTP method based suffix.
		$name .= $this->fetchControllerSuffix();

		// Get the controller object by name.
		$controller = $this->fetchController($name);

		// Execute the controller.
		$controller->execute();
	}

	/**
	 * Set a controller class suffix for a given HTTP method.
	 *
	 * @param   string  $method  The HTTP method for which to set the class suffix.
	 * @param   string  $suffix  The class suffix to use when fetching the controller name for a given request.
	 *
	 * @return  JApplicationWebRouter  This object for method chaining.
	 *
	 * @since   12.3
	 */
	public function setHttpMethodSuffix($method, $suffix)
	{
		$this->suffixMap[strtoupper((string) $method)] = (string) $suffix;

		return $this;
	}

	/**
	 * Get the controller class suffix string.
	 *
	 * @return  string
	 *
	 * @since   12.3
	 * @throws  RuntimeException
	 */
	protected function fetchControllerSuffix()
	{
		// Validate that we have a map to handle the given HTTP method.
		if (!isset($this->suffixMap[$this->input->getMethod()]))
		{
			throw new RuntimeException(sprintf('Unable to support the HTTP method `%s`.', $this->input->getMethod()), 404);
		}

		return ucfirst($this->suffixMap[$this->input->getMethod()]);
	}
}
