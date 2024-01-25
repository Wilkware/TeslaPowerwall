<?php

declare(strict_types=1);

// Allgemeine Funktionen
require_once __DIR__ . '/../libs/_traits.php';

/**
 * CLASS UserMap
 */
class TeslaPowerwallLocal extends IPSModule
{
    use DebugHelper;
    use FormatHelper;
    use ProfileHelper;
    use TeslaHelper;
    use VariableHelper;

    // Echo maps
    private const TELSA_MAP_STATUS = [
        ['din', 'Gateway', 3], // '1152100-13-J--CN322130G3J04Q',
        ['start_time', 'Start time', 4], // '2023-11-09 12:02:20 +0800',
        ['up_time_seconds', 'Uptime', 3], // '1679h44m47.488908756s',
        ['is_new', 'Is new', 0], // false,
        ['version', 'Version', 3], // '23.28.2 27626f98',
        // ['git_hash', '', 5], // '27626f98a66cad5c665bbe1d4d788cdb3e94fd33',
        ['commission_count', 'Commission count', 1], // 0,
        ['device_type', 'Device type', 3], //'teg',
        ['teg_type', 'TEQ type', 3], //'unknown',
        ['sync_type', 'Sync type', 3], //'v2.1',
        ['cellular_disabled', 'Cellular disabled', 0], //false,
        ['can_reboot', 'Can reboot', 0], //true
    ];

    // Form Elements Positions
    private const ELEM_IMAGE = 0;
    private const ELEM_LABEL = 1;
    private const ELEM_ACCOUNT = 2;
    private const ELEM_DEVICE = 3;
    private const ELEM_DATA = 4;
    private const ELEM_ADVANCED = 5;

    // API Endpoints (see )
    private static $API_ENDPOINTS = [
        ['Query' => true,  'Endpoint' => '/meters/aggregates', 'Method'=> 'GET',  'Description'=> 'Instantaneous readings from the CT clamps'],
        ['Query' => false, 'Endpoint' => '/site_info',         'Method'=> 'GET',  'Description'=> 'High-level information about the location and the network to which the Powerwall is connected'],
    ];

    /**
     * Overrides the internal IPSModule::Create($id) function
     */
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        // The auth token is provided in the response to a successful login.
        $this->RegisterAttributeString('AuthToken', '');
        // The cookie file name.
        $this->RegisterAttributeString('CookieFile', tempnam('/tmp', 'tpwl.'));
        // Account
        $this->RegisterPropertyString('Mail', '');
        $this->RegisterPropertyString('Password', '');
        // Device
        $this->RegisterPropertyString('Gateway', '127.0.0.1');
        // Data
        $this->RegisterPropertyString('Endpoints', '[]');
        $this->RegisterPropertyInteger('UpdateInterval', 5);

        // Advanced
        $this->RegisterPropertyBoolean('UppercaseMode', true);
        $this->RegisterPropertyInteger('CookieLifetime', 60);
        // Register cookie update timer
        $this->RegisterTimer('UpdateCookieTimer', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "refresh", "cookie");');
        // Register data update timer
        $this->RegisterTimer('UpdateDataTimer', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "update", "data");');
    }

    /**
     * Overrides the internal IPSModule::Destroy($id) function
     */
    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    /**
     * Configuration Form.
     *
     * @return JSON configuration string.
     */
    public function GetConfigurationForm()
    {
        // Get Form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        // Get Token
        $token = $this->ReadAttributeString('AuthToken');
        // Debug output
        $this->SendDebug(__FUNCTION__, 'AuthToken: ' . $token);
        // LoggedIn?
        if (!empty($token)) {
            $this->SetStatus(102);
        } else {
            $this->SetStatus(104);
        }
        //Only add default element if we do not have anything in persistence
        $endpoints = json_decode($this->ReadPropertyString('Endpoints'), true);
        if (empty($endpoints)) {
            $form['elements'][self::ELEM_DATA]['items'][0]['values'] = $this->GetEndpointValues();
        }
        //$this->SendDebug(__FUNCTION__, $form);
        return json_encode($form);
    }

    /**
     * Overrides the internal IPSModule::ApplyChanges($id) function
     */
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        // Safty Check something changed
        $heartbeat = 0;
        $updatedata = 0;
        $state = $this->Login('refresh');
        if ($state) {
            // Heartbreak
            $heartbeat = $this->ReadPropertyInteger('CookieLifetime');
            // Update Data
            $updatedata = $this->ReadPropertyInteger('UpdateInterval');
        }
        // Timer ?
        $this->SetTimerInterval('UpdateDataTimer', 60 * 1000 * $updatedata);
        // only if updata data longer than lifetime
        if (($updatedata > 0) && ($heartbeat > $updatedata)) {
            $heartbeat = 0;
        }
        $this->SetTimerInterval('UpdateCookieTimer', 60 * 1000 * $heartbeat);
    }

    /**
     * RequestAction.
     *
     *  @param string $ident Ident.
     *  @param string $value Value.
     */
    public function RequestAction($ident, $value)
    {
        // Debug output
        $this->SendDebug(__FUNCTION__, $ident . ' => ' . $value);
        switch ($ident) {
            case 'login':
                $this->Login($value);
                break;
            case 'logout':
                $this->Logout($value);
                break;
            case 'refresh':
                $this->Refresh($value);
                break;
            case 'status':
                $this->Status($value);
                break;
            case 'update':
                $this->Update($value);
                break;
            default:
                // ERROR!!!
                break;
        }
        return true;
    }

    /**
     * GetEndpointValues for form list
     *
     * @return array List values
     */
    protected function GetEndpointValues()
    {
        $values = [];
        foreach (self::$API_ENDPOINTS as $value) {
            $value['Description'] = $this->Translate($value['Description']);
            $values[] = $value;
        }
        return $values;
    }

    /**
     * Login to the gateway.
     *
     * @param string $value context info
     * @return bool true if successful, otherwise false.
     */
    private function Login($value)
    {
        // Display feedback massage
        $info = ($value == 'info');
        $ret = false;
        // Crfedentials
        $mail = $this->ReadPropertyString('Mail');
        $password = $this->ReadPropertyString('Password');
        // Safty check
        if (empty($mail)) {
            $this->SetStatus(201);
            if ($info) $this->EchoMessage('Login not possible!');
            return $ret;
        }
        if (empty($password)) {
            $this->SetStatus(202);
            if ($info) $this->EchoMessage('Login not possible!');
            return $ret;
        }
        // API call
        $body = [];
        $body['username'] = 'customer';
        $body['email'] = $mail;
        $body['password'] = $password;
        $request = json_encode($body);
        $this->SendDebug(__FUNCTION__, 'Request: ' . $request);
        $response = $this->Request('/login/Basic', true, $request);
        $text = 'Login was not successfull!';
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->SendDebug(__FUNCTION__, $params);
            if (isset($params['token'])) {
                $this->SetStatus(102);
                $ret = true;
                $text = 'Login was successfull!';
                // AuthToken
                $this->SendDebug(__FUNCTION__, 'Token: ' . $params['token']);
                $this->WriteAttributeString('AuthToken', $params['token']);
            } else {
                $this->SetStatus(104);
                if (isset($params['error'])) {
                    $text .= $text . ' - ' . $params['error'];
                }
            }
        }
        // Return
        if ($info) $this->EchoMessage($text);
        return $ret;
    }

    /**
     * Logout from the gateway server.
     *
     * @param string $value context info
     * @return True if successful, otherwise false.
     */
    private function Logout($value)
    {
        // Display feedback massage
        $info = ($value == 'info');
        // Exist Auth Token?
        $token = $this->ReadAttributeString('AuthToken');
        // Safty check
        if (empty($token)) {
            $this->SendDebug(__FUNCTION__, 'Token: ' . $token);
            if ($info) echo $this->EchoMessage('Logout not possible!');
            return false;
        }
        // Delete cookie
        $cookie = $this->ReadAttributeString('CookieFile');
        $result = unlink($cookie);
        // Result?
        if ($result !== false) {
            $this->WriteAttributeString('AuthToken', '');
            $this->SetStatus(104);
            if ($info) echo $this->EchoMessage('Logout was successfull!');
        } else {
            if ($info) echo $this->EchoMessage('Logout was not successfull!');
            return false;
        }
        return true;
    }

    /**
     * Device Status
     *
     */
    private function Status($value)
    {
        // Display feedback massage
        $info = ($value == 'info');
        $response = $this->Request('/status', false, null);
        $text = 'Error when calling the function!';
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->SendDebug(__FUNCTION__, $params);
            $text = $this->PrettyPrint(self::TELSA_MAP_STATUS, $params);
        }
        $this->SendDebug(__FUNCTION__, $response);
        // Return
        if ($info) $this->EchoMessage($text);
    }

    /**
     * Refresh cookie
     *
     */
    private function Refresh($value)
    {
        $response = $this->Request('/customer', false, null);
        $text = 'Error when calling the function!';
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->SendDebug(__FUNCTION__, $params);
        }
        $this->SendDebug(__FUNCTION__, $response);
    }

    /**
     * Update data call
     *
     * @param string $value Internal value
     */
    private function Update($value)
    {
        // Check instance state
        if ($this->GetStatus() != 102) {
            $this->SendDebug(__FUNCTION__, 'Status: Instance is not active.');
            return;
        }
        $endpoints = json_decode($this->ReadPropertyString('Endpoints'), true);
        foreach ($endpoints as $call) {
            if ($call['Query']) {
                $response = $this->Request($call['Endpoint'], false, null);
                $this->SendDebug(__FUNCTION__, $response);
                // Result?
                if ($response !== false) {
                    $params = json_decode($response, true);
                    // Flag for upper case names?
                    $upper = $this->ReadPropertyBoolean('UppercaseMode');
                    // Update variables
                    $this->CreateVariablesFromJson($params, $upper);
                }
            }
        }
    }

    /**
     * Create and update variables from json structure/response.
     *
     * @param JSON $json Json response answer
     * @param bool $case Indicator to upper case variable names
     * @param string $prefix Prefix fpr each variable name
     */
    private function CreateVariablesFromJson($json, $case, $prefix = '')
    {
        // Go through the values
        foreach ($json as $key => $value) {
            $name = $prefix . $key;
            // convert name
            if ($case) {
                $name = strtoupper($name);
            }
            // Check whether it is another array (JSON object)
            if (is_array($value)) {
                // Recursively create the variables for the inner array
                $this->CreateVariablesFromJson($value, $case, $name . '_');
                continue;
            }
            // Create ident from name
            $ident = $this->GetVariableIdent($name);
            // Check, if variable exist
            $vid = @$this->GetIDForIdent($ident);
            if ($vid === false) {
                $type = $this->GetVariableType($value);
                switch ($type) {
                    case 0: // boolean
                        $vid = $this->RegisterVariableBoolean($ident, $name);
                        break;
                    case 1: // integer
                        $vid = $this->RegisterVariableInteger($ident, $name);
                        break;
                    case 2: // float
                        $vid = $this->RegisterVariableFloat($ident, $name);
                        break;
                    case 3: // string
                        $vid = $this->RegisterVariableString($ident, $name);
                        break;
                    default:
                        $this->SendDebug(__FUNCTION__, 'Unknown type for ' . $name);
                        break;
                }
            }
            // Update the value of the variable
            $this->SetValue($ident, $value);
        }
    }

    /*
     * Request - Sends the request to the device
     *
     * If $request not null, we will send a POST request, else a GET request.
     * Over the $method parameter can we force a POST or GET request!
     *
     * @param string $endpoint api endpoint to call
     * @param string $request Request data
     * @param string $mehtod 'GET' od 'POST'
     * @return mixed response data or false.
     */
    private function Request(string $endpoint, bool $login, ?string $request, string $method = 'GET')
    {
        //$this->SendDebug(__FUNCTION__, $endpoint, 0);
        // header
        $headers = [
            'Content-Type: application/json',
        ];
        // cookie
        $cookie = $this->ReadAttributeString('CookieFile');
        // URL
        $gateway = $this->ReadPropertyString('Gateway');
        // API Base URL
        $url = 'https://' . $gateway . '/api' . $endpoint;
        $this->SendDebug(__FUNCTION__, $url);
        // prepeare curl call
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($login) {
            curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);
        } else {
            curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);
        }
        $this->SendDebug(__FUNCTION__, $cookie);
        if ($request != null) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        } else {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        if (!$response = curl_exec($curl)) {
            $error = sprintf('Request failed for URL: %s - Error: %s', $url, curl_error($curl));
            $this->SendDebug(__FUNCTION__, $error, 0);
        }
        curl_close($curl);
        $this->SendDebug(__FUNCTION__, $response, 0);
        return $response;
    }

    /**
     * Show message via popup
     *
     * @param string $caption echo message
     */
    private function EchoMessage(string $caption)
    {
        $this->UpdateFormField('EchoMessage', 'caption', $this->Translate($caption));
        $this->UpdateFormField('EchoPopup', 'visible', true);
    }
}