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
        $version = $this->GetMaxVersion("SYNO.Storage.CGI.Storage", "1");

        $parameter = array( "subpath" => "/webapi/entry.cgi",
                            "getparameter"=> array( "api=SYNO.Storage.CGI.Storage",
                                                    "version=".$version ,
                                                    "method=load_info")
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

            if (property_exists($data, 'storagePools')) {
                foreach ($data->storagePools as &$storagePool) {
                    if (property_exists($storagePool, 'space_path')) {
                        $ident ="StoragePool".$this->toIdentName($storagePool->space_path);
                    } else {
                        $ident ="StoragePool".$this->toIdentName($storagePool->id);
                    }
                    if (property_exists($storagePool, 'desc')) {
                        $description =$storagePool->desc;
                    } else {
                        $description =$storagePool->id;
                    }
                    $this->MaintainVariable($ident, $this->Translate('StoragePool').": " . $description, 3, "", $pos++, true);
                    $this->SetValue($ident, $storagePool->status);
                }
            }

            if (property_exists($data, 'volumes')) {
                foreach ($data->volumes as &$volume) {
                    if (property_exists($volume, 'dev_path')) {
                        $ident =$this->toIdentName($volume->dev_path);
                    } elseif (property_exists($volume, 'vol_path')) {
                        $ident =$this->toIdentName($volume->vol_path);
                    } else {
                        $ident =$this->toIdentName($volume->id);
                    }
                    if (property_exists($volume, 'desc')) {
                        $description =$volume->desc;
                    } elseif (property_exists($volume, 'vol_desc')) {
                        $description =$volume->vol_desc;
                    } else {
                        $description =$this->toIdentName($volume->id);
                    }

                    $this->MaintainVariable("Volume".$ident, $this->Translate('Volume').": " . $description, 3, "", $pos++, true);
                    $this->SetValue("Volume".$ident, $volume->status);

                    $this->MaintainVariable("VolumePercent".$ident, $this->Translate('Volume').": " . $description . " (".$this->Translate('used').")", 2, "SYNO_Percent", $pos++, true);
                    $this->SetValue("VolumePercent".$ident, ($volume->size->used/$volume->size->total)*100);
                }
            }
        }
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

    private function toIdentName($input)
    {
        return preg_replace('/[^A-Za-z0-9\_]/', '', $input);
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug('ReceiveData', utf8_decode($JSONString), 0);
    }


    private function CreateVariableProfile()
    {
        $this->SendDebug('RegisterVariableProfiles()', 'RegisterVariableProfiles()', 0);

        if (!IPS_VariableProfileExists('SYNO_Percent')) {
            IPS_CreateVariableProfile('SYNO_Percent', 2);
            IPS_SetVariableProfileDigits('SYNO_Percent', 1);
            IPS_SetVariableProfileText('SYNO_Percent', '', ' %');
            IPS_SetVariableProfileValues('SYNO_Percent', 0, 100, 0.1);
        }
    }
}
