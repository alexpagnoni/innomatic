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

require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/theme/WuiTheme.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');

/*!
 @class WuiWidget
 @abstract Base widget class.
 @discussion Base widget class, to be extended by every widget handler.
 */
abstract class WuiWidget
{
    /*! @var mrWuiDisp dispatcher class - Wui internal dispatcher. */
    public $mrWuiDisp;
    /*! @var mLayout string - Component layout. */
    public $mLayout;
    /*! @var mName string - Component unique name. */
    public $mName;
    /*! @var mArgs array - Array of element arguments and attributes. */
    public $mArgs = array();
    /*! @var mTheme string - Theme applied to the element. */
    public $mTheme;
    /*! @var mThemeHandler WuiTheme class - Theme handler. */
    public $mThemeHandler;
    /*! @var mDispEvents array - Dispatcher events. */
    public $mDispEvents = array();
    /*! @var mComments boolean - Set to TRUE if element should contain comment
    blocks. */
    public $mComments;
    /*! @var mUseSession boolean - TRUE if the widget should use the stored 
    session parameters. */
    public $mUseSession;
    /*! @var mSessionObjectName string - Name of this widget as object
    in the session. */
    public $mSessionObjectName;
    public $mSessionObjectUserName;
    public $mSessionObjectNoUser;
    public $mSessionObjectNoPage;
    public $mSessionObjectNoType;
    public $mSessionObjectNoName;
    public $events = array();

    /*!
     @function WuiWidget
     @abstract Class constructor.
     @discussion Class constructor.
     @param elemName string - Component unique name.
     @param elemArgs array - Array of element arguments and attributes.
     @param elemTheme string - Theme to be applied to the element.
    Currently unuseful.
     @param dispEvents array - Dispatcher events.
     */
    public function __construct(
        $elemName,
        $elemArgs = '',
        $elemTheme = '',
        $dispEvents = ''
    )
    {
        $this->mName = $elemName;
        $this->mArgs = &$elemArgs;
        $this->mComments = Wui::showSourceComments();

        if (is_array($dispEvents)) {
            $this->mDispEvents = &$dispEvents;
        }

        $currentWuiTheme = Wui::instance('wui')->getThemeName();
        if (strlen($elemTheme) and $elemTheme != $currentWuiTheme) {
            $this->mTheme = $elemTheme;
            
            $this->mThemeHandler = new WuiTheme(
                InnomaticContainer::instance(
                    'innomaticcontainer'
                )->getDataAccess(),
                $this->mTheme
            );
        } else {
            $this->mTheme = $currentWuiTheme;
            $this->mThemeHandler = Wui::instance('wui')->getTheme();
        }

        if (
            isset($this->mArgs['usesession'])
            and ($this->mArgs['usesession'] == 'true'
            or $this->mArgs['usesession'] == 'false')
        ) {
            $this->mUseSession = $this->mArgs['usesession'];
        } else {
            $this->mUseSession = 'true';
        }

        if (isset($this->mArgs['sessionobjectnouser'])) {
            $this->mSessionObjectNoUser = $this->mArgs['sessionobjectnouser'];
        }
        if (isset($this->mArgs['sessionobjectnopage'])) {
            $this->mSessionObjectNoPage = $this->mArgs['sessionobjectnopage'];
        }
        if (isset($this->mArgs['sessionobjectnotype'])) {
            $this->mSessionObjectNoType = $this->mArgs['sessionobjectnotype'];
        }
        if (isset($this->mArgs['sessionobjectnoname'])) {
            $this->mSessionObjectNoName = $this->mArgs['sessionobjectnoname'];
        }
        if (isset($this->mArgs['sessionobjectusername'])) {
            $this->mSessionObjectUserName =
                $this->mArgs['sessionobjectusername'];
        }

        $this->mSessionObjectName = ($this->mSessionObjectNoUser == 'true' ? ''
            : (is_object(InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()) ?
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName() : 'root')
             .'_') . ($this->mSessionObjectNoPage == 'true' ? '' : $_SERVER['PHP_SELF'].'_') .
             ($this->mSessionObjectNoType == 'true' ? '' : get_class($this).'_') .
             ($this->mSessionObjectNoName == 'true' ? '' : $this->mName) .
             (strlen($this->mSessionObjectUserName) ? '_'.$this->mSessionObjectUserName : '');
    }

    /*!
     @function build
     @abstract Builds the structure.
     @discussion Builds the structure.
     @param rwuiDisp WuiDispatcher class - Wui internal dispatcher handler.
     @result True it the structure has been built by the member.
     */
    public function build(WuiDispatcher $rwuiDisp)
    {
        $this->mrWuiDisp = $rwuiDisp;
        return $this->generateSource();
    }

    /*!
     @function Render
     @abstract Renders the structure.
     @discussion If the structure has not been built, it will call the
    Wui->Build() member.
     @result True if the structure has been rendered.
     */
    public function &render()
    {
        return $this->mLayout;
    }

    public function destroy()
    {
        $this->mLayout = '';
        $this->mArgs = array();
    }

    /*!
     @function generateSource
     @abstract Wrapped build function, redefined by extension classes.
     @discussion Wrapped build function, redefined by extension classes.
     @result Always true if not extended.
     */
    protected function generateSource()
    {
        $this->mLayout = '';
    }

    /*!
     @function StoreSession
     @abstract Stores widget parameters to be saved in the session.
     @param args array - Array of the parameters to be stored.
     @result Always true.
     */
    public function storeSession($args)
    {
        if ($this->mUseSession) {
            require_once(
                'innomatic/desktop/controller/DesktopFrontController.php'
            );
            DesktopFrontController::instance(
                'desktopfrontcontroller'
            )->session->put(
                $this->mSessionObjectName, serialize($args)
            );
        }
    }

    /*!
     @function RetrieveSession
     @abstract Retrieves stored widget parameters.
     @result The array of the stored parameters, if any.
     */
    public function retrieveSession()
    {
        require_once('innomatic/desktop/controller/DesktopFrontController.php');
        if (
            $this->mUseSession == 'true'
            and DesktopFrontController::instance(
                'desktopfrontcontroller'
            )->session->isValid($this->mSessionObjectName)
        ) {
            return unserialize(
                DesktopFrontController::instance(
                    'desktopfrontcontroller'
                )->session->get($this->mSessionObjectName)
            );
        } else {
            return false;
        }
    }

    /**
      * Adds a Javascript event.
     * @param string $event Event name, without the "on" prefix, e.g. "onclick" must be given as "click".
     * @param string $call Javascript function to be called.
     */
    public function addEvent($event, $call)
    {
        $this->events[$event][] = $call;
    }

    /**
     * Builds the event content string, e.g. action_a();action_b().
     * @param string $event Event name.
     * @return string Javascript functions list.
     */
    public function getEventString($event)
    {
        if (!isset($this->events[$event])) {
            return '';
        }
        return implode(';', $this->events[$event]);
    }

    /**
     * Builds the event string containing the event plus the the Javascript
     * function calls, e.g. onclick="action_a();action_b()".
     * @param String $event Event name.
     * @return string Event with Javascript functions list, prepared for HTML.
     */
    public function getEventCompleteString($event)
    {
        $string = $this->getEventString($event);
        if (strlen($string)) {
            return 'on'.$event.'="'.$string.'"';
        }
        return '';
    }

    /**
     * Builds the events strings containing the events plus the the Javascript
     * function calls, e.g. onclick="action_a();action_b()"
     * onmouseover="action_c()".
     * @param String $event Event name.
     * @return string Event with Javascript functions list, prepared for HTML.
     */
    public function getEventsCompleteString()
    {
        if (!count($this->events)) {
            return '';
        }

        $string = '';
        foreach ($this->events as $eventName => $calls) {
            $string = ' on'.$eventName.'="'.implode(';', $calls).'"';
        }
        return $string;
    }
}