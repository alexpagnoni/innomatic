<?php
/**
 * Innomatic
 *
 * LICENSE 
 * 
 * This source file is subject to the new BSD license that is bundled 
 * with this package in the file LICENSE.
 *
 * @copyright  1999-2012 Innoteam S.r.l.
 * @license    http://www.innomatic.org/license/   BSD License
 * @link       http://www.innomatic.org
 * @since      Class available since Release 5.0
*/

require_once('innomatic/util/Singleton.php');

/**
 * Front controller for the Innomatic desktop.
 * 
 * This is the real front controller for the Innomatic desktop.
 *
 * @copyright  2000-2012 Innoteam S.r.l.
 * @license    http://www.innomatic.org/license/   BSD License
 * @link       http://www.innomatic.org
 * @since      Class available since Release 5.0
 * @package    Desktop
 */
class DesktopFrontController extends Singleton
{
    /**
     * Innomatic mode.
     *
     * @var integer
     */
    protected $mode;
    /**
     * Desktop session object.
     *
     * @var DesktopSession
     */
    public $session;

    /**
     * Class constructor.
     */
    public function ___construct()
    {
        require_once('innomatic/desktop/session/DesktopSession.php');
        $this->session = new DesktopSession();
        $this->session->start();
    }

    public function execute($mode, $resource)
    {
        require_once('innomatic/wui/WuiException.php');

        $this->mode = $mode;
        require_once('innomatic/desktop/auth/DesktopAuthenticatorHelperFactory.php');
        require_once('innomatic/wui/theme/WuiTheme.php');

        // Sets root theme.
        WuiTheme::setRootTheme();

        // Authenticates the user.
        $auth = DesktopAuthenticatorHelperFactory::getAuthenticatorHelper($mode);
        if ($auth->authenticate()) {
            // Validates WUI widgets input.
            require_once('innomatic/wui/validation/WuiValidatorHelper.php');
            $validator = new WuiValidatorHelper();
            $validator->validate();
            
            // TODO Put authorizer here
                
            // Sets domain theme, if the system is in domain mode.
            if ($mode == InnomaticContainer::MODE_DOMAIN) {
                WuiTheme::setDomainTheme();
            }
                
            switch ($mode) {
                case InnomaticContainer::MODE_BASE:
                    $this->executeBase($resource);
                    break;
                        
                case InnomaticContainer::MODE_DOMAIN:
                    $this->executeDomain($resource);
                    break;
                        
                case InnomaticContainer::MODE_ROOT:
                    $this->executeRoot($resource);
                    break;
            }
                
            // TODO Verify whose panel has been called
                
            // TODO Verificare se esiste e se � valida, altrimenti mandare 404 di WebApp
        }

        /**
         * To be applied when implementing a xml def file parser
         require_once('shared/wui/WuiXml.php');
         $wui->addChild(new WuiXml('def',array('definition' => $this->response->getContent())));
         $wui->render();
         */
    }

    /**
     * Gets the Innomatic mode.
     *
     * @return integer
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Launches a panel in the base Innomatic desktop.
     *
     * @param string $resource Panel name.
     */
    public function executeBase($resource)
    {
        $path = 'base';
        // TODO verificare se � ancora necessario dopo aver creato Wui::setTheme()
        if (!(InnomaticContainer::instance('innomaticcontainer')->getState() == InnomaticContainer::STATE_SETUP)) {
            require_once('innomatic/application/ApplicationSettings.php');
            $appCfg = new ApplicationSettings(
                InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
                'innomatic'
            );
            if (strlen($appCfg->getKey('wui-root-theme'))) {
                Wui::instance('wui')->setTheme(
                    $appCfg->getKey('wui-root-theme')
                );
            }
            unset($appCfg);
        } else {
            $path = 'setup';
        }

        if (substr($resource, -1, 1) != '/') {
            include(
                'innomatic/desktop/layout/'
                . $path . '/' . basename($resource) . '.php'
            );
        } else {
            WebAppContainer::instance('webappcontainer')->getProcessor()->getResponse()->addHeader(
                'P3P', 'CP="CUR ADM OUR NOR STA NID"'
            );
            include('innomatic/desktop/layout/' . $path . '/index.php');
        }
    }

    /**
     * Launches a panel in the control panel (root desktop).
     *
     * If the panel name is one among "index", "main", "menu" and "logo" then
     * no real panel is launched, a root desktop layout file is included instead
     * for drawing a desktop frame.
     * 
     * Root desktop layout files are stored in the folder
     * core/classes/innomatic/desktop/layout/root.
     * 
     * If the panel name is "unlock", a special routine for unlocking a blocked
     * Innomatic container (such as when an application update failed) is
     * launched, after a standard root authentication.
     * 
     * @param string $resource Panel name.
     */
    public function executeRoot($resource)
    {
        require_once('innomatic/desktop/layout/DesktopLayout.php');
        DesktopLayout::instance('desktoplayout')->initRootLayout();
        
        if (substr($resource, -1, 1) != '/') {
            $desktopPanel = basename($resource);
            if (
                InnomaticContainer::instance('innomaticcontainer')->getState()
                == InnomaticContainer::STATE_DEBUG
            ) {
                require_once('innomatic/debug/InnomaticDump.php');
                $dump = InnomaticDump::instance('innomaticdump');
                $dump->desktopApplication = $desktopPanel;
            }

            switch ($desktopPanel) {
                case 'index':
                case 'main':
                case 'menu':
                case 'logo':
                case 'unlock':
                    break;

                default:
                    require_once('shared/wui/WuiSessionkey.php');
                    $empty = new WuiSessionKey(
                        'mainpage',
                        array(
                            'sessionobjectnopage' => 'true',
                            'value' => $desktopPanel
                    )
                );
            }
            if (is_dir($resource . '-panel')) {
                $panelHome = $resource . '-panel/';
                $panelName = basename($resource);
                $controllerClassName = ucfirst($panelName) . 'PanelController';

                // Checks if view file and definition exist
                if (!include_once($panelHome.$controllerClassName . '.php')) {
                    require_once('innomatic/wui/WuiException.php');
                    throw new WuiException(
                        WuiException::MISSING_CONTROLLER_FILE
                    );
                }
                if (!class_exists($controllerClassName)) {
                    require_once('innomatic/wui/WuiException.php');
                    throw new WuiException(
                        WuiException::MISSING_CONTROLLER_CLASS
                    );
                }
                $controller = new $controllerClassName(
                    InnomaticContainer::MODE_ROOT,
                    $panelName
                );
            } else {
                switch ($desktopPanel) {
                    case 'index':
                    case 'main':
                    case 'menu':
                    case 'logo':
                        include(
                            'innomatic/desktop/layout/root/'
                            . $desktopPanel
                            . '.php'
                        );
                        break;

                    case 'unlock':
                        // Handles system unlock.
                        $innomatic = InnomaticContainer::instance('innomaticcontainer');
                        $innomatic->setInterface(InnomaticContainer::INTERFACE_WEB);
                        $innomatic->unlock();
                        break;
                        
                    default:
                        include($resource.'.php');
                }

            }
        } else {
            if (strlen($this->session->get('INNOMATIC_ROOT_AUTH_USER')))
            {
                require_once('shared/wui/WuiSessionkey.php');

                $empty = new WuiSessionKey( 'mainpage', array( 'sessionobjectnopage' => 'true' ) );
                if ( !strlen( $empty->mValue )
                    or (!file_exists( InnomaticContainer::instance('innomaticcontainer')->getHome() . 'root/'.$empty->mValue . '.php')
                    and !file_exists( InnomaticContainer::instance('innomaticcontainer')->getHome() . 'root/'.$empty->mValue . '-panel'))) {
                    $main_page_url = 'main';
                } else {
                    require_once('innomatic/wui/dispatch/WuiEventsCall.php');
                    $main_page_url = WuiEventsCall::buildEventsCallString($empty->mValue, array( array( 'view', 'default', '' ) ) );
                }
                WebAppContainer::instance('webappcontainer')->getProcessor()->getResponse()->addHeader('P3P', 'CP="CUR ADM OUR NOR STA NID"' );
                include('innomatic/desktop/layout/root/index.php');
            }
        }
    }

    /**
     * Launches a panel in the domain desktop.
     *
     * If the panel name is one among "index", "main", "menu" and "logo" then
     * no real panel is launched, a domain  desktop layout file is included
     * instead for drawing a desktop frame.
     * 
     * Domain desktop layout files are stored in the folder
     * core/classes/innomatic/desktop/layout/domain.
     * 
     * @param string $resource Panel name.
     */
    
    public function executeDomain($resource)
    {
        require_once('innomatic/desktop/layout/DesktopLayout.php');
        DesktopLayout::instance('desktoplayout')->initDomainLayout();
        
        if (substr($resource, -1, 1) != '/') {
            // Must exit if the user called a page for which he isn't enabled
            //
            require_once('innomatic/domain/user/Permissions.php');
            $perm = new Permissions(InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getGroup());

            $desktopPanel = basename($resource);
            if (InnomaticContainer::instance('innomaticcontainer')->getState() == InnomaticContainer::STATE_DEBUG) {
                $dump = InnomaticDump::instance('innomaticdump');
                $dump->desktopApplication = $desktopPanel;
            }

            switch ($desktopPanel) {
                case 'index':
                case 'menu':
                case 'logo':
                case 'main':
                    break;

                default:
                    $node_id = $perm->getNodeIdFromFileName($desktopPanel);

                    if ($node_id) {
                        if ($perm->Check($node_id, Permissions::NODETYPE_PAGE) == Permissions::NODE_NOTENABLED) {
                            require_once('innomatic/locale/LocaleCatalog.php');
                            $adloc = new LocaleCatalog('innomatic::authentication', InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage());
                            InnomaticContainer::instance('innomaticcontainer')->abort($adloc->getStr('nopageauth'));
                        }
                    } else {
                        require_once('innomatic/locale/LocaleCatalog.php');
                        $adloc = new LocaleCatalog('innomatic::authentication', InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage());
                        InnomaticContainer::instance('innomaticcontainer')->abort($adloc->getStr('nopageauth'));
                    }

                    require_once('shared/wui/WuiSessionkey.php');
                    $empty = new WuiSessionKey('mainpage', array('sessionobjectnopage' => 'true', 'value' => $desktopPanel));
            }
            if (is_dir($resource . '-panel')) {
                $panelHome = $resource . '-panel/';
                $panelName = basename($resource);
                $controllerClassName = ucfirst($panelName) . 'PanelController';

                // Checks if view file and definition exist
                if (!include_once($panelHome . $controllerClassName . '.php')) {
                    require_once('innomatic/wui/WuiException.php');
                    throw new WuiException(WuiException::MISSING_CONTROLLER_FILE);
                }
                if (!class_exists($controllerClassName)) {
                    require_once('innomatic/wui/WuiException.php');
                    throw new WuiException(WuiException::MISSING_CONTROLLER_CLASS);
                }
                $controller = new $controllerClassName(InnomaticContainer::MODE_DOMAIN, $panelName);
            } else {
                switch ($desktopPanel) {
                    case 'index':
                    case 'main':
                    case 'menu':
                    case 'logo':
                        include(
                            'innomatic/desktop/layout/domain/'
                            . $desktopPanel
                            . '.php'
                        );
                        break;

                    default:
                        include($resource . '.php');
                }
            }
        } else {
            if (strlen($this->session->get('INNOMATIC_AUTH_USER')))
            {
                require_once('shared/wui/WuiSessionkey.php');

                $empty = new WuiSessionKey('mainpage', array('sessionobjectnopage' => 'true'));
                if ( !strlen( $empty->mValue )
                    or (!file_exists( InnomaticContainer::instance('innomaticcontainer')->getHome() . 'domain/' . $empty->mValue . '.php')
                    and !file_exists( InnomaticContainer::instance('innomaticcontainer')->getHome() . 'domain/' . $empty->mValue . '-panel'))) {
                    // Launches the default desktop background.
                    $mainPageUrl = 'main';
                } else {
                    // Launches the last opened panel with the default view.
                    require_once('innomatic/wui/dispatch/WuiEventsCall.php');
                    $mainPageUrl = WuiEventsCall::buildEventsCallString($empty->mValue, array( array( 'view', 'default', '' ) ) );
                }

                WebAppContainer::instance('webappcontainer')->getProcessor()->getResponse()->addHeader('P3P', 'CP="CUR ADM OUR NOR STA NID"' );
                include('innomatic/desktop/layout/domain/index.php');
            }
        }
    }
}