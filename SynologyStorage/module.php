<?php

declare(strict_types=1);
    class SynologyStorage extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();
            $this->RegisterPropertyInteger('UpdateInterval', 60);
            $this->RegisterTimer('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000, 'SYNOSTORAGE_Update($_IPS[\'TARGET\']);');
       
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
            //  $this->SetReceiveDataFilter(".*E94AC765-F5C1-0E77-7870-83F9D7EBFD6F.*");

            $this->CreateVariableProfile();
            $this->Maintain();
            parent::ApplyChanges();
        }

        public function Update()
        {
            $parameter = array( "subpath" => "/webapi/entry.cgi",
                                "getparameter"=> array( "api=SYNO.Storage.CGI.Storage",
                                                        "version=1",
                                                        "method=load_info"),
                                "call"=>"SYNO.Storage.CGI.Storage.load_info",
                                "instance"=>"{E94AC765-F5C1-0E77-7870-83F9D7EBFD6F}"
                                   );

            $returnvalue= $this->SendDataToParent(json_encode(['DataID' => '{59B36CB0-EF4D-D794-FED4-89C69D410CDD}','Parameter'=>$parameter]));

            $data = json_decode($returnvalue);
            if ($returnvalue == false) {
                $this->SetValue("State", false);
            }
            
            
            $this->SendDebug('Update', 'data: '. json_encode($data), 0);


            if (property_exists($data, 'apidata')
                    && 	property_exists($data->apidata, 'data')
                    && 	property_exists($data, 'apiparameter')
                    && 	property_exists($data->apiparameter, 'call')) {
                if ($data->apiparameter->call == "SYNO.Storage.CGI.Storage.load_info") {
                    $data = $data->apidata->data;

					$pos = 1;

                    foreach ($data->storagePools as &$storagePool) {
                        $this->MaintainVariable("StoragePool".$this->toIdentName($storagePool->space_path), $this->Translate('StoragePool').": " . $storagePool->desc, 3, "", $pos++ , true);
                        $this->SetValue("StoragePool".$this->toIdentName($storagePool->space_path), $storagePool->status);
                    }

                    foreach ($data->volumes as &$volume) {
                        $this->MaintainVariable("Volume".$this->toIdentName($volume->dev_path), $this->Translate('Volume').": " . $volume->desc, 3, "", $pos++, true);
                        $this->SetValue("Volume".$this->toIdentName($volume->dev_path), $volume->status);
                        
                        $this->MaintainVariable("VolumePercent".$this->toIdentName($volume->dev_path), $this->Translate('Volume').": " . $volume->desc . " (".$this->Translate('used').")", 2, "SYNO_Percent", $pos++, true);
                        $this->SetValue("VolumePercent".$this->toIdentName($volume->dev_path), ($volume->size->used/$volume->size->total)*100);
                    }
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

            if (!IPS_VariableProfileExists('SYNO_Online')) {
                IPS_CreateVariableProfile('SYNO_Online', 0);
                IPS_SetVariableProfileAssociation('SYNO_Online', 0, $this->Translate('Offline'), '', 0xFF0000);
                IPS_SetVariableProfileAssociation('SYNO_Online', 1, $this->Translate('Online'), '', 0x00FF00);
            }

            if (!IPS_VariableProfileExists('SYNO_Percent')) {
                IPS_CreateVariableProfile('SYNO_Percent', 2);
                IPS_SetVariableProfileDigits('SYNO_Percent', 1);
                IPS_SetVariableProfileText('SYNO_Percent', '', ' %');
                IPS_SetVariableProfileValues('SYNO_Percent', 0, 100, 0.1);
            }

            if (!IPS_VariableProfileExists('SYNO_Mbps')) {
                IPS_CreateVariableProfile('SYNO_Mbps', 2);
                IPS_SetVariableProfileDigits('SYNO_Mbps', 1);
                IPS_SetVariableProfileText('SYNO_Mbps', '', ' MBit/s');
                IPS_SetVariableProfileValues('SYNO_Mbps', 0, 1000, 0.1);
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
