<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
 */

/**
 * Joomlatools System Plugin
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Plugin\System\Joomlatools
 */
class PlgSystemJoomlatools extends JPlugin
{
    /**
     * Boots Koowa framework and applies some bug fixes for certain environments
     *
     * @param object $subject
     * @param array  $config
     */
    public function __construct($subject, $config = array())
    {
        // Try to raise Xdebug nesting level
        @ini_set('xdebug.max_nesting_level', 200);

        // Set pcre.backtrack_limit to a larger value
        // See: https://bugs.php.net/bug.php?id=40846
        if (version_compare(PHP_VERSION, '5.3.6', '<=') && @ini_get('pcre.backtrack_limit') < 1000000) {
            @ini_set('pcre.backtrack_limit', 1000000);
        }

        //Bugfix: Set offset according to user's timezone
        if (!JFactory::getUser()->guest)
        {
            if ($offset = JFactory::getUser()->getParam('timezone')) {
                JFactory::getConfig()->set('offset', $offset);
            }
        }

        //Bugfix: Set display_errors accordingly
        if(JFactory::getApplication()->getCfg('error_reporting') == 'none') {
            @ini_set('display_errors', 0);
        }

        //Bootstrap the Koowa Framework
        $this->bootstrap();

        parent::__construct($subject, $config);
    }

    /**
     * Allow event listeners to perform cleanup operations before the application terminates
     */
    public function __destruct()
    {
        $this->onBeforeApplicationTerminate();
    }

    /**
     * Bootstrap the Koowa Framework
     *
     * @return bool Returns TRUE if the framework was found and bootstrapped succesfully.
     */
    public function bootstrap()
    {
        $path = JPATH_LIBRARIES.'/joomlatools/library/koowa.php';
        if (file_exists($path))
        {
            /**
             * Koowa Bootstrapping
             *
             * If KOOWA is defined assume it was already loaded and bootstrapped
             */
            if (!defined('KOOWA'))
            {
                require_once $path;

                $application = JFactory::getApplication()->getName();

                /**
                 * Find Composer Vendor Directory
                 */
                $vendor_path = false;
                if(file_exists(JPATH_ROOT.'/composer.json'))
                {
                    $content  = file_get_contents(JPATH_ROOT.'/composer.json');
                    $composer = json_decode($content);

                    if(isset($composer->config->vendor_dir)) {
                        $vendor_path = JPATH_ROOT.'/'.$composer->config->vendor_dir;
                    } else {
                        $vendor_path = JPATH_ROOT.'/vendor';
                    }
                }

                /**
                 * Framework Bootstrapping
                 */
                Koowa::getInstance(array(
                    'debug'           => JDEBUG,
                    'cache'           => false, //JFactory::getApplication()->getCfg('caching')
                    'cache_namespace' => 'koowa-' . $application . '-' . md5(JFactory::getApplication()->getCfg('secret')),
                    'root_path'       => JPATH_ROOT,
                    'base_path'       => JPATH_BASE,
                    'vendor_path'     => $vendor_path
                ));

                /**
                 * Component Bootstrapping
                 */
                $bootstrapper = KObjectManager::getInstance()->getObject('object.bootstrapper')
                    ->registerComponents(JPATH_LIBRARIES . '/joomlatools/component', 'koowa')
                    ->registerApplication('site', JPATH_SITE . '/components', JFactory::getApplication()->isSite())
                    ->registerApplication('admin', JPATH_ADMINISTRATOR . '/components', JFactory::getApplication()->isAdmin());

                if (is_dir(JPATH_LIBRARIES . '/joomlatools-components')) {
                    $bootstrapper->registerComponents(JPATH_LIBRARIES . '/joomlatools-components', 'koowa');
                }

                $bootstrapper->bootstrap();
            }

            $manager = KObjectManager::getInstance();
            $loader  = $manager->getClassLoader();

            //Module Locator
            $loader->registerLocator(new ComKoowaClassLocatorModule(array(
                'namespaces' => array(
                    '\\'     => JPATH_BASE.'/modules',
                    'Koowa'  => JPATH_LIBRARIES.'/joomlatools/module',
                )
            )));

            /**
             * Module Bootstrapping
             */
            $manager->registerLocator('com:koowa.object.locator.module');

            /**
             * Plugin Bootstrapping
             */
            $loader->registerLocator(new ComKoowaClassLocatorPlugin(array(
                'namespaces' => array(
                    '\\'     => JPATH_PLUGINS,
                    'Koowa'  => JPATH_LIBRARIES.'/joomlatools/plugin',
                )
            )));

            $manager->registerLocator('com:koowa.object.locator.plugin');

            /**
             * Context Boostrapping
             */
            $request = $manager->getObject('request');

            // Get the URL from Joomla if live_site is set
            if (JFactory::getApplication()->getCfg('live_site'))
            {
                $request->setBasePath(rtrim(JURI::base(true), '/\\'));
                $request->setBaseUrl($manager->getObject('lib:http.url', array('url' => JURI::base())));
            }

            //Exception Handling
            if (PHP_SAPI !== 'cli') {
                $manager->getObject('event.publisher')->addListener('onException', array($this, 'onException'), KEvent::PRIORITY_LOW);
            }

            // Handle 404 errors gracefully after log outs
            $manager->getObject('event.publisher')->addListener('onException', array($this, 'onErrorAfterLogout'), KEvent::PRIORITY_HIGH);

            /**
             * Plugin Bootstrapping
             */
            JPluginHelper::importPlugin('koowa', null, true);

            // Load and bootstrap custom vendor directory if it exists
            $custom_vendor = dirname(dirname($path)).'/vendor';
            if (is_dir($custom_vendor) && file_exists($custom_vendor.'/autoload.php')) {
                require_once $custom_vendor.'/autoload.php';
            }

            return true;
        }

        return false;
    }

    /**
     * Handles 404 errors gracefully after log outs
     *
     * If a user does not have access to the entity after logging out, they will be redirected to the homepage.
     *
     * @param KEventException $event
     * @return bool
     */
    public function onErrorAfterLogout(KEventException $event)
    {
        if ($event->getException()->getCode() === KHttpResponse::NOT_FOUND && JFactory::getApplication()->isSite())
        {
            $hash = JApplicationHelper::getHash('PlgSystemLogout');

            $app = JFactory::getApplication();
            if ($app->input->cookie->getString($hash, null)) // just logged out
            {
                $app->enqueueMessage(JText::_('PLG_SYSTEM_LOGOUT_REDIRECT'));
                $app->redirect('index.php');

                return true;
            }
        }
    }

    /**
     * Exception event handler
     *
     * @param KEventException $event
     */
    public function onException(KEventException $event)
    {
        KObjectManager::getInstance()->getObject('com:koowa.dispatcher.http')->fail($event);
        return true;
    }

    /**
     * Proxy onAfterInitialise
     *
     * @return void
     */
    public function onAfterInitialise()
    {
        $this->_proxyEvent('onAfterApplicationInitialise');
    }

    /**
     * Proxy onAfterRoute
     *
     * @return void
     */
    public function onAfterRoute()
    {
        $this->_proxyEvent('onAfterApplicationRoute');
    }

    /**
     * Proxy onAfterDispatch
     *
     * @return void
     */
    public function onAfterDispatch()
    {
        $this->_proxyEvent('onAfterApplicationDispatch');
    }

    /**
     * Proxy onBeforeRender
     *
     * @return void
     */
    public function onBeforeRender()
    {
        $this->_proxyEvent('onBeforeApplicationRender');
    }

    /**
     * Proxy onAfterRender
     *
     * @return void
     */
    public function onAfterRender()
    {
        $this->_proxyEvent('onAfterApplicationRender');
    }

    /**
     * Proxy onBeforeApplicationTerminate
     *
     * @return void
     */
    public function onBeforeApplicationTerminate()
    {
        $this->_proxyEvent('onBeforeApplicationTerminate');
    }

    /**
     * Proxy all Joomla events
     *
     * @param   array  &$args  Arguments
     * @return  mixed  Routine return value
     */
    protected function _proxyEvent($event, $args = array())
    {
        //Publish the event
        if (class_exists('Koowa')) {
            KObjectManager::getInstance()->getObject('event.publisher')->publishEvent($event, $args, JFactory::getApplication());
        }
    }
}
