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

require_once('innomatic/core/InnomaticContainer.php');

/**
 * 
 * @package WUI
 */
class WuiValidatorHelper
{

    public static function validate() {
        static $validated = false;

        if (!$validated) {
            require_once('innomatic/core/InnomaticContainer.php');
            $innomatic = InnomaticContainer::instance('innomaticcontainer');
            if ($innomatic->getState() != InnomaticContainer::STATE_SETUP) {
                $validators_query = $innomatic->getDataAccess()->execute('SELECT file FROM wui_validators');
                if ($validators_query) {
                    // TODO old
                    while (!$validators_query->eof) {
                        if (file_exists(InnomaticContainer::instance('innomaticcontainer')->getHome().'core/classes/shared/wui/validators/'.$validators_query->getFields('file'))) {
                            include_once(InnomaticContainer::instance('innomaticcontainer')->getHome().'core/classes/shared/wui/validators/'.$validators_query->getFields('file'));
                        }
                        $validators_query->moveNext();
                    }
                }
                $validators_query->free();
                $validated = true;
            }
        }
    }
}