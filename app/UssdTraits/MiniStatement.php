<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait MiniStatement
{
	
	public function StatementAccounts()
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
		
		$response = (object) array('id' => 'StatementAccounts', 'action' => 'con', 'response' => $message, 'map' => array( (object) array('menu' => 'AccountStatement')), 'type' => 'form');
		
		return $response;
	}
	
    public function AccountStatement(){
        $Accounts = explode(",", Cache::get($this->msisdn.'ACCOUNTS'));
		$Index = Cache::get($this->msisdn.'StatementAccounts') - 1;
        $BankAccountID = $Accounts[$Index];
        $CustomerID = Cache::get($this->msisdn.'CUSTOMERID');
        $DataToSend = 'FORMID:B-:MERCHANTID:STATEMENT:BANKACCOUNTID:'. $BankAccountID .':SHORTCODE:'.$this->shortcode.':CUSTOMERID:'. $CustomerID .':MOBILENUMBER:'.$this->msisdn.':BANKID:'. $this->DefaultBankID .':COUNTRY:'. $this->Country .':UNIQUEID:'.$this->guid().':';
        $this->logErr($this->msisdn, "MINISTATEMENT REQUEST : ". $DataToSend );
        $ElmaResponse = $this->ElmaU($DataToSend);
        $responseData = explode(':', strip_tags($ElmaResponse));
        $this->logErr($this->msisdn, "MINISTATEMENT RESPONSE : ". strip_tags($ElmaResponse) );


        $response = "";
        if($responseData[1] == "OK" || $responseData[1] == "000"){
            $Statement = str_replace("~", "Mini Statement\n", $responseData[3]);
            $response = (object) array('id' => 'AccountStatement', 'action' => 'con', 'response' => $Statement. PHP_EOL .'0. Back 00. Home 000. Exit', 'map' => array( (object) array('menu' => 'AccountStatement')), 'type' => 'form');
        }else{
            $response = (object) array('id' => 'AccountStatement', 'action' => 'con', 'response' =>$responseData[3]. PHP_EOL .'0. Back 00. Home 000. Exit', 'map' => array( (object) array('menu' => 'AccountStatement')), 'type' => 'form');
        }
        return $response;
    }
	
}