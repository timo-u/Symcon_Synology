<?php

declare(strict_types=1);
    class SynologySurveillanceStation extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();
            $this->RegisterPropertyInteger('UpdateInterval', 60);
            $this->RegisterTimer('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000, 'SYNOSVS_Update($_IPS[\'TARGET\']);');
       
            $this->ConnectParent('{F308439D-89E4-5486-74B4-44D498C9CD07}');
        }

        public function Destroy()
        {
            //Never delete this line!
            parent::Destroy();
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000);
           
            $this->CreateVariableProfile();
            $this->Maintain();
            parent::ApplyChanges();
        }

        public function Update()
        {
            $version = $this->GetMaxVersion("SYNO.SurveillanceStation.Camera","9");

            $parameter = array( "subpath" => "/webapi/entry.cgi",
                                "getparameter"=> array("api=SYNO.SurveillanceStation.Camera",
                                                        "version=".$version,
                                                        "method=List")
                                   );

            $returnvalue= $this->SendDataToParent(json_encode(['DataID' => '{59B36CB0-EF4D-D794-FED4-89C69D410CDD}','Parameter'=>$parameter]));

            
            if ($returnvalue == false) {
                return false;
            }
            $data = json_decode($returnvalue);

            $this->SendDebug('Update', 'data: '. json_encode($data), 0);

            if (property_exists($data, 'apidata')
                    && 	property_exists($data->apidata, 'data')
                    && 	property_exists($data, 'apiparameter')) {
                $data = $data->apidata->data;

                $pos = 1;

                foreach ($data->cameras as &$camera) {
                    $this->MaintainVariable("CameraState".$this->toIdentName($camera->id), $this->Translate('Camera').": " . $camera->id . " (".$camera->newName.")", 1, "SYNO_Camera_State", $pos++, true);
                    $this->SetValue("CameraState".$this->toIdentName($camera->id), $camera->status);
                }
            }
        }

        private function toIdentName($input)
        {
            return preg_replace('/[^A-Za-z0-9\_]/', '', $input);
        }

        public function ReceiveData($JSONString)
        {
            $this->SendDebug('ReceiveData', utf8_decode($JSONString), 0);
        }
        private function GetMaxVersion(string $api, string $possibleVersions)
        {
            $buffername =  "MaxVersion". preg_replace('/[^A-Za-z0-9\_]/', '', $api);
            $version = intval($this->GetBuffer($buffername));
            if ($version>0) {
                return  $version;
            }


            $parameter = array( "subpath" => "/webapi/query.cgi",
                                "getparameter"=> array( "api=SYNO.API.Info",
                                                        "version=1",
                                                        "method=query",
                                                        "query=".$api)
                                );


            $response= $this->SendDataToParent(json_encode(['DataID' => '{59B36CB0-EF4D-D794-FED4-89C69D410CDD}','Parameter'=>$parameter]));

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
        private function Maintain()
        {
            // $this->MaintainVariable('State', $this->Translate('State'), 0, 'SYNO_Online', 1, true);
          //  $this->MaintainVariable('CpuLoad5Min', $this->Translate('CPU 5 min load'), 2, 'SYNO_Percent', 2, true);
           // $this->MaintainVariable('MemoryPercent', $this->Translate('MemoryPercent'), 2, 'SYNO_Percent', 2, true);
           // $this->MaintainVariable('NetworkTotalRx', $this->Translate('NetworkTotalRx'), 2, 'SYNO_Mbps', 2, true);
           // $this->MaintainVariable('NetworkTotalTx', $this->Translate('NetworkTotalTx'), 2, 'SYNO_Mbps', 2, true);
        }
     
        private function CreateVariableProfile()
        {
            $this->SendDebug('RegisterVariableProfiles()', 'RegisterVariableProfiles()', 0);
            // für Entwicklung altes Profil löschen
            if (IPS_VariableProfileExists('SYNO_Camera_State')) {
                IPS_DeleteVariableProfile("SYNO_Camera_State");
            }

            if (!IPS_VariableProfileExists('SYNO_Camera_State')) {
                IPS_CreateVariableProfile('SYNO_Camera_State', 1);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 1, $this->Translate('Normal'), "", 0x00FF00);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 2, $this->Translate('Deleted'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 3, $this->Translate('Disconnected'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 4, $this->Translate('Unavailable'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 5, $this->Translate('Ready'), "", 0xFFFFFF);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 6, $this->Translate('Inaccessible'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 7, $this->Translate('Disabled'), "", 0xFFFFFF);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 8, $this->Translate('Unrecognized'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 9, $this->Translate('Setting'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 10, $this->Translate('Server disconnected'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 11, $this->Translate('Migrating'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 12, $this->Translate('Others'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 13, $this->Translate('Storage removed'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 14, $this->Translate('Stopping'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 15, $this->Translate('Connect hist failed'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 16, $this->Translate('Unauthorized'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 17, $this->Translate('RTSP error'), "", 0xFF0000);
                IPS_SetVariableProfileAssociation("SYNO_Camera_State", 18, $this->Translate('No video'), "", 0xFF0000);
            }

            /*
            if (!IPS_VariableProfileExists('TTN_dBm_RSSI')) {
                IPS_CreateVariableProfile('TTN_dBm_RSSI', 1);
                IPS_SetVariableProfileText('TTN_dBm_RSSI', '', ' dBm');
                IPS_SetVariableProfileValues('TTN_dBm_RSSI', -150, 0, 1);
            }

            if (!IPS_VariableProfileExists('TTN_dB_SNR')) {
                IPS_CreateVariableProfile('TTN_dB_SNR', 2);
                IPS_SetVariableProfileDigits('TTN_dB_SNR', 1);
                IPS_SetVariableProfileText('TTN_dB_SNR', '', ' dB');
                IPS_SetVariableProfileValues('TTN_dB_SNR', -25, 15, 0.1);
            }
            */
        }
    }