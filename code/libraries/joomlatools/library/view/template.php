<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
 */

/**
 * Abstract Template View
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\View
 */
abstract class KViewTemplate extends KViewAbstract
{
    /**
     * Template identifier (com://APP/COMPONENT.template.NAME)
     *
     * @var string|object
     */
    protected $_template;

    /**
     * Layout name
     *
     * @var string
     */
    protected $_layout;

    /**
     * Auto assign
     *
     * @var boolean
     */
    protected $_auto_fetch;

    /**
     * Constructor
     *
     * @param   KObjectConfig $config Configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Set the auto fetch
        $this->_auto_fetch = $config->auto_fetch;

        //Set the layout
        $this->setLayout($config->layout);

        //Set the template object
        $this->setTemplate($config->template);
    }

    /**
     * Initializes the config for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   KObjectConfig $config Configuration options
     * @return  void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'auto_fetch'         => true,
            'layout'             => '',
            'template'           => 'default',
            'template_filters'   => array('asset', 'decorator'),
            'template_functions' => array(
                'route'   => array($this, 'getRoute'),
                'url'     => array($this, 'getUrl'),
                'title'   => array($this, 'getTitle'),
                'content' => array($this, 'getContent'),
            ),
        ));

        parent::_initialize($config);
    }

    /**
     * Return the views output
     *
     * @param KViewContext  $context A view context object
     * @return string  The output of the view
     */
    protected function _actionRender(KViewContext $context)
    {
        $format = $this->getFormat(); //format cannot be changed through context
        $layout = $context->layout;

        if(!parse_url($layout, PHP_URL_SCHEME))
        {
            //Handle partial layout paths
            if (is_string($layout) && strpos($layout, '.') === false)
            {
                $identifier = $this->getIdentifier()->toArray();
                $identifier['name'] = $layout;
                unset($identifier['path'][0]);

                $layout = (string) $this->getIdentifier($identifier);
            }
        }

        $data = KObjectConfig::unbox($context->data);

        //Render the template
        $content = $this->getTemplate()
            ->loadFile((string) $layout.'.'.$format)
            ->setParameters($context->parameters)
            ->render($data);

        //Set the content
        $this->setContent($content);

        return parent::_actionRender($context);
    }

    /**
     * Fetch the view data
     *
     * This function will always fetch the model state. Model data will only be fetched if the auto_fetch property is
     * set to TRUE.
     *
     * @param KViewContext  $context A view context object
     * @return void
     */
    protected function _fetchData(KViewContext $context)
    {
        $model = $this->getModel();

        //Auto-assign the data from the model
        if($this->_auto_fetch)
        {
            //Set the data
            $name   = $this->getName();
            $entity = $model->fetch();
            $context->data->$name = $entity;

            //Set the parameters
            if($this->isCollection())
            {
                $context->parameters = $model->getState()->getValues();
                $context->parameters->total = $model->count();
            }
            else {
                $context->parameters = $entity->getProperties();
                $context->parameters->total = 1;
            }
        }
        else $context->parameters = $model->getState()->getValues();

        //Set the layout and view in the parameters.
        $context->parameters->layout = $context->layout;
        $context->parameters->view   = $this->getName();
    }

    /**
     * Get the template object attached to the view
     *
     * @throws UnexpectedValueException    If the template doesn't implement the TemplateInterface
     * @return  KTemplateInterface
     */
    public function getTemplate()
    {
        if (!$this->_template instanceof KTemplateInterface)
        {
            //Make sure we have a template identifier
            if (!($this->_template instanceof KObjectIdentifier)) {
                $this->setTemplate($this->_template);
            }

            $options = array(
                'filters'   => $this->getConfig()->template_filters,
                'functions' => $this->getConfig()->template_functions,
            );

            $this->_template = $this->getObject($this->_template, $options);

            if(!$this->_template instanceof KTemplateInterface)
            {
                throw new UnexpectedValueException(
                    'Template: '.get_class($this->_template).' does not implement KTemplateInterface'
                );
            }
        }

        return $this->_template;
    }

    /**
     * Method to set a template object attached to the view
     *
     * @param   mixed   $template An object that implements KObjectInterface, an object that implements
     *                            KObjectIdentifierInterface or valid identifier string
     * @throws  UnexpectedValueException    If the identifier is not a table identifier
     * @return  KViewAbstract
     */
    public function setTemplate($template)
    {
        if (!($template instanceof KTemplateInterface))
        {
            if (is_string($template) && strpos($template, '.') === false)
            {
                $identifier = $this->getIdentifier()->toArray();
                $identifier['path'] = array('template');
                $identifier['name'] = $template;

                $identifier = $this->getIdentifier($identifier);
            }
            else $identifier = $this->getIdentifier($template);

            $template = $identifier;
        }

        $this->_template = $template;

        return $this;
    }

    /**
     * Get the layout
     *
     * @return string The layout name
     */
    public function getLayout()
    {
        return empty($this->_layout) ? 'default' : $this->_layout;
    }

    /**
     * Sets the layout name to use
     *
     * @param    string  $layout The template name.
     * @return   $this
     */
    public function setLayout($layout)
    {
        $this->_layout = $layout;
        return $this;
    }

    /**
     * Creates a route based on a full or partial query string.
     *
     * This function adds the layout information to the route if a layout has been set
     *
     * @param string|array $route   The query string used to create the route
     * @param boolean $fqr          If TRUE create a fully qualified route. Default TRUE.
     * @param boolean $escape       If TRUE escapes the route for xml compliance. Default TRUE.
     * @return  KDispatcherRouterRoute The route
     */
    public function getRoute($route = '', $fqr = true, $escape = true)
    {
        if(is_string($route)) {
            parse_str(trim($route), $parts);
        } else {
            $parts = $route;
        }

        //Check to see if there is component information in the route if not add it
        if (!isset($parts['component'])) {
            $parts['component'] = $this->getIdentifier()->package;
        }

        //Add the view information to the route if it's not set
        if (!isset($parts['view'])) {
            $parts['view'] = $this->getName();
        }

        if (!isset($parts['layout']) && !empty($this->_layout))
        {
            if (($parts['component'] == $this->getIdentifier()->package) && ($parts['view'] == $this->getName())) {
                $parts['layout'] = $this->getLayout();
            }
        }

        return parent::getRoute($parts, $fqr, $escape);
    }

    /**
     * Get the view context
     *
     * @return  KViewContext
     */
    public function getContext()
    {
        $context = new KViewContextTemplate();
        $context->setSubject($this);
        $context->setData($this->_data);
        $context->setLayout($this->getLayout());

        return $context;
    }
}