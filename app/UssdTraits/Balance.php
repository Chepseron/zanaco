<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait Balance
{
	
	public function BalanceAccounts()
	{
		$Accounts = explode(",", Cache::get($this->msisdn.'ACCOUNTS'));
		$message = "Select Account:\n";
		
		$num = 1;
		foreach($Accounts as $Account){
			if($Account != ""){
				$message .= $num.". Ugafode/".$Account."\n";
				$num++;
			}
		}
		
		$response = (object) array('id' => 'BalanceAccounts', 'action' => 'con', 'response' => $message, 'map' => array( (object) array('menu' => 'AccountBalance')), 'type' => 'form');
		
		return $response;
	}
	
    public function AccountBalance(){
		$Accounts = explode(",", Cache::get($this->msisdn.'ACCOUNTS'));
		$Index = Cache::get($this->msisdn.'BalanceAccounts') - 1;
        $BankAccountID = $Accounts[$Index];
        $CustomerID = Cache::get($this->msisdn.'CUSTOMERID');
        $DataToSend = 'FORMID:B-:MERCHANTID:BALANCE:BANKACCOUNTID:'. $BankAccountID .':SHORTCODE:'. $this->shortcode .':CUSTOMERID:'. $CustomerID .':MOBILENUMBER:'.$this->msisdn.':BANKID:'. $this->DefaultBankID .':COUNTRY:'. $this->Country .':UNIQUEID:'.$this->guid().':';
        $this->logErr($this->msisdn, "BALANCE REQUEST : ". $DataToSend );
        $ElmaResponse = $this->ElmaU($DataToSend);
        $responseData = explode(':', strip_tags($ElmaResponse));
        $this->logErr($this->msisdn, "BALANCE RESPONSE : ". strip_tags($ElmaResponse) ." shown");
		
        $response = "";
        if($responseData[1] == "OK"){
            $Balance = explode("|", $responseData[3]);
            $message = str_replace("|", " ", $responseData[3])."\n00. Home \n000. Exit";
            $response = (object) array('id' => 'AccountBalance', 'action' => 'con', 'response' => $message, 'map' => array( (object) array('menu' => 'AccountBalance')), 'type' => 'form');
        }else {
            $response = (object) array('id' => 'AccountBalance', 'action' => 'end', 'response' => $responseData[3], 'map' => array( (object) array('menu' => 'BalanceAccounts')), 'type' => 'static');
        }
		
        return $response;

    }
	
}