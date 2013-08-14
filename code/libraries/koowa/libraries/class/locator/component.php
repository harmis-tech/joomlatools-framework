<?php
/**
 * Koowa Framework - http://developer.joomlatools.com/koowa
 *
 * @copyright	Copyright (C) 2007 - 2013 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/koowa for the canonical source repository
 */

/**
 * Component Loader Adapter
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Loader
 */
class KClassLocatorComponent extends KClassLocatorAbstract
{
	/**
	 * The adapter type
	 *
	 * @var string
	 */
	protected $_type = 'com';

	/**
	 * The class prefix
	 *
	 * @var string
	 */
	protected $_prefix = 'Com';

	/**
	 * Get the path based on a class name
	 *
	 * @param  string $classname The class name
     * @param  string $basepath  The base path
	 * @return string|bool  	 Returns the path on success FALSE on failure
	 */
	public function locate($classname, $basepath = null)
	{
		$path = false;

        if (substr($classname, 0, strlen($this->_prefix)) === $this->_prefix)
        {
            /*
             * Exception rule for Exception classes
             *
             * Transform class to lower case to always load the exception class from the /exception/ folder.
             */
            if ($pos = strpos($classname, 'Exception')) {
                $filename  = substr($classname, $pos + strlen('Exception'));
                $classname = str_replace($filename, ucfirst(strtolower($filename)), $classname);
            }

            $word    = strtolower(preg_replace('/(?<=\\w)([A-Z])/', ' \\1', $classname));
            $parts   = explode(' ', $word);

            $type    = array_shift($parts);
            $package = array_shift($parts);

            $component = 'com_'.$package;
            $file 	   = array_pop($parts);

            if(count($parts))
            {
                if($parts[0] != 'view')
                {
                    foreach($parts as $key => $value) {
                        $parts[$key] = KInflector::pluralize($value);
                    }
                }
                else $parts[0] = KInflector::pluralize($parts[0]);

                $path = implode('/', $parts);
                $path = $path.'/'.$file;
            }
            else $path = $file;

            //Find the basepath
            if(!empty($basepath) && empty($this->_basepaths[$package])) {
                $this->_basepath = $basepath;
            }

            if(isset($this->_basepaths[$package])) {
                $basepath = $this->_basepaths[$package];
            } else {
                $basepath = $this->_basepath;
            }

            $path = $basepath.'/components/'.$component.'/'.$path.'.php';
        }
        
		return $path;
	}
}