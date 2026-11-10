<?php

/**
* Provides an interface to issue commands to a remote WHM server.
* @link http://docs.cpanel.net/twiki/bin/view/AllDocumentation/AutomationIntegration/XmlApi
* @author Jamie Chung
* @email jamie@clientexec.com
* @version 7.08.2010
*/
class CpanelApi
{
    protected $host;
    protected $username;
    protected $hash;
    protected $ssl = false;
    var $port;
    var $schema;
    var $type = 'json';
    var $result;
    var $request;
    var $url;

    /**
    * Let's start her up!
    * @param string $host Host name of server
    * @param string $username Username with WHM privileges
    * @param string $hash Access hash
    * @param boolean $ssl Use an SSL connection
    * @param string $type Output type
    */
    public function __construct($host, $username, $hash, $ssl = false, $type = 'json')
    {
        $this->host = $host;
        $this->username = $username;
        $this->hash = $hash;
        $this->ssl = $ssl;
        $this->port = ( $ssl == true ) ? 2087 : 2086;
        $this->schema = ( $ssl == true ) ? 'https://' : 'http://';
        $this->type = 'json';
    }

    /**
     * Makes a request through the cPanel API
     * @link http://docs.cpanel.net/twiki/bin/view/AllDocumentation/AutomationIntegration/XmlApi
     * @param string $function
     * @param array $params
     * @return object
     */
    public function call(string $function, array $params = []): object
    {
        if (!function_exists('curl_init')) {
            CE_Lib::debug(1, 'cURL is required in order to connect to cPanel');
            throw new CE_Exception('cURL is required in order to connect to cPanel');
        }

        $this->url = $url = $this->schema . $this->host . ':' . $this->port . '/json-api/' . $function;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HEADER, false);

        // Parameters go in the body so passwords never land in the WHM access log
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        // Verify the server certificate against Composer's CA bundle
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            curl_setopt($ch, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($ch, CURLOPT_CAINFO, $caPathOrFile);
        }

        $headers = [];
        $headers[0] = "Authorization: WHM {$this->username}:" . preg_replace("'(\r|\n)'", "", $this->hash);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = curl_exec($ch);
        if ($data === false) {
            $errorNumber = curl_errno($ch);
            $errorDesc = curl_error($ch);
            // 51 = peer certificate rejected, 60 = certificate could not be verified (includes hostname mismatch on newer libcurl)
            if (in_array($errorNumber, [51, 60])) {
                $errorDesc .= '. Make sure ' . $this->host . ' has a valid SSL certificate and that the server hostname in Clientexec matches the name on the certificate.';
            } elseif ($errorDesc == '' && $errorNumber == 3) {
                $errorDesc = 'Server Hostname is not valid.';
            } elseif ($errorDesc == '') {
                $errorDesc = 'Error number ' . $errorNumber;
            }
            $error = 'cPanel API Request (' . $function . ') / cURL Error: ' . $errorDesc;
            CE_Lib::log(1, $error);
            throw new Exception($error);
        }

        // cPanel returns UTF-8, so decode as-is; curly quotes are normalized for plain-text error display
        $data = str_replace(['“', '”'], "'", $data);
        $result = $this->result = json_decode($data);

        $this->request = ['url' => $this->url, 'function' => $function, 'params' => $params, 'raw' => $data, 'json' => $result];

        $loggedParams = $params;
        foreach ($loggedParams as $key => $value) {
            if (mb_stripos((string) $key, 'pass') !== false) {
                $loggedParams[$key] = '********';
            }
        }
        CE_Lib::log(3, 'cPanel API Request: ' . print_r(array_merge($this->request, ['params' => $loggedParams]), true));

        if (!is_object($result)) {
            // invalid json... check raw for an SSL error
            if (strpos($data, 'SSL encryption is required for access to this server')) {
                CE_Lib::log(1, "Error from cPanel: SSL encryption is required for access to this server.");
                throw new Exception('Error from cPanel: SSL encryption is required for access to this server.');
            }
            if (stristr($data, '<html>') == true && stristr($data, 'refresh') == true) {
                throw new CE_Exception('SSL is required to connect');
            }
            throw new Exception("cPanel call method: Invalid JSON please check your connection");
        }

        // Rejected before dispatch; through the cpanel proxy this means the account, not the server credentials
        if (isset($result->cpanelresult->error) && $result->cpanelresult->error == 'Access denied') {
            if ($function == 'cpanel') {
                $accountUser = $params['cpanel_jsonapi_user'] ?? '';
                throw new CE_Exception('Access denied to cPanel account ' . $accountUser . '. The account may not exist on this server.');
            }
            throw new CE_Exception('Access denied, please double check Username and API Token');
        }

        // UAPI (apiversion 3) responses carry status and errors inside a result object
        if (isset($result->apiversion) && $result->apiversion == 3 && isset($result->result) && is_object($result->result)) {
            if (empty($result->result->status)) {
                $errors = (isset($result->result->errors) && is_array($result->result->errors)) ? implode(' ', $result->result->errors) : 'Unknown error';
                throw new CE_Exception('cPanel returned an error: ' . $errors);
            }
            return $result;
        }

        if (isset($result->data->result) && $result->data->result == 0) {
            throw new CE_Exception('cPanel returned an error: ' . $result->data->reason);
        }

        if (isset($result->status) && $result->status == 0) {
            throw new CE_Exception('cPanel returned an error: ' . $result->statusmsg);
        }

        if (isset($result->result) && is_array($result->result) && isset($result->result[0]) && $result->result[0]->status == 0) {
            throw new CE_Exception('cPanel returned an error: ' . $result->result[0]->statusmsg);
        }

        return $result;
    }

    /**
     * Calls a UAPI function as the given cPanel account through the WHM API proxy.
     * @param string $user cPanel account username
     * @param string $module UAPI module (e.g. Email)
     * @param string $function UAPI function (e.g. list_pops)
     * @param array $params UAPI function parameters
     * @return mixed The data returned by the UAPI function
     */
    public function uapi(string $user, string $module, string $function, array $params = []): mixed
    {
        // Proxy parameters are merged last so function params can never override the target account
        $params = array_merge($params, [
            'cpanel_jsonapi_user' => $user,
            'cpanel_jsonapi_apiversion' => 3,
            'cpanel_jsonapi_module' => $module,
            'cpanel_jsonapi_func' => $function,
        ]);

        $result = $this->call('cpanel', $params);
        return $result->result->data;
    }

    /**
    * Gets all packages available to the cpanel user.
    * @link http://docs.cpanel.net/twiki/bin/view/AllDocumentation/AutomationIntegration/ListPackages
    * @return Array of packages (key = package name, index = package array)
    */
    public function packages()
    {
        $result = $this->call('matchpkgs', ['api.version' => 1]);
        $packages = [];

        foreach ((array)$result->data->pkg as $p) {
             $packages[trim($p->name)] = $p;
        }

        return $packages;
    }

    /**
    * Gets all the accounts available to the cpanel user.
    * @link http://docs.cpanel.net/twiki/bin/view/AllDocumentation/AutomationIntegration/ListAccounts
    * @return Array of accounts (key = account username, index = account array)
    */
    public function accounts()
    {
        $result = $this->call('listaccts');
        $accounts = array();

        foreach ((array)$result->acct as $a) {
             $accounts[$a->user] = $a;
        }

        return $accounts;
    }

    /**
    * Gets all the suspended accounts.
    * @link http://docs.cpanel.net/twiki/bin/view/AllDocumentation/AutomationIntegration/ListSuspended
    */
    public function suspended()
    {
        $result = $this->call('listsuspended');

        $accounts = array();

        foreach ((array)$result->accts as $a) {
            $accounts[$a->user] = $a;
        }

        return $accounts;
    }

    /**
    * Gets the current WHM/cPanel version on the server.
    * @link http://docs.cpanel.net/twiki/bin/view/AllDocumentation/AutomationIntegration/DisplaycPanelWHMVersion
    * @return string Current WHM version
    */
    public function version()
    {
        $result = $this->call('version');
        return $result->version;
    }
}
