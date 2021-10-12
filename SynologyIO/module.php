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
            $data = json_decode($JSONString, false);
            
            return $this->ApiCall($data->Parameter);
        }

        public function Send(string $Text)
        {
            $this->SendDataToChildren(json_encode(['DataID' => '{43456771-9080-F6F1-AC09-ADCF5DEE6FEA}', 'Buffer' => $Text]));
        }

        public function Login($force = false)
        {
            if (!$this->ReadPropertyBoolean('Active')) {
                return false;
            }

            if($this->GetBuffer('Authentication')=='failed' && !$force)
            {
                return false;
            }

            $username = urlencode($this->ReadPropertyString('Username'));
            $password = urlencode($this->ReadPropertyString('Password'));
            $url = $this->ReadPropertyString('Url');
            
            if ($this->ReadPropertyBoolean('VerifyHost')) {
                $verifyhost = 2;
            } else {
                $verifyhost = 0;
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                    CURLOPT_URL => $url .'/webapi/auth.cgi?api=SYNO.API.Auth&version=6&method=login&account='.$username.'&passwd='.$password.'&format=sid&session=symcon',
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


        private function ApiCall($parameter)
        {
            /*
            $parameter = array( "subpath" => "/webapi/entry.cgi",
                                "getparameter"=> array( "api=SYNO.Core.System.Utilization",
                                                        "version=1",
                                                        "method=get")
                                );

            */
            if ($parameter == null || !property_exists($parameter, 'subpath')|| !property_exists($parameter, 'getparameter'))
            {
                $this->SendDebug('ApiCall()', 'Fehlerhafte Parameter', 0);
                return false;
            }               
            



            $subpath = $parameter->subpath;
            $GetParameter = $parameter->getparameter;

            if (!$this->ReadPropertyBoolean('Active')) {
                return false;
            }

            $sessionId = $this->GetBuffer('SessionId');
            if ($sessionId == "") {
                if ($this->Login()) {
                    $sessionId = $this->GetBuffer('SessionId');
                } else {
                    return false;
                }
            }
            

            if ($this->ReadPropertyBoolean('VerifyHost')) {
                $verifyhost = 2;
            } else {
                $verifyhost = 0;
            }
            
            array_push($GetParameter, "_sid=" .$sessionId);

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


            $data = json_decode($response);

            $data =json_encode(array("apidata" => $data, "apierror" => $err, "apiparameter" => $parameter));
            
            return $data;
        }
    }
