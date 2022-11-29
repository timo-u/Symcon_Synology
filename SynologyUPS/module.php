<?php

declare(strict_types=1);
class SynologyUPS extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyInteger('UpdateInterval', 60);
        $this->RegisterTimer('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000, 'SYNOUPS_Update($_IPS[\'TARGET\']);');

        $this->ConnectParent('{F308439D-89E4-5486-74B4-44D498C9CD07}');
        $this->CreateVariableProfile();
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000);
        $this->Maintain();
        //Never delete this line!
        parent::ApplyChanges();
    }

    public function Update()
    {
        $this->Maintain();
        $this->UpdateUpsStatus();
    }



    private function UpdateUpsStatus()
    {
        $version = $this->GetMaxVersion("SYNO.Core.ExternalDevice.UPS", "1");

        $parameter = array(
            "subpath" => "/webapi/entry.cgi",
            "getparameter" => array(
                "api=SYNO.Core.ExternalDevice.UPS",
                "version=" . $version,
                "method=get"
            )
        );

        $returnvalue = $this->SendDataToParent(json_encode(['DataID' => '{59B36CB0-EF4D-D794-FED4-89C69D410CDD}', 'Parameter' => $parameter]));

        $this->SendDebug('Update', "returnvalue: " . utf8_decode($returnvalue), 0);
        $data = json_decode($returnvalue);


        if ($data == false) {
            $this->SetValue("ConnectionState", false);
            return false;
        }

        $this->SendDebug('Update', 'data: ' . json_encode($data), 0);


        if (
            property_exists($data, 'apidata')
            &&     property_exists($data->apidata, 'data')
            &&     property_exists($data, 'apiparameter')
        ) {
            $data = $data->apidata->data;

            $this->SetValue("Charge", ($data->{'charge'}));
            $this->SetValue("Runtime", $data->{'runtime'} / 60);

            $this->SetValue("UpsState", ($data->{'status'}));
            $this->SetValue("UpsConnectionState", ($data->{'usb_ups_connect'}));

            $this->SetValue("ConnectionState", true);
            return true;
        }

        return false;
    }

    private function GetMaxVersion(string $api, string $possibleVersions)
    {
        $buffername =  "MaxVersion" . preg_replace('/[^A-Za-z0-9\_]/', '', $api);
        $version = intval($this->GetBuffer($buffername));
        if ($version > 0) {
            return  $version;
        }


        $parameter = array(
            "subpath" => "/webapi/query.cgi",
            "getparameter" => array(
                "api=SYNO.API.Info",
                "version=1",
                "method=query",
                "query=" . $api
            )
        );


        $response = $this->SendDataToParent(json_encode(['DataID' => '{59B36CB0-EF4D-D794-FED4-89C69D410CDD}', 'Parameter' => $parameter]));

        if ($response == false) {
            return false;
        }
        $data = json_decode($response);

        if (
            property_exists($data, 'apidata')
            &&     property_exists($data->apidata, 'data')
            &&     property_exists($data, 'apiparameter')
        ) {
            $data = $data->apidata->data;
            $max = $data->{$api}->maxVersion;
            $min = $data->{$api}->minVersion;
            $this->SendDebug('GetMaxVersion()', 'Api' . $api, 0);
            $this->SendDebug('GetMaxVersion()', 'Max' . $max, 0);
            $this->SendDebug('GetMaxVersion()', 'Min' . $min, 0);

            $this->SendDebug('GetMaxVersion()', 'possibleVersions string: ' . $possibleVersions, 0);
            $possibleVersions = explode(',', $possibleVersions);

            arsort($possibleVersions);

            foreach ($possibleVersions as &$version) {
                $this->SendDebug('GetMaxVersion()', 'Version ' . $version, 0);

                $versionint = intval($version);
                if ($versionint <= $max && $versionint >= $min) {
                    $this->SetBuffer($buffername, $versionint);
                    return $versionint;
                }
            }
        }
        $this->SendDebug('GetMaxVersion()', 'Keine entsprechnde Version Gefunden', 0);
        return false;
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug('ReceiveData', utf8_decode($JSONString), 0);
    }
    private function Maintain()
    {
        //UpdateUtilization
        $this->MaintainVariable('ConnectionState', $this->Translate('Connection'), 0, 'SYNO_Online', 1, true);
        $this->MaintainVariable('UpsConnectionState', $this->Translate('UPS Connection'), 0, 'SYNO_Online', 2, true);
        $this->MaintainVariable("UpsState", $this->Translate('UPS State'), 3, "", 3, true);
        $this->MaintainVariable('Charge', $this->Translate('Charge'), 2, 'SYNO_Percent', 4, true);
        $this->MaintainVariable('Runtime', $this->Translate('Runtime'), 1, 'SYNO_Minute', 5, true);
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

        if (!IPS_VariableProfileExists('SYNO_Minute')) {
            IPS_CreateVariableProfile('SYNO_Minute', 1);
            IPS_SetVariableProfileDigits('SYNO_Minute', 1);
            IPS_SetVariableProfileText('SYNO_Minute', '', ' min');
        }
    }
}
