<?php

require_once 'library/CE/NE_MailGateway.php';
require_once 'modules/admin/models/ServerPlugin.php';
require_once dirname(__FILE__) . '/CpanelApi.php';
require_once dirname(__FILE__) . '/xmlapl.php';

class PluginCpanel extends ServerPlugin
{
    public $features = array(
        'packageName' => true,
        'testConnection' => true,
        'showNameservers' => true,
        'directlink' => true,
        'admindirectlink' => true,
        'upgrades' => true
    );

    public $api;
    public $xmlapi;

    public function getVariables()
    {
        $variables = array (
            lang("Name") => array (
                "type" => "hidden",
                "description" => "Used By CE to show plugin - must match how you call the action function names",
                "value" => "CPanel"
            ),
            lang("Description") => array (
                "type" => "hidden",
                "description" => lang("Description viewable by admin in server settings"),
                "value" => lang("cPanel control panel integration")
            ),
            lang("Username") => array (
                "type" => "text",
                "description" => lang("WHM Username"),
                "value" => ""
            ),
            lang("Access Hash") => array (
                "type" => "textarea",
                "description" => lang("API Token"),
                "value" => "",
                "encryptable" => true
            ),
            lang("Use SSL") => array (
                "type" => "yesno",
                "description" => lang("Set NO if you do not have PHP compiled with cURL.  YES if your PHP is compiled with cURL<br><b>NOTE:</b>It is suggested that you keep this as YES"),
                "value" => "1"
            ),
            lang("Failure E-mail") => array (
                "type" => "text",
                "description" => lang("E-mail address Cpanel error messages will be sent to"),
                "value" => ""
            ),
            lang("Actions") => array (
                "type" => "hidden",
                "description" => lang("Current actions that are active for this plugin per server"),
                "value" => "Create,Delete,Suspend,UnSuspend"
            ),
            lang('reseller')  => array(
                'type'          => 'hidden',
                'description'   => lang('Whether this server plugin can set reseller accounts'),
                'value'         => '1',
            ),
            lang('reseller-fieldset')  => array(
                'type'          => 'fieldset',
                'name'          => 'reseller-fieldset',
                'label'   => lang('Reseller Account Specific Fields'),
                'description'   => '',
                'value'         => '1',
            ),
            lang('reseller_acl_fields') => array(
                'type'          => 'hidden',
                'description'   => lang('ACL field for reseller account'),
                'value'         => array(
                    array(
                        'name' => 'acl-name',
                        'type' => 'text',
                        'label' => 'Reseller ACL Name',
                        'description' => lang('If you have a predefined ACL List in WHM you wish to use, enter it here.'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-rslimit-disk',
                        'type' => 'text',
                        'label' => 'Disk space in MB',
                        'description' => lang('If you wish to set Disk space AND Bandwidth as unlimited, leave this field empty.  Note: If you wish to limit Bandwidth but not Disk Space, enter a very large number here'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-rsolimit-disk',
                        'type' => 'check',
                        'label' => 'Disk space overselling allowed' ,
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-rslimit-bw',
                        'type' => 'text',
                        'label' => lang('Bandwidth in MB'),
                        'description' => lang('If you wish to set Disk space AND Bandwidth as unlimited, leave this field empty.  Note: If you wish to limit Disk Space but not Bandwidth, enter a very large number here'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-rsolimit-bw',
                        'type' => 'check',
                        'label' => lang('Bandwidth overselling allowed'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-domain-quota',
                        'type' => 'text',
                        'label' => lang('Domain quota'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-list-accts',
                        'type' => 'check',
                        'label' => lang('List Accounts'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-show-bandwidth',
                        'type' => 'check',
                        'label' => lang('View Account Bandwidth Usage'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-create-acct',
                        'type' => 'check',
                        'label' => lang('Account Creation'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-edit-account',
                        'type' => 'check',
                        'label' => lang('Account Modification'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-suspend-acct',
                        'type' => 'check',
                        'label' => lang('Account Suspension'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-kill-acct',
                        'type' => 'check',
                        'label' => lang('Account Termination'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-upgrade-account',
                        'type' => 'check',
                        'label' => lang('Account Upgrades'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-limit-bandwidth',
                        'type' => 'check',
                        'label' => lang('Bandwidth Limiting Modification'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-edit-mx',
                        'type' => 'check',
                        'label' => lang('Edit MX Entries'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-frontpage',
                        'type' => 'check',
                        'label' => lang('Enabling/Disabling FrontPage Extensions'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-mod-subdomains',
                        'type' => 'check',
                        'label' => lang('Enabling/Disabling SubDomains'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-passwd',
                        'type' => 'check',
                        'label' => lang('Password Modification'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-quota',
                        'type' => 'check',
                        'label' => lang('Quota Modification'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-res-cart',
                        'type' => 'check',
                        'label' => lang('Reset Shopping Cart'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-ssl-gencrt',
                        'type' => 'check',
                        'label' => lang('SSL CSR/CRT Generator'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-ssl',
                        'type' => 'check', 'label' => lang('SSL Site Management'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-demo-setup',
                        'type' => 'check',
                        'label' => lang('Turn an account into a demo account'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-rearrange-accts',
                        'type' => 'check',
                        'label' => lang('Rearrange Accounts'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-clustering',
                        'type' => 'check',
                        'label' => lang('Clustering'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-create-dns',
                        'type' => 'check',
                        'label' => lang('Add DNS'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-edit-dns',
                        'type' => 'check',
                        'label' => lang('Edit DNS'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-park-dns',
                        'type' => 'check',
                        'label' => lang('Park DNS'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-kill-dns',
                        'type' => 'check',
                        'label' => lang('Remove DNS'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-add-pkg',
                        'type' => 'check',
                        'label' => lang('Add/Remove Packages'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-edit-pkg',
                        'type' => 'check',
                        'label' => lang('Edit Packages'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-add-pkg-shell',
                        'type' => 'check',
                        'label' => lang('Allow Creation of Packages With Shell Access'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-allow-unlimited-disk-pkgs',
                        'type' => 'check',
                        'label' => lang('Allow Creation of Packages with Unlimited Diskspace'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-allow-unlimited-pkgs',
                        'type' => 'check' ,
                        'label' => lang('Allow Creation of Packages with Unlimited Features'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-add-pkg-ip',
                        'type' => 'check',
                        'label' => lang('Allow Creation of Packages With a Dedicated IP'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-allow-addoncreate',
                        'type' => 'check' ,
                        'label' => lang('Allow Creation of Packages with Addon Domains'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-allow-parkedcreate',
                        'type' => 'check',
                        'label' => lang('Allow Creation of Packages With Parked Domains'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-onlyselfandglobalpkgs',
                        'type' => 'check' ,
                        'label' => lang('Allow creation of accounts with packages that are global or owned by this user'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-disallow-shell',
                        'type' => 'check',
                        'label' => lang('Never allow creation of accounts with shell access'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-stats',
                        'type' => 'check',
                        'label' => lang('View Account Statistics'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-status',
                        'type' => 'check',
                        'label' => lang('View Server Status'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-restart',
                        'type' => 'check',
                        'label' => lang('Restart Services'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-mailcheck',
                        'type' => 'check',
                        'label' => lang('Mail Trouble Shooter'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-restftp',
                        'type' => 'check',
                        'label' => lang('Resync Ftp Passwords'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-news',
                        'type' => 'check',
                        'label' => lang('News Modification'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl-all',
                        'type' => 'check',
                        'label' => lang('All Features (root access)'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                ),
            ),
            lang('package_addons') => array(
                'type'          => 'hidden',
                'description'   => lang('Supported signup addons variables'),
                'value'         => array(
                    'DISKSPACE', 'BANDWIDTH', 'SSL'
                ),
            ),
            lang('package_vars_values') => array(
                'type'          => 'hidden',
                'description'   => lang('Hosting account parameters'),
                'value'         => array(
                    'dkim' => array(
                        'type'           => 'check',
                        'label'          => 'Enable DKIM?',
                        'description'    => lang('Enable DKIM on this account.'),
                        'value'          => '0',
                    ),
                    'spf' => array(
                        'type'           => 'check',
                        'label'          => 'Enable SPF?',
                        'description'    => lang('Enable SPF on this account.'),
                        'value'          => '0',
                    ),
                    'owner' => array(
                        'type'           => 'check',
                        'label'          => 'Make the reseller account own itself',
                        'description'    => lang('Make the reseller account own itself.'),
                        'value'          => '1',
                    ),
                )
            )
        );
        return $variables;
    }

    /**
     * Sets up the CpanelApi object in order to make requests to the server.
     * @param <type> $args Standard set of arguments in order to make API request.
     */
    public function setup($args)
    {
        if (isset($args['server']['variables']['ServerHostName']) && isset($args['server']['variables']['plugin_cpanel_Username']) && isset($args['server']['variables']['plugin_cpanel_Access_Hash']) && isset($args['server']['variables']['plugin_cpanel_Use_SSL'])) {
            $this->api = new CpanelApi($args['server']['variables']['ServerHostName'], $args['server']['variables']['plugin_cpanel_Username'], $args['server']['variables']['plugin_cpanel_Access_Hash'], $args['server']['variables']['plugin_cpanel_Use_SSL']);

            // xmlapi, all new code should use this
            $this->xmlapi = new xmlapi($args['server']['variables']['ServerHostName']);
            $this->xmlapi->set_user($args['server']['variables']['plugin_cpanel_Username']);
            $this->xmlapi->set_hash(preg_replace("'(\r|\n)'", "", $args['server']['variables']['plugin_cpanel_Access_Hash']));
            $port = ( $args['server']['variables']['plugin_cpanel_Use_SSL'] == true ) ? 2087 : 2086;
            $this->xmlapi->set_port($port);
            $this->xmlapi->set_output('json');
        } else {
            throw new CE_Exception('Missing Server Credentials: please fill out all information when editing the server.');
        }
    }

    /**
     * Emails cPanel server errors.
     * @param String $name
     * @param String $message
     * @param Array $args
     * @return string
     */
    private function emailError($name, $message, $args)
    {
        $error = "cPanel Account " . $name . " Failed. ";
        if (trim($args['server']['variables']['plugin_cpanel_Failure_E-mail'])) {
            $error .= "An email with the Details was sent to " . $args['server']['variables']['plugin_cpanel_Failure_E-mail'] . ".\n";
        }

        if (is_array($message)) {
            $message = implode("\n", trim($message));
        }

        // remove access hash from e-mails
        unset($args['server']['variables']['plugin_cpanel_Access_Hash']);

        CE_Lib::log(1, 'cPanel Error: ' . print_r(array('type' => $name, 'error' => $error, 'message' => $message, 'params' => $args), true));

        if (!empty($args['server']['variables']['plugin_cpanel_Failure_E-mail'])) {
            $mailGateway = new NE_MailGateway();
            $mailGateway->mailMessageEmail(
                $message,
                $args['server']['variables']['plugin_cpanel_Failure_E-mail'],
                "Cpanel Plugin",
                $args['server']['variables']['plugin_cpanel_Failure_E-mail'],
                "",
                "Cpanel Account " . $name . " Failure"
            );
        }
        return $error . nl2br($message);
    }

    public function getPackages($args)
    {
        $this->setup($args);
        return $this->api->packages();
    }

    public function getAccounts($args)
    {
        $this->setup($args);
        return $this->xmlapi->listaccts();
    }

    /**
     * Show views that might be specific to this plugin.
     * This content should be echoed out not returned
     *
     * @param UserPackage $user_package
     * @param CE_Controller_Action $action
     * @return html
     */
    public function show_publicviews($user_package, $action)
    {
        $action->view->addScriptPath(APPLICATION_PATH . '/../plugins/server/cpanel/');
        $product_id = $action->getParam('id', FILTER_SANITIZE_NUMBER_INT);

        echo $action->view->render('cpanel.phtml');
    }

    /**
     * Preps for account creation or update.
     * @param <type> $args
     */
    public function validateCredentials($args)
    {
        //$this->setup($args);
        $args['package']['username'] = trim(strtolower($args['package']['username']));

        $errors = array();

        // Ensure that the username is not test and doesn't contain test
        if (strpos(strtolower($args['package']['username']), 'test') !== false) {
            if (strtolower($args['package']['username']) != 'test') {
                $args['package']['username'] = str_replace('test', '', $args['package']['username']);
            } else {
                $errors[] = 'Domain username can\'t contain \'test\'';
            }
        }

        // Username cannot start with a number
        if (is_numeric(mb_substr(trim($args['package']['username']), 0, 1))) {
            $args['package']['username'] = preg_replace("/^\d*/", '', $args['package']['username']);

            if (is_numeric(mb_substr(trim($args['package']['username']), 0, 1)) || strlen(trim($args['package']['username'])) == 0) {
                $errors[] = 'Domain username can\'t start with a number';
            }
        }

        // Username cannot contain a dash (-)
        if (strpos($args['package']['username'], "-") !== false) {
            $args['package']['username'] = str_replace("-", "", $args['package']['username']);
            $errors[] = 'Domain username can\'t contain dashes';
        }

        // Username cannot contain a space
        if (strpos($args['package']['username'], " ") !== false) {
            $args['package']['username'] = str_replace(" ", "", $args['package']['username']);
            $errors[] = 'Domain username can\'t contain spaces';
        }

        // Username cannot contain an underscore (_)
        if (strpos($args['package']['username'], "_") !== false) {
            $args['package']['username'] = str_replace("_", "", $args['package']['username']);
            $errors[] = 'Domain username can\'t contain underscores';
        }

         // Username cannot contain a period (.)
        if (strpos($args['package']['username'], ".") !== false) {
            $args['package']['username'] = str_replace(".", "", $args['package']['username']);
            $errors[] = 'Domain username can\'t contain periods';
        }

        // Username cannot contain a @
        if (strpos($args['package']['username'], "@") !== false) {
            $args['package']['username'] = str_replace("@", "", $args['package']['username']);
            $errors[] = 'Domain username can\'t contain @';
        }

        // Username cannot be greater than 16 characters (if database prefixing is on in WHM, then it is only 8)
        if (strlen($args['package']['username']) > 16) {
            $args['package']['username'] = mb_substr($args['package']['username'], 0, 16);
        } elseif (strlen(trim($args['package']['username'])) <= 0) {
            $errors[] = 'The cPanel username is blank.';
        } elseif (strlen(trim($args['package']['password'])) <= 0) {
            $errors[] = 'The cPanel password is blank';
        }

        // Only make the request if there have been no errors so far.
        if (count($errors) == 0) {
            if (strpos($args['package']['password'], $args['package']['username']) !== false) {
                $errors[] = 'Domain password can\'t contain domain username';
            }
        }

        // Check if we want to supress errors during signup and just return a valid username
        if (isset($args['noError'])) {
            return $args['package']['username'];
        } else {
            if (count($errors) > 0) {
                CE_Lib::log(4, "plugin_cpanel::validate::error: " . print_r($errors, true));
                throw new CE_Exception($errors[0]);
            }
            return $args['package']['username'];
        }
    }

    //plugin function called after account is activated
    public function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->create($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") . ' has been created.';
    }

    public function create($args)
    {
        $this->setup($args);
        $errors = array();

        if ($args['package']['name_on_server'] == null) {
            throw new CE_Exception("This package is not configured properly.  Missing 'Package Name on Server'.");
        }

        // package add-ons handling
        if (isset($args['package']['addons']['DISKSPACE'])) {
            @$args['package']['acl']['acl-rslimit-disk'] += ((int)$args['package']['addons']['DISKSPACE']);
        }
        if (isset($args['package']['addons']['BANDWIDTH'])) {
            @$args['package']['acl']['acl-rslimit-bw'] += ((int)$args['package']['addons']['BANDWIDTH']) * 1024; // Convert from Gigs to MB
        }
        if (isset($args['package']['is_reseller']) && isset($args['package']['addons']['SSL']) && $args['package']['addons']['SSL'] == 1) {
            $args['package']['acl']['acl-ssl'] = 1;
        }

        $params = array();
        $params['username'] = $args['package']['username'];
        $params['domain'] = $args['package']['domain_name'];
        $params['plan'] = $args['package']['name_on_server'];
        $params['password'] = $args['package']['password'];
        $params['contactemail'] = $args['customer']['email'];
        $params['dkim'] = 0;
        if (isset($args['package']['variables']['dkim']) && $args['package']['variables']['dkim'] == 1) {
            $params['dkim'] = 1;
        }
        $params['spf'] = 0;
        if (isset($args['package']['variables']['spf']) && $args['package']['variables']['spf'] == 1) {
            $params['spf'] = 1;
        }

        $userPackage = new UserPackage($args['package']['id']);
        $userPackage->setCustomField('User Name', $params['username']);

        // Check if we need to set a dedicated IP
        if ($userPackage->getCustomField('Shared') == '0') {
            $params['ip'] = 'yes';
            $params['customip'] = $args['package']['ip'];
        }

        $request = $this->api->call('createacct', $params);

        if ($request->result[0]->status != 1) {
            $errors[] = $this->emailError('Creation', $request->result[0]->statusmsg, $args);
        } elseif ($request->result[0]->status == 1) {
            // setup the reseller permissions if necessary
            if (isset($args['package']['is_reseller']) && $args['package']['is_reseller'] == 1) {
                $this->addReseller($args);
                $this->setResellerACLs($args);
            }
        } else {
            $errors[] = "Error connecting to cPanel server";
        }

        if (count($errors) > 0) {
            CE_Lib::log(4, "plugin_cpanel::create::error: " . print_r($errors, true));
            throw new CE_Exception($errors[0]);
        }
        return;
    }

    public function doUpdate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->update($this->buildParams($userPackage, $args));
        return $userPackage->getCustomField("Domain Name") . ' has been updated.';
    }

    public function update($args)
    {
        $this->setup($args);
        $args = $this->updateArgs($args);
        $errors = array();
        // Loop over changes array
        foreach ($args['changes'] as $key => $value) {
            switch ($key) {
                case 'username':
                    $request = $this->api->call('modifyacct', array('user' => $args['package']['username'], 'newuser' => $value));
                    if ($request->result[0]->status != 1) {
                        $errors[] = $this->emailError('Username Change', $request->result[0]->statusmsg, $args);
                    }
                    // Internal fix, incase we are also changing the domain name.
                    $args['package']['username'] = $value;
                    break;

                case 'password':
                    $request = $this->api->call('passwd', array('user' => $args['package']['username'], 'pass' => $value));
                    // passwd has a different json struct.
                    if ($request->passwd[0]->status != 1) {
                        $errors[] = $this->emailError('Password Change', $request->passwd[0]->statusmsg, $args);
                    }
                    break;

                case 'domain':
                    $request = $this->api->call('modifyacct', array('user' => $args['package']['username'], 'domain' => $value));
                    if ($request->result[0]->status != 1) {
                        $errors[] = $this->emailError('Domain Change', $request->result[0]->statusmsg, $args);
                    }
                    $args['package']['domain_name'] = $value;
                    break;

                case 'ip':
                    $request = $this->api->call('setsiteip', array('user' => $args['package']['username'], 'ip' => $value));
                    if ($request->result[0]->status != 1) {
                        $errors[] = $this->emailError('IP Change', $request->result[0]->statusmsg, $args);
                    }
                    break;

                case 'package':
                    $request = $this->api->call('changepackage', array('user' => $args['package']['username'], 'pkg' => $args['package']['name_on_server']));
                    if ($request->result[0]->status != 1) {
                        $errors[] = $this->emailError('Plan Change', $request->result[0]->statusmsg, $args);
                    } else {
                        // setup or delete the reseller permissions if necessary
                        if (isset($args['package']['is_reseller']) && $args['package']['is_reseller'] == 1) {
                            if (!isset($args['changes']['leave_reseller'])) {
                                $this->addReseller($args);
                                $this->setResellerACLs($args);
                            }
                        } else {
                            // If the old package was a reseller, we need to remove it.
                            if (isset($args['changes']['remove_reseller']) && $args['changes']['remove_reseller'] == 1) {
                                $this->removeReseller($args);
                            }
                        }
                    }
                    break;
            }
        }

        if (count($errors) > 0) {
            CE_Lib::log(4, "plugin_cpanel::update::error: " . print_r($errors, true));
            throw new CE_Exception($errors[0]);
        }
    }

    public function doDelete($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->delete($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") . ' has been deleted.';
    }

    public function delete($args)
    {
        $this->setup($args);
        $args = $this->updateArgs($args);

        if (isset($args['package']['is_reseller'])) {
            $request = $this->xmlapi->terminatereseller($args['package']['username']);
        } else {
            $request = $this->api->call('removeacct', ['user' => $args['package']['username']]);
        }

        if ($request->result[0]->status != 1) {
            $error = $this->emailError('Deletion', $request->result[0]->statusmsg, $args);
        }

        if (isset($error)) {
            CE_Lib::log(4, "plugin_cpanel::delete::error: " . $error);
            throw new CE_Exception($error);
        }
    }

    public function doSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->suspend($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") . ' has been suspended.';
    }

    public function suspend($args)
    {
        $this->setup($args);
        $args = $this->updateArgs($args);
        $action = ( isset($args['package']['is_reseller']) ) ? 'suspendreseller' : 'suspendacct';
        $request = $this->api->call($action, array('user' => $args['package']['username']));

        if ($request->result[0]->status != 1) {
            $error = $this->emailError('Suspension', $request->result[0]->statusmsg, $args);
        }

        if (isset($error)) {
            CE_Lib::log(4, "plugin_cpanel::suspend::error: " . $error);
            throw new CE_Exception($error);
        }
    }

    public function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->unsuspend($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") . ' has been unsuspended.';
    }

    public function unsuspend($args)
    {
        $this->setup($args);
        $args = $this->updateArgs($args);
        $action = ( isset($args['package']['is_reseller']) ) ? 'unsuspendreseller' : 'unsuspendacct';
        $request = $this->api->call($action, array('user' => $args['package']['username']));

        if ($request->result[0]->status != 1) {
            $error = $this->emailError('Unsuspension', $request->result[0]->statusmsg, $args);
        }

        if (isset($error)) {
            CE_Lib::log(4, "plugin_cpanel::unsuspend::error: " . $error);
            throw new CE_Exception($error);
        }
    }

    public function testConnection($args)
    {
        CE_Lib::log(4, 'Testing connection to cPanel server');
        $this->setup($args);
        try {
            $response = $this->api->packages();
        } catch (Exception $e) {
            throw new CE_Exception($e->getMessage());
        }

        if (!is_array($response)) {
            throw new CE_Exception("Connection to server failed.");
        }
    }

    public function setResellerACLs($args)
    {
        $this->setup($args);
        $args = $this->updateArgs($args);
        $resourceLimits = array('acl-rslimit-disk', 'acl-rsolimit-disk', 'acl-rslimit-bw', 'acl-rsolimit-bw', 'acl-domain-quota');

        $acls = array();
        if (isset($args['package']['acl']['acl-name']) && $args['package']['acl']['acl-name'] != '') {
            $acls['acllist'] = $args['package']['acl']['acl-name'];
        } else {
            foreach ($args['package']['acl'] as $key => $value) {
                if (mb_substr($key, 0, 4) == 'acl-') {
                    if (in_array($key, $resourceLimits)) {
                        $key = mb_substr($key, 4);
                    }
                    $acls[$key] = $value;
                }
            }
            if ((isset($args['package']['acl']['acl-rslimit-disk']) && $args['package']['acl']['acl-rslimit-disk']) || (isset($args['package']['acl']['acl-rslimit-bw']) && $args['package']['acl']['acl-rslimit-bw'])) {
                $acls['resreslimit'] = 1;
            }

            // Only send ACLs that are set to 1.  Even if set to 0, cPanel still enables them.
            foreach ($acls as $key => $value) {
                if ($value != '1') {
                    unset($acls[$key]);
                }
                // This key is for domain quota, which is sent as a seperate API call (setresellerlimits).
                if ($key == 'acl-domain-quota') {
                    unset($acls[$key]);
                }
            }
        }

        $request = $this->api->call('setacls', array_merge(array('reseller' => $args['package']['username']), $acls));

        if ($request->result[0]->status != 1) {
            $error = $request->result[0]->statusmsg . ' setacls';
            $this->emailError('Setup Reseller', $error, $args);
        }

        $tmpArgs = array();

        // Setup domain quota for the reseller
        if ((isset($args['package']['acl']['acl-domain-quota']) && $args['package']['acl']['acl-domain-quota'] > 0 )) {
            $tmpArgs['enable_account_limit'] = 1;
            $tmpArgs['account_limit'] = $args['package']['acl']['acl-domain-quota'];
        }

        if ((isset($args['package']['acl']['acl-rslimit-disk']) && $args['package']['acl']['acl-rslimit-disk']) || (isset($args['package']['acl']['acl-rslimit-bw']) && $args['package']['acl']['acl-rslimit-bw'])) {
            $tmpArgs['enable_resource_limits'] = 1;
            $tmpArgs['bandwidth_limit'] = $args['package']['acl']['acl-rslimit-bw'];
            $tmpArgs['diskspace_limit'] = $args['package']['acl']['acl-rslimit-disk'];

            if ((isset($args['package']['acl']['acl-rsolimit-disk']) && $args['package']['acl']['acl-rsolimit-disk'] == 1) || isset($args['package']['acl']['rsolimit-bw']) && $args['package']['acl']['rsolimit-bw']) {
                $tmpArgs['enable_overselling'] = 1;
                $tmpArgs['enable_overselling_bandwidth'] = $args['package']['acl']['acl-rsolimit-bw'];
                $tmpArgs['enable_overselling_diskspace'] = $args['package']['acl']['acl-rsolimit-disk'];
            }
        }

        if (count($tmpArgs) > 0) {
            $request = $this->api->call('setresellerlimits', array_merge(array('user' => $args['package']['username']), $tmpArgs));
            if ($request->result[0]->status != 1) {
                $error = $request->result[0]->statusmsg . ' setupresellerlimits';
                $this->emailError('Setup Reseller Limits', $error, $args);
            }
        }

        if (isset($error)) {
            CE_Lib::log(4, "plugin_cpanel::setupreselleracls::error: " . $error);
            throw new CE_Exception($error);
        }
    }

    private function addReseller($args)
    {
        $this->setup($args);
        $args = $this->updateArgs($args);

        $makeOwner = 0;
        if (isset($args['package']['variables']['owner']) && $args['package']['variables']['owner'] == 1) {
            $makeOwner = 1;
        }

        $request = $this->api->call('setupreseller', array('user' => $args['package']['username'], 'makeowner' => $makeOwner));

        if ($request->result[0]->status != 1) {
            $error = $this->emailError('Setup Reseller', $request->result[0]->statusmsg, $args);
        }

        if (isset($error)) {
            CE_Lib::log(4, "plugin_cpanel::setupreseller::error: " . $error);
            throw new CE_Exception($error);
        }
    }

    private function removeReseller($args)
    {
        $this->setup($args);
        $request = $this->api->call('unsetupreseller', array('user' => $args['package']['username'], 'makeowner' => 1));

        if ($request->result[0]->status != 1) {
            $error = $this->emailError('Unsetup Reseller', $request->result[0]->statusmsg, $args);
        }

        if (isset($error)) {
            CE_Lib::log(4, "plugin_cpanel::unsetupreseller::error: " . $error);
            throw new CE_Exception($error);
        }
    }

    private function updateArgs($args)
    {
        $args['package']['username'] = trim(strtolower($args['package']['username']));
        if (isset($args['changes']['username'])) {
            $args['changes']['username'] = trim(strtolower($args['changes']['username']));
        }

        return $args;
    }

    public function getAvailableActions($userPackage)
    {
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $args = $this->updateArgs($args);
        $actions = array();

        if ($args['package']['username'] == '') {
            // no username, so just pass create, and return
            $actions[] = 'Create';
            return $actions;
        }

        try {
            $request = $this->api->call('accountsummary', array('user' => $args['package']['username']));
            $actions[] = 'Delete';
            if ($request->acct[0]->suspended == 1) {
                $actions[] = 'UnSuspend';
            } else {
                $actions[] = 'Suspend';
            }
        } catch (Exception $e) {
            $actions[] = 'Create';
        }
        return $actions;
    }

    public function getDirectLink($userPackage, $getRealLink = true, $fromAdmin = false, $isReseller = false)
    {
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $params = [];
        $params['user'] = trim($args['package']['username']);
        $params['service'] = 'cpaneld';
        $params['api.version'] = '1';

        $linkText = $this->user->lang('Login to cPanel');
        if ($isReseller && isset($args['package']['is_reseller']) && $args['package']['is_reseller'] == 1) {
            $params['service'] = 'whostmgrd';
            $linkText = $this->user->lang('Login to WHM');
        }

        if ($fromAdmin) {
            $cmd = 'panellogin';
            if ($isReseller && isset($args['package']['is_reseller']) && $args['package']['is_reseller'] == 1) {
                $cmd = 'panellogin_reseller';
            }
            return [
                'cmd' => $cmd,
                'label' => $linkText
            ];
        } elseif ($getRealLink) {
            $result = $this->api->call('create_user_session', $params);

            return array(
                'fa' => 'fa fa-user fa-fw',
                'link' => $result->data->url,
                'text' =>  $linkText,
                'form' => ''
            );
        } else {
            $link = 'index.php?fuse=clients&controller=products&action=openpackagedirectlink&packageId=' . $userPackage->getId() . '&sessionHash=' . CE_Lib::getSessionHash();

            if ($isReseller && isset($args['package']['is_reseller']) && $args['package']['is_reseller'] == 1) {
                $link .= '&isReseller=1';
            }

            return [
                'fa' => 'fa fa-user fa-fw',
                'link' => $link,
                'text' => $linkText,
                'form' => ''
            ];
        }
    }

    public function dopanellogin($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $response = $this->getDirectLink($userPackage);
        return $response['rawlink'];
    }

    public function dopanellogin_reseller($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $response = $this->getDirectLink($userPackage, true, false, true);
        return $response['rawlink'];
    }

    public function getAdminDirectLink($args)
    {
        $params = [];
        $params['user'] = trim($args['plugin_cpanel_Username']);
        $params['service'] = 'whostmgrd';
        $params['api.version'] = '1';

        $api = new CpanelApi(
            $args['ServerHostName'],
            $args['plugin_cpanel_Username'],
            $args['plugin_cpanel_Access_Hash'],
            $args['plugin_cpanel_Use_SSL']
        );

        $result = $api->call('create_user_session', $params);
        if ($result->data->url != '') {
            return $result->data->url;
        }
        return false;
    }
}
