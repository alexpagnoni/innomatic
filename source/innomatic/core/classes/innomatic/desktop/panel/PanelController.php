<?php
/**
 * Innomatic
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright  1999-2014 Innoteam Srl
 * @license    http://www.innomatic.org/license/   BSD License
 * @link       http://www.innomatic.org
 * @since      Class available since Release 5.0
*/
namespace Innomatic\Desktop\Panel;

use \Innomatic\Core\InnomaticContainer;

/**
 * Abstract class for implementing a controller in a Desktop Panel following
 * the MVC design pattern.
 *
 * @copyright  2000-2012 Innoteam Srl
 * @license    http://www.innomatic.org/license/   BSD License
 * @link       http://www.innomatic.org
 * @since      Class available since Release 5.0
 * @package    Desktop
 */
abstract class PanelController implements \Innomatic\Util\Observer
{
    protected $_application;
    protected $_mode;
    protected $_applicationHome;
    protected $_action;
    protected $_view;

    public function __construct($mode, $application)
    {
        // Builds the application home path
        $home = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome();
        switch ($mode) {
            case \Innomatic\Core\InnomaticContainer::MODE_ROOT:
                $home .= 'root/';
                break;

            case \Innomatic\Core\InnomaticContainer::MODE_DOMAIN:
                $home .= 'domain/';
                break;
        }
        $home .= $application . '-panel/';

        // Checks if the application exists and is valid
        if (file_exists($home)) {
            $this->_mode = $mode;
            $this->_applicationHome = $home;
            $this->_application = $application;
        } else {
            throw new \Innomatic\Wui\WuiException(\Innomatic\Wui\WuiException::INVALID_APPLICATION);
        }

        // TODO Verificare, dopo questa impostazione, quanto ancora sia utile di WuiDispatcher

        $view = null;
        $action = null;

        // View initialization
        $viewDispatcher = new \Innomatic\Wui\Dispatch\WuiDispatcher('view');
        $viewEvent = $viewDispatcher->getEventName();
        if (!strlen($viewEvent)) {
            $viewEvent = 'default';
        }
        $viewClassName = ucfirst($this->_application).'PanelViews';

        // Checks if view file and definition exist
        // @todo update to new namespaces model
        if (!include_once($this->_applicationHome.$viewClassName.'.php')) {
            throw new \Innomatic\Wui\WuiException(\Innomatic\Wui\WuiException::MISSING_VIEWS_FILE);
        }
        if (!class_exists($viewClassName, true)) {
            throw new \Innomatic\Wui\WuiException(\Innomatic\Wui\WuiException::MISSING_VIEWS_CLASS);
        }

        // Instantiate views class
        $this->_view = new $viewClassName($this);
        $this->_view->beginHelper();

        // Action initialization
        $actionClassName = ucfirst($this->_application).'PanelActions';

        // Checks if class file and definition exist
        if (!include_once($this->_applicationHome.$actionClassName.'.php')) {
            throw new \Innomatic\Wui\WuiException(\Innomatic\Wui\WuiException::MISSING_ACTIONS_FILE);
        }
        if (!class_exists($actionClassName, true)) {
            throw new \Innomatic\Wui\WuiException(\Innomatic\Wui\WuiException::MISSING_ACTIONS_CLASS);
        }

        // AJAX
        $ajax_request_uri = $_SERVER['REQUEST_URI'];
        if (strpos($ajax_request_uri, '?')) {
            $ajax_request_uri = substr($ajax_request_uri, 0, strpos($ajax_request_uri, '?'));
        }

        $xajax = \Innomatic\Ajax\Xajax::instance('Xajax', $ajax_request_uri);

        // Set debug mode
        if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getState() == \Innomatic\Core\InnomaticContainer::STATE_DEBUG) {
            $xajax->debugOn();
        }
        $xajax->setLogFile(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome().'core/log/ajax.log');

        // Register action ajax calls
        $theClass = new \ReflectionClass($actionClassName);
        $methods = $theClass->getMethods();
        foreach ($methods as $method) {
            // Ignore private methods
            $theMethod = new \ReflectionMethod($theClass->getName(), $method->getName());
            if (!$theMethod->isPublic()) {
                continue;
            }

            // Expose only methods beginning with "ajax" prefix
            if (!(substr($method->getName(), 0, 4) == 'ajax')) {
                continue;
            }

            // Register the ajax call
            $call_name = substr($method->getName(), 4);
            $this->_view->getWuiContainer()->registerAjaxCall($call_name);
            $xajax->registerExternalFunction(array($call_name, $actionClassName, $method->getName()), $this->_applicationHome.$actionClassName.'.php');
        }

        // Process ajax requests, if any (if so, then it exits)
        $xajax->processRequests();

        // Action execution, if set
        $actionDispatcher = new \Innomatic\Wui\Dispatch\WuiDispatcher('action');
        $actionEvent = $actionDispatcher->getEventName();
        if (strlen($actionEvent)) {

            $this->_action = new $actionClassName($this);
            $this->_action->addObserver($this);
            if (is_object($this->_view)) {
                $this->_action->addObserver($this->_view);
            }
            $this->_action->beginHelper();

            // Executes the action
            $actionResult = $this->_action->execute(
                $actionEvent,
                $actionDispatcher->getEventData()
            );
            $this->_action->endHelper();
        }

        // Displays the view result
        if (is_object($this->_view)) {
            $this->_view->execute($viewEvent, $viewDispatcher->getEventData());
            $this->_view->endHelper();
            $this->_view->display();
        } else {
            throw new \Innomatic\Wui\WuiException(\Innomatic\Wui\WuiException::NO_VIEW_DEFINED);
        }
    }

    public function getAction()
    {
        return $this->_action;
    }

    public function getView()
    {
        return $this->_view;
    }
}
