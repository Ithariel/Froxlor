<?php

namespace Froxlor\Cli\Action;

use Froxlor\Database\Database;
use Froxlor\SImExporter;
use Froxlor\Settings;
use Froxlor\Cli\CommandLineInterfaceCmd;

class CommandLineInterfaceAction extends \Froxlor\Cli\Action
{
    private $userinfo = [];

    public function __construct($args)
    {
        parent::__construct($args);
    }

    public function run()
    {
        $this->validate();
    }

    /**
     * validates the parsed command line parameters
     *
     * @throws \Exception
     */
    private function validate()
    {
        global $lng;

        $this->checkConfigParam(true);
        $this->parseConfig();

        require FROXLOR_INSTALL_DIR . '/lib/tables.inc.php';

        include_once FROXLOR_INSTALL_DIR . '/lng/english.lng.php';
        include_once FROXLOR_INSTALL_DIR . '/lng/lng_references.php';

        $stmt = Database::prepare("SELECT * FROM `" . TABLE_PANEL_ADMINS . "` WHERE `adminid` = 1");
        $this->userinfo = Database::pexecute_first($stmt);
        $this->userinfo['adminsession'] = 1;
        $this->userinfo['userid'] = 1;

        if (array_key_exists("list-domains", $this->_args)) {
            $this->listdomains();
        }

        /*
        if (array_key_exists("create", $this->_args)) {
            $this->createConfig();
        } elseif (array_key_exists("apply", $this->_args)) {
            $this->applyConfig();
        } elseif (array_key_exists("list-daemons", $this->_args) || array_key_exists("daemon", $this->_args)) {
            CommandLineInterfaceCmd::printwarn("--list-daemons and --daemon only work together with --apply");
        }
        */
    }

    private function listDomains()
    {
        \Froxlor\Api\Commands\Domains::getLocal($this->userinfo)->listing();
    }

    private function parseConfig()
    {
        define('FROXLOR_INSTALL_DIR', $this->_args['froxlor-dir']);
        if (!class_exists('\\Froxlor\\Database\\Database')) {
            throw new \Exception("Could not find froxlor's Database class. Is froxlor really installed to '" . FROXLOR_INSTALL_DIR . "'?");
        }
        if (!file_exists(FROXLOR_INSTALL_DIR . '/lib/userdata.inc.php')) {
            throw new \Exception("Could not find froxlor's userdata.inc.php file. You should use this script only with a fully installed and setup froxlor system.");
        }
    }

    private function checkConfigParam($needed = false)
    {
        if ($needed) {
            if (!isset($this->_args["froxlor-dir"])) {
                $this->_args["froxlor-dir"] = \Froxlor\Froxlor::getInstallDir();
            } elseif (!is_dir($this->_args["froxlor-dir"])) {
                throw new \Exception("Given --froxlor-dir parameter is not a directory");
            } elseif (!file_exists($this->_args["froxlor-dir"])) {
                throw new \Exception("Given froxlor directory cannot be found ('" . $this->_args["froxlor-dir"] . "')");
            } elseif (!is_readable($this->_args["froxlor-dir"])) {
                throw new \Exception("Given froxlor direcotry cannot be read ('" . $this->_args["froxlor-dir"] . "')");
            }
        }
    }
}
