<?php
/**
 * Kodekit - http://timble.net/kodekit
 *
 * @copyright   Copyright (C) 2007 - 2016 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/timble/kodekit for the canonical source repository
 */

namespace Kodekit\Library;

/**
 * Composer Class Locator
 *
 * Proxy calls to the Composer Autoloader through Composer\Autoload\ClassLoader::findFile().
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Class\Locator
 */
class ClassLocatorComposer extends ClassLocatorAbstract
{
    /**
     * The locator name
     *
     * @var string
     */
    protected static $_name = 'composer';

    /**
     * The composer loader
     *
     * @var \Composer\Autoload\ClassLoader
     */
    protected $_loader = null;

    /**
     * Constructor
     *
     * @param array $config Array of configuration options.
     */
    public function __construct($config = array())
    {
        if(isset($config['vendor_path']))
        {
            //Proxy class loading
            if(file_exists($config['vendor_path'].'/autoload.php')) {
                $this->_loader = require $config['vendor_path'].'/autoload.php';
            }
        }
    }

    /**
     * Get a fully qualified path based on a class name
     *
     * @param  string $class     The class name
     * @return string|false Returns canonicalized absolute pathname or FALSE of the class could not be found.
     */
    public function locate($class)
    {
        $path = false;

        if($this->_loader) {
            $path = $this->_loader->findFile($class);
        }

        return $path;
    }
}
