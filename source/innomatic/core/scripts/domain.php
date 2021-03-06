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
 * @since      Class available since Release 5.1
 */

require_once('scripts_container.php');
$script = \Innomatic\Scripts\ScriptContainer::instance('\Innomatic\Scripts\ScriptContainer');

if (!isset ($argv[1])) $argv[1] = '';

try {
    switch ($argv[1]) {
        case '-h' :
            print('Usage: php innomatic/core/scripts/domain.php command argument'."\n");
            print("\n");
            print('Supported commands:'."\n");
            print('    create domainname description pwd  Creates a new domain'."\n");
            print('    remove domainname                  Removes a domain'."\n");
            print('    enable domainname                  Enables a disabled domain'."\n");
            print('    disable domainname                 Disables a domain'."\n");
            print('    applist domainname                 Retrieves a list of enabled applications'."\n");
            print('    appenable domainname application   Disables a domain'."\n");
            print('    appdisable domainname application  Disables a domain'."\n");
            $script->cleanExit();
            break;

        case 'enable' :
            $domain = new \Innomatic\Domain\Domain(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), $argv[2], null);
            if ($domain->enable()) {
                print("Domain $argv[2] enabled\n");
                $script->cleanExit();
            } else {
                print("Domain $argv[2] not enabled\n");
                $script->cleanExit(1);
            }
            break;

        case 'disable' :
            $domain = new \Innomatic\Domain\Domain(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), $argv[2], null);
            if ($domain->disable()) {
                print("Domain $argv[2] disabled\n");
                $script->cleanExit();
            } else {
                print("Domain $argv[2] not disabled\n");
                $script->cleanExit(1);
            }
            break;

        case 'create' :
            $domain = new \Innomatic\Domain\Domain(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), 0, null);
            $data['domainid'] = $argv[2];
            $data['domainname'] = $argv[3];
            $data['domainpassword'] = $argv[4];

            if ($domain->create($data)) {
                print("Domain $argv[2] created\n");
                $script->cleanExit();
            } else {
                print("Domain $argv[2] not created\n");
                $script->cleanExit(1);
            }
            break;

        case 'remove' :
            $domain = new \Innomatic\Domain\Domain(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), $argv[2], null);
            if ($domain->remove()) {
                print("Domain $argv[2] removed\n");
                $script->cleanExit();
            } else {
                print("Domain $argv[2] not removed\n");
                $script->cleanExit(1);
            }
            break;

        case 'applist' :
            $domain = new \Innomatic\Domain\Domain(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), $argv[2], null);
            $list = $domain->getEnabledApplications();
            foreach($list as $app) {
                print($app."\n");
            }
            $script->cleanExit();
            break;

        case 'appenable' :
            $domain = new \Innomatic\Domain\Domain(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), $argv[2], null);
            $appid = \Innomatic\Application\Application::getAppIdFromName($argv[3]);
            if ($domain->enableApplication($appid)) {
                print("Application $argv[3] enabled to domain $argv[2]\n");
                $script->cleanExit();
            } else {
                print("Application $argv[3] not enabled to domain $argv[2]\n");
                $script->cleanExit(1);
            }
            break;

        case 'appdisable' :
            $domain = new \Innomatic\Domain\Domain(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), $argv[2], null);
            $appid = \Innomatic\Application\Application::getAppIdFromName($argv[3]);
            if ($domain->disableApplication($appid)) {
                print("Application $argv[3] disabled from domain $argv[2]\n");
                $script->cleanExit();
            } else {
                print("Application $argv[3] not disabled from domain $argv[2]\n");
                $script->cleanExit(1);
            }
            break;

        default :
            print('Usage: php innomatic/core/scripts/domain.php command'."\n");
            print('Type domain.php -h for a list of supported commands'."\n");
    }
} catch (\Exception $e) {
    echo $e;
}

$script->cleanExit();
