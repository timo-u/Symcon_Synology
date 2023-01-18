<?php

declare(strict_types=1);
class SynologyIO extends IPSModule
{
    public function Create()
    {
        $this->RegisterPropertyString('Url', '');
        $this->RegisterPropertyString('Username', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyString('TwoFactorAuthCode', '');

        $this->RegisterPropertyBoolean('Active', false);

        $this->RegisterPropertyBoolean('VerifyHost', true);
        $this->RegisterPropertyBoolean('VerifyPeer', true);
        //Never delete this line!
        parent::Create();
    }

    public function Destroy()
    {
        $this->Logout();
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
    }

    public function ForwardData($JSONString)
    {
        $this->SendDebug('ForwardData', utf8_decode($JSONString), 0);
        $data = json_decode($JSONString, true);

        return $this->ApiCall($data['Parameter']);
    }

    private function Send(string $Text)
    {
        $this->SendDataToChildren(json_encode(['DataID' => '{43456771-9080-F6F1-AC09-ADCF5DEE6FEA}', 'Buffer' => $Text]));
    }

    public function Create2FACode()
    {
        $code = str_replace(' ', '', $this->ReadPropertyString('TwoFactorAuthCode'));
        if ($code =="") {
            $this->SendDebug('Create2FACode', 'Es wurde kein 2FA Code erzeugt', 0);
            return false;
        }
        $timestamp = floor(microtime(true) / 30);

        $lut = [
        'A' => 0,
        'B' => 1,
        'C' => 2,
        'D' => 3,
        'E' => 4,
        'F' => 5,
        'G' => 6,
        'H' => 7,
        'I' => 8,
        'J' => 9,
        'K' => 10,
        'L' => 11,
        'M' => 12,
        'N' => 13,
        'O' => 14,
        'P' => 15,
        'Q' => 16,
        'R' => 17,
        'S' => 18,
        'T' => 19,
        'U' => 20,
        'V' => 21,
        'W' => 22,
        'X' => 23,
        'Y' => 24,
        'Z' => 25,
        '2' => 26,
        '3' => 27,
        '4' => 28,
        '5' => 29,
        '6' => 30,
        '7' => 31];
        // Decode Base32 Seed
        $b32 = strtoupper($code);
        $n = $j = 0;
        $key = '';
        if (!preg_match('/^[ABCDEFGHIJKLMNOPQRSTUVWXYZ234567]+$/', $b32, $match)) {
            $this->SendDebug('Create2FACode', 'Invalid characters in the base32 string.', 0);
            $this->SetStatus(203);
            return false;
        }
        for ($i = 0, $iMax = strlen($b32); $i < $iMax; $i++) {
            $n <<= 5;              // Move buffer left by 5 to make room
            $n += $lut[$b32[$i]]; // Add value into buffer
            $j += 5;              // Keep track of number of bits in buffer
            if ($j >= 8) {
                $j -= 8;
                $key .= chr(($n & (0xFF << $j)) >> $j);
            }
        }
        // Check Binary Key
        if (strlen($key) < 8) {
            trigger_error('Secret key is too short. Must be at least 16 base 32 characters');
            $this->SendDebug('Create2FACode', 'Invalid characters in the base32 string.', 0);
            $this->SetStatus(203);
            return null;
        }
            // Generate OTA Code based on Seed and Current Timestamp
        $h = hash_hmac('sha1', pack('N*', 0) . pack('N*', $timestamp), $key, true);  // NOTE: Counter must be 64-bit int
        $o = ord($h[19]) & 0xf;
        $ota_code =
        (((ord($h[$o + 0]) & 0x7f) << 24) | ((ord($h[$o + 1]) & 0xff) << 16) | ((ord($h[$o + 2]) & 0xff) << 8) | (ord($h[$o + 3]) & 0xff)) % (10
            ** 6);
        $this->SendDebug('Create2FACode', "OTP-Code: ".$ota_code, 0);
        return  $ota_code;
    }
    public function Login(bool $force = false)
    {
        if (!$this->ReadPropertyBoolean('Active')) {
            return false;
        }

        if ($this->GetBuffer('Authentication')=='failed' && !$force) {
            $this->SendDebug('Login()', 'Authentication has failed - Login is blocked!', 0);
            return false;
        }

        $this->SendDebug('Login()', 'Try to log in', 0);

        $username = urlencode($this->ReadPropertyString('Username'));
        $password = urlencode($this->ReadPropertyString('Password'));
        $url = $this->ReadPropertyString('Url');

        $version = $this->GetMaxVersion("SYNO.API.Auth", "3,4,5,6");
        $otp="";
        if ($this->ReadPropertyString('TwoFactorAuthCode')!="") {
            $code = $this->Create2FACode();
            if ($code) {
                $otp="&otp_code=".$code ;
            }
        }


        if ($this->ReadPropertyBoolean('VerifyHost')) {
            $verifyhost = 2;
        } else {
            $verifyhost = 0;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
                CURLOPT_URL => $url .'/webapi/auth.cgi?api=SYNO.API.Auth&version='.$version.'&method=login&account='.$username.'&passwd='.$password.'&format=sid&session=symcon'.$otp,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_SSL_VERIFYHOST => $verifyhost,
                CURLOPT_SSL_VERIFYPEER => $this->ReadPropertyBoolean('VerifyPeer'),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        $this->SendDebug('Login()', 'Response:' . $response, 0);

        curl_close($curl);
        if ($err) {
            $this->SetStatus(201);
            $this->SendDebug('Login()', 'Error:' . $err, 0);
            $this->SetBuffer('SessionId', '');
            return false;
        }

        $data = json_decode($response, false);


        $success = ($data->success);

        if ($success) {
            $this->SetStatus(102);
            $this->SetBuffer('SessionId', $data->data->sid);
            $this->SetBuffer('Authentication', '');
        } else {
            $this->SendDebug('Login()', 'Authentication failed', 0);
            $this->SendDebug('Login()', 'Code:' . $data->error->code, 0);
            if ($data->error->code==400) {
                $this->SendDebug('Login()', 'No such account or incorrect password.', 0);
            }
            if ($data->error->code==401) {
                $this->SendDebug('Login()', 'Disabled account', 0);
            }
            if ($data->error->code==402) {
                $this->SendDebug('Login()', 'Denied permission', 0);
            }
            if ($data->error->code==403) {
                $this->SendDebug('Login()', '2-factor authentication code required.', 0);
            }
            if ($data->error->code==404) {
                $this->SendDebug('Login()', 'Failed to authenticate 2-factor authentication code.', 0);
            }
            if ($data->error->code==406) {
                $this->SendDebug('Login()', 'Enforce to authenticate with 2-factor authentication code', 0);
            }
            if ($data->error->code==407) {
                $this->SendDebug('Login()', 'Blocked IP source', 0);
            }
            if ($data->error->code==408) {
                $this->SendDebug('Login()', 'Expired password cannot change', 0);
            }
            if ($data->error->code==409) {
                $this->SendDebug('Login()', 'Expired password', 0);
            }
            if ($data->error->code==410) {
                $this->SendDebug('Login()', 'Password must be changed', 0);
            }
            $this->SetBuffer('Authentication', 'failed');
            $this->SetStatus(202); // Authentication failed
        }

        return $success;
    }



    public function GetMaxVersion(string $api, string $possibleVersions)
    {
        $buffername =  "MaxVersion". preg_replace('/[^A-Za-z0-9\_]/', '', $api);
        $version = $this->GetBuffer($buffername);
        if ($version!="") {
            return  intval($version);
        }

        $parameter = array( "subpath" => "/webapi/query.cgi",
                            "getparameter"=> array( "api=SYNO.API.Info",
                                                    "version=1",
                                                    "method=query",
                                                    "query=".$api),
                            "auth"=>false
                            );


        $response = $this->ApiCall($parameter);

        if ($response== false) {
            return false;
        }
        $data = json_decode($response);

        if (property_exists($data, 'apidata')
                && 	property_exists($data->apidata, 'data')
                && 	property_exists($data, 'apiparameter')) {
            $data = $data->apidata->data;
            $max = $data->{$api}->maxVersion;
            $min = $data->{$api}->minVersion;
            $this->SendDebug('GetMaxVersion()', 'Api'.$api, 0);
            $this->SendDebug('GetMaxVersion()', 'Max'.$max, 0);
            $this->SendDebug('GetMaxVersion()', 'Min'.$min, 0);

            $this->SendDebug('GetMaxVersion()', 'possibleVersions string: '.$possibleVersions, 0);
            $possibleVersions =explode(',', $possibleVersions);

            arsort($possibleVersions);

            foreach ($possibleVersions as &$version) {
                $this->SendDebug('GetMaxVersion()', 'Version '.$version, 0);

                $versionint = intval($version);
                if ($versionint<=$max && $versionint>=$min) {
                    $this->SetBuffer($buffername, $versionint);
                    return $versionint;
                }
            }
        }
        $this->SendDebug('GetMaxVersion()', 'Keine entsprechnde Version Gefunden', 0);
        return false;
    }

    public function Logout()
    {
        if (!$this->ReadPropertyBoolean('Active')) {
            return false;
        }

        $this->SetBuffer('Authentication', '');

        $sessionId = $this->GetBuffer('SessionId');
        if ($sessionId == "") {
            return true;
        }
        $this->SetBuffer('SessionId', '');

        $url = $this->ReadPropertyString('Url');

        if ($this->ReadPropertyBoolean('VerifyHost')) {
            $verifyhost = 2;
        } else {
            $verifyhost = 0;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
                CURLOPT_URL => $url .'/webapi/auth.cgi?api=SYNO.API.Auth&version=1&method=logout&_sid='.$sessionId ,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_SSL_VERIFYHOST => $verifyhost,
                CURLOPT_SSL_VERIFYPEER => $this->ReadPropertyBoolean('VerifyPeer'),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        $this->SendDebug('Logout()', 'Response:' . $response, 0);

        curl_close($curl);

        if ($err) {
            $this->SetStatus(201);
            $this->SendDebug('Logout()', 'Error:' . $err, 0);
            return false;
        }

        $data = json_decode($response, false);

        if ($data->success) {
            $this->SetStatus(102);
            return true;
        }

        return $false;
    }

    private function ApiCall($parameter)
    {
        /*
        $parameter = array( "subpath" => "/webapi/entry.cgi",
                            "getparameter"=> array( "api=SYNO.Core.System.Utilization",
                                                    "version=1",
                                                    "method=get")
                            );

        */

        if ($parameter == null || !array_key_exists('subpath', $parameter)|| !array_key_exists('getparameter', $parameter)) {
            $this->SendDebug('ApiCall()', 'Fehlerhafte Parameter', 0);
            return false;
        }


        $subpath = $parameter['subpath'];
        $GetParameter = $parameter['getparameter'];



        if (!$this->ReadPropertyBoolean('Active')) {
            return false;
        }


        if (!(array_key_exists('auth', $parameter) && $parameter['auth'] == false)) {
            $sessionId = $this->GetBuffer('SessionId');
            $this->SendDebug('ApiCall', 'Step4'.$sessionId, 0);
            if ($sessionId == "") {
                $this->SendDebug('ApiCall', '->Login', 0);
                if ($this->Login()) {
                    $this->SendDebug('ApiCall', 'Login Succsessfull', 0);
                    $sessionId = $this->GetBuffer('SessionId');
                } else {
                    return false;
                }
            }
            array_push($GetParameter, "_sid=" .$sessionId);
        }


        if ($this->ReadPropertyBoolean('VerifyHost')) {
            $verifyhost = 2;
        } else {
            $verifyhost = 0;
        }



        $url = $this->ReadPropertyString('Url').$subpath. "?".  implode("&", $GetParameter);

        $this->SendDebug('ApiCall()', 'URL:' . $url, 0);


        $curl = curl_init();

        curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_SSL_VERIFYHOST => $verifyhost,
                CURLOPT_SSL_VERIFYPEER => $this->ReadPropertyBoolean('VerifyPeer'),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        $this->SendDebug('ApiCall()', 'Response:' . $response, 0);
        $this->SendDebug('ApiCall()', 'Error:' . $err, 0);

        curl_close($curl);

        if ($err) {
            return false;
        }

        if ($response==null || $response =="") {
            return false;
        }

        $data = json_decode($response);

        if (property_exists($data, 'error') && property_exists($data->error, 'code')) {
            if ($data->error->code == 119) { // Invalid session
                $this->SetBuffer('SessionId', ""); // Session-ID entfernen, dadurch wird beim nächsten versuch neu angemeldet
                $this->SendDebug('ApiCall()', 'Invalid session', 0);
            }
        }

        $data =json_encode(array("apidata" => $data, "apierror" => $err, "apiparameter" => $parameter, "url" => $url ));

        $this->SendDebug('ApiCall()', 'Response:' . $data, 0);

        return $data;
    }
}
