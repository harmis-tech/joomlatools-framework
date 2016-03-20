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
 * Component Translator Locator
 *
 * @author  Johan Janssens <http://github.com/johanjanssens>
 * @package Kodekit\Library\Translator\Locator
 */
class TranslatorLocatorComponent extends TranslatorLocatorIdentifier
{
    /**
     * The locator name
     *
     * @var string
     */
    protected static $_name = 'com';

    /**
     * Find a template path
     *
     * @param array  $info      The path information
     * @return array
     */
    public function find(array $info)
    {
        $result = false;

        //Base paths
        $paths = $this->getObject('object.bootstrapper')->getComponentPath($info['package'], $info['domain']);

        $result = array();
        foreach($paths as $basepath)
        {
            $info['path'] = $basepath.'/resources/language';

            if($path = parent::find($info)) {
                $result = array_merge($result, $path);
            }
        }

        return $result;
    }
}
