<?php

declare(strict_types=1);
    class SynologySystem extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();
            $this->RegisterPropertyInteger('UpdateInterval', 60);
            $this->RegisterTimer('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000, 'SYNOSYS_Update($_IPS[\'TARGET\']);');
       
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
            $this->SetReceiveDataFilter(".*D04449C0-D59B-5C08-90C1-CB00EA0F92D1.*");

            $this->CreateVariableProfile();
            $this->Maintain();
            parent::ApplyChanges();
        }

        public function Update()
        {
            $this->UpdateUtilization();
            $this->UpdateSystemStatus();
            $this->UpdateSystemInformation();
        }


        private function UpdateUtilization()
        {
            $parameter = array( "subpath" => "/webapi/entry.cgi",
            "getparameter"=> array( "api=SYNO.Core.System.Utilization",
                                    "version=1",
                                    "method=get")
            );

            $returnvalue= $this->SendDataToParent(json_encode(['DataID' => '{59B36CB0-EF4D-D794-FED4-89C69D410CDD}','Parameter'=>$parameter]));

            $this->SendDebug('Update', "returnvalue: " . utf8_decode($returnvalue), 0);
            $data = json_decode($returnvalue);


            if ($data == false) {
                return false;
            }

            $this->SendDebug('Update', 'data: '. json_encode($data), 0);


            if (property_exists($data, 'apidata')
                    && 	property_exists($data->apidata, 'data')
                    && 	property_exists($data, 'apiparameter')) {
                $data = $data->apidata->data;
                $this->SetValue("CpuLoad5Min", ($data->cpu->{'5min_load'}) / 100);
                $this->SetValue("MemoryPercent", $data->memory->{'real_usage'});
                $this->SetValue("NetworkTotalRx", ($data->network[0]->rx) / 1024/1024);
                $this->SetValue("NetworkTotalTx", ($data->network[0]->tx) / 1024/1024);
                $this->SetValue("State", true);
                return true;
            }

            return false;
        }
        private function UpdateSystemStatus()
        {
            $parameter = array( "subpath" => "/webapi/entry.cgi",
            "getparameter"=> array( "api=SYNO.Core.System.Status",
                                    "version=1",
                                    "method=get")
            );

            $returnvalue= $this->SendDataToParent(json_encode(['DataID' => '{59B36CB0-EF4D-D794-FED4-89C69D410CDD}','Parameter'=>$parameter]));

            $this->SendDebug('Update', "returnvalue: " . utf8_decode($returnvalue), 0);
            $data = json_decode($returnvalue);


            if ($data == false) {
                $this->SetValue("State", false);
                return false;
            }

            $this->SendDebug('Update', 'data: '. json_encode($data), 0);


            if (property_exists($data, 'apidata')
                    && 	property_exists($data->apidata, 'data')
                    && 	property_exists($data, 'apiparameter')) {
                $data = $data->apidata->data;
               
                $this->SetValue("SystemCrashed", ($data->{'is_system_crashed'}) / 100);
                $this->SetValue("UpgradeReady", $data->{'upgrade_ready'});
                return true;
            }

            return false;
        }
        private function UpdateSystemInformation()
        {
            $parameter = array( "subpath" => "/webapi/entry.cgi",
            "getparameter"=> array( "api=SYNO.Core.System",
                                    "version=1",
                                    "method=info")
            );

            $returnvalue= $this->SendDataToParent(json_encode(['DataID' => '{59B36CB0-EF4D-D794-FED4-89C69D410CDD}','Parameter'=>$parameter]));

            $this->SendDebug('Update', "returnvalue: " . utf8_decode($returnvalue), 0);
            $data = json_decode($returnvalue);


            if ($data == false) {
                $this->SetValue("State", false);
                return false;
            }

            $this->SendDebug('Update', 'data: '. json_encode($data), 0);


            if (property_exists($data, 'apidata')
                    && 	property_exists($data->apidata, 'data')
                    && 	property_exists($data, 'apiparameter')) {
                $data = $data->apidata->data;
               
                $this->SetValue("Uptime", (intval($data->up_time)));
                $this->SetValue("SystemTemperature", $data->sys_temp);
                $this->SetValue("FirmwareVersion", $data->firmware_ver);
                return true;
            }

            return false;
        }


        public function ReceiveData($JSONString)
        {
            $this->SendDebug('ReceiveData', utf8_decode($JSONString), 0);
        }
        private function Maintain()
        {
            //UpdateUtilization
            $this->MaintainVariable('State', $this->Translate('State'), 0, 'SYNO_Online', 1, true);
            $this->MaintainVariable('CpuLoad5Min', $this->Translate('CPU 5 min load'), 2, 'SYNO_Percent', 2, true);
            $this->MaintainVariable('MemoryPercent', $this->Translate('Memory Percent'), 2, 'SYNO_Percent', 3, true);
            $this->MaintainVariable('NetworkTotalRx', $this->Translate('Network Total Rx'), 2, 'SYNO_Mbps', 4, true);
            $this->MaintainVariable('NetworkTotalTx', $this->Translate('Network Total Tx'), 2, 'SYNO_Mbps', 5, true);
            //UpdateSystemStatus
            $this->MaintainVariable('SystemCrashed', $this->Translate('System Crashed'), 0, 'SYNO_Fault', 10, true);
            $this->MaintainVariable('UpgradeReady', $this->Translate('Upgrade Ready'), 0, 'SYNO_Fault', 41, true);
            //UpdateSystemInformation
            $this->MaintainVariable("FirmwareVersion", $this->Translate('Firmware Version'), 3, "", 40, true);
            $this->MaintainVariable('SystemTemperature', $this->Translate('System Temperature'), 1, 'SYNO_Temperature', 2, true);
            $this->MaintainVariable('Uptime', $this->Translate('Uptime'), 1, 'SYNO_Hour', 2, true);
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

            if (!IPS_VariableProfileExists('SYNO_Fault')) {
                IPS_CreateVariableProfile('SYNO_Fault', 0);
                IPS_SetVariableProfileAssociation('SYNO_Fault', 0, $this->Translate('OK'), '', 0x00FF00);
                IPS_SetVariableProfileAssociation('SYNO_Fault', 1, $this->Translate('Fault'), '', 0xFF0000);
            }

            if (!IPS_VariableProfileExists('SYNO_Temperature')) {
                IPS_CreateVariableProfile('SYNO_Temperature', 1);
                IPS_SetVariableProfileDigits('SYNO_Temperature', 1);
                IPS_SetVariableProfileText('SYNO_Temperature', '', ' °C');
                IPS_SetVariableProfileValues('SYNO_Temperature', 30, 100, 1);
            }

            if (!IPS_VariableProfileExists('SYNO_Hour')) {
                IPS_CreateVariableProfile('SYNO_Hour', 1);
                IPS_SetVariableProfileDigits('SYNO_Hour', 1);
                IPS_SetVariableProfileText('SYNO_Hour', '', ' h');
            }
        }
    }
