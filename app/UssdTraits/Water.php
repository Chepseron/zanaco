<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait Water{
	public function WaterAccounts(){
        $all_accounts = Cache::get($this->msisdn.'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if(count($Accounts) > 0){
            $message = "Select Account \n";

            $num = 1;
            foreach($Accounts as $Account){
                if($Account != ""){
					$message .= $num.". ".$Account."\n";
					$num++;
				}
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'WaterAccounts', 'action' => 'con', 'response' => $message, 'map' => array( (object) array('menu' => 'waterConfirm')), 'type' => 'form');
        }else{
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array( (object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;

    }
	public function waterValidate(){
		
		$CustomerID = Cache::get($this->msisdn.'CUSTOMERID');
		$utilityAccount = Cache::get($this->msisdn.'NATWATERUG');
		$Areas = ['Kampala','Jinja','Entebe','Iganga','Lugazi','Mukono','Kajjansi','Kawuku','Others'];
		$selectedArea = Cache::get($this->msisdn.'formWaterArea');
		$Area = $Areas[$selectedArea - 1];
	
		$DataToSend = 'FORMID:M-:MERCHANTID:007001003:INFOFIELD1:'.$Area.':ACCOUNTID:'. $utilityAccount .':CUSTOMERID:'. $CustomerID .':INFOFIELD9:'.$this->msisdn.':MOBILENUMBER:'.$this->msisdn.':BANKNAME:'.$this->BankName.':SHORTCODE:'.$this->shortcode.':BANKID:'. $this->DefaultBankID .':COUNTRY:'. $this->Country .':ACTION:GETNAME:UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:';
		$this->logErr($this->msisdn, "WATER VALIDATE REQUEST : ". $DataToSend);
		$ElmaResponse = $this->ElmaU($DataToSend);
		$responseData = explode(':', strip_tags($ElmaResponse));
		$this->logErr($this->msisdn, "WATER VALIDATE RESPONSE : ". strip_tags($ElmaResponse));
		if($responseData[1] == "000" || $responseData[1] == "OK"){
			//$Data = explode(':',$responseData[3]);
			$response = (object) array('id' => 'waterValidate', 'action' => 'con', 'response' => $responseData[3]."\n1.Accept\n00.Back", 'map' => array( (object) array('menu' => 'WaterformAmount')), 'type' => 'form');
		}else{
			$response = (object) array('id' => 'NATWATERUG', 'action' => 'con', 'response' => "Invalid account: Enter account number\n00.Back\n0.Main Menu", 'map' => array( (object) array('menu' => 'waterValidate')), 'type' => 'form');
		}
		return $response;
		
	}
	
	public function waterConfirm(){
		$Accounts = explode(',',Cache::get($this->msisdn.'ACCOUNTS'));
		$Selected = Cache::has($this->msisdn.'WaterAccounts') ? Cache::get($this->msisdn.'WaterAccounts') : "";
		$index = $Selected - 1;
        $Account = $Accounts[$index];
		
		$utilityAccount = Cache::get($this->msisdn.'NATWATERUG');
		$Amount = Cache::get($this->msisdn.'WaterformAmount');
		$utilityAccount = Cache::get($this->msisdn.'NATWATERUG');
		$message = "National Water\nPay ".$Amount." UGX to National Water account ".$utilityAccount."\n Reply with:\n1. Accept\n2. Cancel";

		$response = (object) array('id' => 'waterConfirm', 'action' => 'con', 'response' => $message, 'map' => array( (object) array('menu' => 'waterPin')), 'type' => 'form');

		return $response;
    }
	
	public function waterPin()
	{
		
		if(Cache::get($this->msisdn.'waterConfirm')!=="2")
		{
			  return (object) array('id' => 'waterPin', 'action' => 'con', 'response' => 'Enter Pin to complete transaction ' . PHP_EOL . '0. Home ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'waterPinConfirm')), 'type' => 'form');
		}
		else{
				return (object) array('id' => 'waterPin', 'action' => 'con', 'response' => "Transaction request was cancelled: \n0. Home \n000. Logout", 'map' => array( (object) array('menu' => 'waterPin')), 'type' => 'form');
	
		}
	}
	
	public function waterPinConfirm(){		
				$utilityAccount = Cache::get($this->msisdn.'NATWATERUG');
				$Selected = Cache::get($this->msisdn.'WaterAccounts');
				$Accounts = explode(',',Cache::get($this->msisdn.'ACCOUNTS'));
				$CustomerID = Cache::get($this->msisdn.'CUSTOMERID');
				$Index = $Selected - 1;
				$Account = $Accounts[$Index];
				$Amount = Cache::get($this->msisdn.'WaterformAmount');
				
				$Areas = ['Kampala','Jinja','Entebe','Iganga','Lugazi','Mukono','Kajjansi','Kawuku','Others'];
				$selectedArea = Cache::get($this->msisdn.'formWaterArea');
				$Area = $Areas[$selectedArea - 1];
				
				$pin = Cache::get($this->msisdn . 'waterPin');
				
				$DataToSend = 'FORMID:M-:MERCHANTID:007001003:INFOFIELD1:'.$Area.':INFOFIELD1:' . $utilityAccount . ':INFOFIELD9:'.$this->msisdn.':TMPIN:'.$pin.':BANKACCOUNTID:'.$Account.':ACCOUNTID:'. $utilityAccount .':CUSTOMERID:'. $CustomerID .':MOBILENUMBER:'.$this->msisdn.':BANKNAME:'.$this->BankName.':SHORTCODE:'.$this->shortcode.':BANKID:'. $this->DefaultBankID .':COUNTRY:'. $this->Country .':AMOUNT:'.$Amount.':NP05052005:YES:ACTION:PAYBILL:QUICKPAY:NO:UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:';
				$this->logErr($this->msisdn, "NATWATER REQUEST : ". $DataToSend);
				$ElmaResponse = $this->ElmaU($DataToSend);
				$responseData = explode(':', strip_tags($ElmaResponse));
				$this->logErr($this->msisdn, "NATWATER RESPONSE : ". strip_tags($ElmaResponse));
				
				if(count($responseData) < 2){
					$response = (object) array('id' => 'waterPinConfirm', 'action' => 'con', 'response' => "There was a problem processing your request. Please try again later:\n00.Back\n0.Main Menu", 'map' => array( (object) array('menu' => 'waterPinConfirm')), 'type' => 'form');
				}else{
					if($responseData[1] == "000" || $responseData[1] == "OK"){
						$response = (object) array('id' => 'waterPinConfirm', 'action' => 'con', 'response' => $responseData[3], 'map' => array( (object) array('menu' => 'waterPinConfirm')), 'type' => 'form');
					}else{
						$response = (object) array('id' => 'waterPinConfirm', 'action' => 'con', 'response' => $responseData[3].":\n00.Back\n0.Main Menu", 'map' => array( (object) array('menu' => 'waterPinConfirm')), 'type' => 'form');
					}
				}			
		return $response;
		
	}

}
