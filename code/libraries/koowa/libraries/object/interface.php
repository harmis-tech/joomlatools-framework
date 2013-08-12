<?php
/**
 * Koowa Framework - http://developer.joomlatools.com/koowa
 *
 * @copyright	Copyright (C) 2007 - 2013 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/koowa for the canonical source repository
 */

/**
 * Object Interface
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Object
 */
interface KObjectInterface
{
    /**
     * Constructor.
     *
     * @param   KConfig $config Configuration options
     */
    public function __construct( KConfig $config = null);

	/**
	 * Get an instance of a class based on a class identifier only creating it if it doesn't exist yet.
	 *
	 * @param	string|object	$identifier The class identifier or identifier object
	 * @param	array  			$config     An optional associative array of configuration settings.
	 * @throws	RuntimeException if the service container has not been defined.
	 * @return	object  		Return object on success, throws exception on failure
	 * @see 	KObjectServiceable
	 */
	public function getService($identifier, array $config = array());

	/**
	 * Gets the service identifier.
	 *
     * @param   null|KServiceIdentifier|string $identifier Identifier
	 * @return	KServiceIdentifier
     *
	 * @see 	KObjectServiceable
     * @throws	RuntimeException if the service container has not been defined.
	 */
    public function getIdentifier($identifier = null);
}