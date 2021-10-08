<?php

declare(strict_types=1);
	class SynologyIO extends IPSModule
	{
		public function Create()
		{
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
			$data = json_decode($JSONString);
			IPS_LogMessage('IO FRWD', utf8_decode($data->Buffer));
		}

		public function Send(string $Text)
		{
			$this->SendDataToChildren(json_encode(['DataID' => '{43456771-9080-F6F1-AC09-ADCF5DEE6FEA}', 'Buffer' => $Text]));
		}
	}