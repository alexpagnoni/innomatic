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

$wui = Wui::instance('wui');
$wui->loadWidget('button');
$wui->loadWidget('image');
$wui->loadWidget('label');
$wui->loadWidget('link');
$wui->loadWidget('page');
$wui->loadWidget('vertgroup');
$wui->loadWidget('horizgroup');

require_once('innomatic/desktop/layout/DesktopLayout.php');
$layout_mode = DesktopLayout::instance('desktoplayout')->getLayout();

$wuiPage = new WuiPage('page', array('title' => 'Innomatic - '.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->domaindata['domainname'], 'border' => 'false'));
if ($layout_mode == 'horiz') {
    $wuiPage->mArgs['background'] = $wuiPage->mThemeHandler->mStyle['menubackhoriztop'];
    $wuiPage->mArgs['horizbackground'] = 'true';
    $wuiMainVertGroup = new WuiHorizGroup('mainvertgroup', array('groupvalign' => 'middle', 'width' => '100%'));
} else {
    $wuiPage->mArgs['background'] = $wuiPage->mThemeHandler->mStyle['menuback'];
    $wuiMainVertGroup = new WuiVertGroup('mainvertgroup', array('align' => 'center'));
}

$user_data = InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserData();
$wuiMainVertGroup->addChild(new WuiButton('innomaticlogo', array('action' => InnomaticContainer::instance('innomaticcontainer')->getBaseUrl().'/', 'target' => '_top', 'image' => $wuiPage->mThemeHandler->mStyle['headerlogo'], 'highlight' => 'false', 'compact' => 'true')));
$wuiMainVertGroup->addChild(new WuiLabel('label', array('label' => InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->domaindata['domainname'], 'nowrap' => 'true', 'align' => 'center', 'color' => $wuiPage->mThemeHandler->mColorsSet['buttons']['text'])));
$wuiMainVertGroup->addChild(new WuiLabel('labelname', array('label' => $user_data['fname'].' '.$user_data['lname'], 'color' => $wuiPage->mThemeHandler->mColorsSet['buttons']['text'])));

if ($layout_mode == 'vert') {
        $wuiMainVertGroup->addChild(new WuiLabel('logout', array('label' => ' ')));
}

require_once('innomatic/wui/dispatch/WuiEvent.php');
    require_once('innomatic/wui/dispatch/WuiEventsCall.php');
    require_once('innomatic/locale/LocaleCatalog.php');
    $innomatic_menu_locale = new LocaleCatalog('innomatic::root_menu', InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage());

    $buttons_group = new WuiHorizGroup('buttons', array('groupalign' => 'center'));
if ($layout_mode == 'horiz') {
    $buttons_group->addChild(new WuiButton('logout', array('label' => $innomatic_menu_locale->getStr('rootadmin'), 'horiz' => 'true', 'action' => InnomaticContainer::instance('innomaticcontainer')->getBaseUrl().'/root/', 'target' => 'parent', 'compact' => 'true', 'themeimage' => 'configure', 'themeimagetype' => 'mini', 'highlight' => 'false')));
}
    $logout_events_call = new WuiEventsCall(WebAppContainer::instance('webappcontainer')->getProcessor()->getRequest()->getUrlPath().'/domain');
    $logout_events_call->addEvent(new WuiEvent('login', 'logout', ''));

    $buttons_group->addChild(new WuiButton('logout', array('label' => $innomatic_menu_locale->getStr('logout'), 'horiz' => 'true', 'action' => $logout_events_call->getEventsCallString(), 'target' => 'parent', 'compact' => 'true', 'themeimage' => 'exit', 'themeimagetype' => 'mini', 'highlight' => 'false')));

$wuiMainVertGroup->addChild($buttons_group);
$wuiPage->addChild($wuiMainVertGroup);
$wui->addChild($wuiPage);
$wui->render();
?>