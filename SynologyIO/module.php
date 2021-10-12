<?php

declare(strict_types=1);
    class SynologyIO extends IPSModule
    {
        public function Create()
        {
            $this->RegisterPropertyString('Url', '');
            $this->RegisterPropertyString('Username', '');
            $this->RegisterPropertyString('Password', '');
            
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

        public function Login(bool $force = false)
        {
            if (!$this->ReadPropertyBoolean('Active')) {
                return false;
            }

            if ($this->GetBuffer('Authentication')=='failed' && !$force) {
                return false;
            }

            $username = urlencode($this->ReadPropertyString('Username'));
            $password = urlencode($this->ReadPropertyString('Password'));
            $url = $this->ReadPropertyString('Url');

            $version = $this->GetMaxVersion("SYNO.API.Auth", "3,4,5,6");
            
            if ($this->ReadPropertyBoolean('VerifyHost')) {
                $verifyhost = 2;
            } else {
                $verifyhost = 0;
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                    CURLOPT_URL => $url .'/webapi/auth.cgi?api=SYNO.API.Auth&version='.$version.'&method=login&account='.$username.'&passwd='.$password.'&format=sid&session=symcon',
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
            } else {
                if ($data->error->code==400) {
                    $this->SendDebug('Login()', 'Authentication failed', 0);
                    $this->SetBuffer('Authentication', 'failed');
                }
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
            $this->SendDebug('ApiCall', '1', 0);
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
                if ($sessionId == "") {
                    if ($this->Login()) {
                        $sessionId = $this->GetBuffer('SessionId');
                    } else {
                        return false;
                    }
                }
                array_push($GetParameter, "_sid=" .$sessionId);
            }
            
            
            $this->SendDebug('ApiCall', '5', 0);
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

            $data =json_encode(array("apidata" => $data, "apierror" => $err, "apiparameter" => $parameter, "url" => $url ));
           
            $this->SendDebug('ApiCall()', 'Response:' . $data, 0);

            return $data;
        }
    }
