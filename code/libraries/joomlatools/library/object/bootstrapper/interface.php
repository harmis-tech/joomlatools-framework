<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
 */

/**
 * Object Bootstrapper Interface
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Object\Bootstrapper
 */
interface KObjectBootstrapperInterface extends KObjectHandlable
{
    /**
     * Priority levels
     */
    const PRIORITY_HIGHEST = 1;
    const PRIORITY_HIGH    = 2;
    const PRIORITY_NORMAL  = 3;
    const PRIORITY_LOW     = 4;
    const PRIORITY_LOWEST  = 5;

    /**
     * Perform the bootstrapping
     *
     * @return void
     */
    public function bootstrap();

    /**
     * Register an application
     *
     * @param string  $name  The application name
     * @param string  $path  The application path
     * @return KObjectBootstrapperInterface
     */
    public function registerApplication($name, $path, $bootstrap = false);

    /**
     * Register a component to be bootstrapped.
     *
     * If the component contains a /resources/config/bootstrapper.php file it will be registered. Class and object
     * locators will be setup for vendor only components.
     *
     * @param string $name      The component name
     * @param string $path      The component path
     * @param string $domain    The component domain. Domain is optional and can be NULL
     * @return KObjectBootstrapperInterface
     */
    public function registerComponent($name, $path, $domain = null);

    /**
     * Register components from a directory to be bootstrapped
     *
     * All the first level directories are assumed to be component folders and will be registered.
     *
     * @param string  $directory
     * @param string  $domain
     * @return KObjectBootstrapperInterface
     */
    public function registerComponents($directory, $domain = null);

    /**
     * Register a configuration file to be bootstrapped
     *
     * @param string $filename The absolute path to the file
     * @return KObjectBootstrapperInterface
     */
    public function registerFile($filename);

    /**
     * Get an application path
     *
     * @param string  $name   The application name
     * @return string|null Returns the application path if the application was registered. NULL otherwise
     */
    public function getApplicationPath($name);

    /**
     * Get the registered components
     *
     * @return array
     */
    public function getComponents();

    /**
     * Get a registered component domain
     *
     * @param string $name    The component name
     * @return string Returns the component domain if the component is registered. FALSE otherwise
     */
    public function getComponentDomain($name);

    /**
     * Get a registered component path
     *
     * @param string $name    The component name
     * @param string $domain  The component domain. Domain is optional and can be NULL
     * @return string Returns the component path if the component is registered. FALSE otherwise
     */
    public function getComponentPath($name, $domain = null);

    /**
     * Get a registered component domain
     *
     * @param string $name    The component name
     * @param string $domain  The component domain. Domain is optional and can be NULL
     * @return string|null Returns the component class namespace if the component is registered. NULL otherwise
     */
    public function getComponentNamespace($name, $domain = null);

    /**
     * Get a hash based on a name and domain
     *
     * @param string $name    The component name
     * @param string $domain  The component domain. Domain is optional and can be NULL
     * @return string The hash
     */
    public function getComponentIdentifier($name, $domain = null);

    /**
     * Check if the bootstrapper has been run
     *
     * If you specify a specific component name the function will check if this component was bootstrapped.
     *
     * @param string $name      The component name
     * @return bool TRUE if the bootstrapping has run FALSE otherwise
     */
    public function isBootstrapped($name = null, $domain = null);
}