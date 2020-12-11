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

        if (array_key_exists("list-admins", $this->_args)) {
            $this->listAdmins();
        } elseif (array_key_exists("list-customers", $this->_args)) {
            $this->listCustomers();
        } elseif (array_key_exists("list-domains", $this->_args)) {
            $this->listDomains();
        } elseif (array_key_exists("get-admin", $this->_args)) {
            if ($this->_args['get-admin'] === true) {
                CommandLineInterfaceCmd::printerr("Option --get-admin requires id or loginname");
            }
            $this->getAdmin($this->_args['get-admin']);
        } elseif (array_key_exists("get-customer", $this->_args)) {
            if ($this->_args['get-customer'] === true) {
                CommandLineInterfaceCmd::printerr("Option --get-customer requires id or loginname");
            }
            $this->getCustomer($this->_args['get-customer']);
        } elseif (array_key_exists("get-domain", $this->_args)) {
            if ($this->_args['get-domain'] === true) {
                CommandLineInterfaceCmd::printerr("Option --get-domain requires id domainname");
            }
            $this->getDomain($this->_args['get-domain']);
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

    private function listAdmins()
    {
        $result = \Froxlor\Api\Commands\Admins::getLocal($this->userinfo)->listing();
        $list = json_decode($result, true)['data']['list'];
        foreach ($list as $domain) {
            CommandLineInterfaceCmd::println("${domain['id']}: ${domain['domain_ace']}");
        }
    }

    private function listCustomers()
    {
        $result = \Froxlor\Api\Commands\Customers::getLocal($this->userinfo)->listing();
        $list = json_decode($result, true)['data']['list'];
        foreach ($list as $domain) {
            CommandLineInterfaceCmd::println("${domain['id']}: ${domain['domain_ace']}");
        }
    }

    private function listDomains()
    {
        $result = \Froxlor\Api\Commands\Domains::getLocal($this->userinfo)->listing();
        $list = json_decode($result, true)['data']['list'];
        foreach ($list as $domain) {
            CommandLineInterfaceCmd::println("${domain['id']}: ${domain['domain_ace']}");
        }
    }

    private function getAdmin($search_param)
    {
        $data = [];
        if (is_numeric($search_param)) {
            $data['id'] = $search_param;
        } else {
            $data['loginname'] = $search_param;
        }
        $result = \Froxlor\Api\Commands\Admins::getLocal($this->userinfo, $data)->get();
        $decoded = json_decode($result, true)['data'];
        foreach ($decoded as $key => $value) {
            CommandLineInterfaceCmd::println("$key:\t$value");
        }
    }

    private function getCustomer($search_param)
    {
        $data = [];
        if (is_numeric($search_param)) {
            $data['id'] = $search_param;
        } else {
            $data['loginname'] = $search_param;
        }
        $result = \Froxlor\Api\Commands\Customers::getLocal($this->userinfo, $data)->get();
        $decoded = json_decode($result, true)['data'];
        foreach ($decoded as $key => $value) {
            CommandLineInterfaceCmd::println("$key:\t$value");
        }
    }

    private function getDomain($search_param)
    {
        $data = [];
        if (is_numeric($search_param)) {
            $data['id'] = $search_param;
        } else {
            $data['domainname'] = $search_param;
        }
        $result = \Froxlor\Api\Commands\Domains::getLocal($this->userinfo, $data)->get();
        $decoded = json_decode($result, true)['data'];
        foreach ($decoded as $key => $value) {
            CommandLineInterfaceCmd::println("$key:\t$value");
        }
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
