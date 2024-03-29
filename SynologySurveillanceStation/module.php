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

        //Never delete this line!
        parent::ApplyChanges();
    }

    public function Update()
    {
        $version = $this->GetMaxVersion("SYNO.SurveillanceStation.Camera", "9");

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
        return preg_replace('/[^A-Za-z0-9\_]/', '', strval($input));
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

        $this->SendDebug('GetMaxVersion()_Data', $response, 0);

        if ($response== false) {
            return false;
        }
        $data = json_decode($response);

        if (property_exists($data, 'apidata')
                && 	property_exists($data->apidata, 'data')
                && 	property_exists($data, 'apiparameter')) {
            $data = $data->apidata->data;
            if (!property_exists($data, $api)) {
                $this->SendDebug('GetMaxVersion()', 'Informationen zur API '.$api . ' sind nicht in der Antwort enthalten. Der Vorgang wird abgebrochen', 0);
                return false;
            }

            $max = $data->{$api}->maxVersion;
            $min = $data->{$api}->minVersion;
            $this->SendDebug('GetMaxVersion()', 'Api Name: '.$api, 0);
            $this->SendDebug('GetMaxVersion()', 'Api Max Version: '.$max, 0);
            $this->SendDebug('GetMaxVersion()', 'Api Min Version: '.$min, 0);

            $this->SendDebug('GetMaxVersion()', 'possibleVersions string: '.$possibleVersions, 0);
            $possibleVersions =explode(',', $possibleVersions);

            arsort($possibleVersions); // Höchste Version bevorzugen

            foreach ($possibleVersions as &$version) {
                $versionint = intval($version);
                if ($versionint<=$max && $versionint>=$min) {
                    $this->SetBuffer($buffername, $versionint);
                    $this->SendDebug('GetMaxVersion()', 'Version ausgewählt: '.$version, 0);
                    return $versionint;
                }
            }
        }
        $this->SendDebug('GetMaxVersion()', 'Keine entsprechnde Version Gefunden', 0);
        return false;
    }

    public function SetHomeMode(bool $state)
    {
        $version = $this->GetMaxVersion("SYNO.SurveillanceStation.HomeMode", "1");


        $on = $state ? 'true' : 'false';

        $parameter = array( "subpath" => "/webapi/entry.cgi",
                            "getparameter"=> array("api=SYNO.SurveillanceStation.HomeMode",
                                                    "version=".$version,
                                                    "method=Switch",
                                                    "on=".$on)
                               );

        $returnvalue= $this->SendDataToParent(json_encode(['DataID' => '{59B36CB0-EF4D-D794-FED4-89C69D410CDD}','Parameter'=>$parameter]));


        if ($returnvalue == false) {
            return false;
        }
        $data = json_decode($returnvalue);

        $this->SendDebug('Update', 'data: '. json_encode($data), 0);

        if (property_exists($data, 'apidata')
                && 	property_exists($data->apidata, 'success')
                && $data->apidata->success) {
            return true;
        }
        return false;
    }

    public function SetRecording(int $cameraid, bool $record)
    {
        $version = $this->GetMaxVersion("SYNO.SurveillanceStation.ExternalRecording", "1");

        if ($record) {
            $action = "start";
        } else {
            $action = "stop";
        }

        $parameter = array( "subpath" => "/webapi/entry.cgi",
                            "getparameter"=> array("api=SYNO.SurveillanceStation.ExternalRecording",
                                                    "version=".$version,
                                                    "method=Record",
                                                    "cameraId=".$cameraid,
                                                    "action=".$action
                                                    )
                               );

        $returnvalue= $this->SendDataToParent(json_encode(['DataID' => '{59B36CB0-EF4D-D794-FED4-89C69D410CDD}','Parameter'=>$parameter]));


        if ($returnvalue == false) {
            return false;
        }
        $data = json_decode($returnvalue);

        $this->SendDebug('Update', 'data: '. json_encode($data), 0);

        if (property_exists($data, 'apidata')
                && 	property_exists($data->apidata, 'success')
                && $data->apidata->success) {
            return true;
        }
        return false;
    }



    private function CreateVariableProfile()
    {
        $this->SendDebug('RegisterVariableProfiles()', 'RegisterVariableProfiles()', 0);

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
    }
}
