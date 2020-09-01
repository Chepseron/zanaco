<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait UmemeYaka {

    public function UmemeYakaAccounts() {
        $all_accounts = Cache::get($this->msisdn . 'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if (count($Accounts) > 0) {
            $message = "Select Account \n";

            $num = 1;
            foreach ($Accounts as $Account) {
                if ($Account != "") {
                    $message .= $num . ". Ugafode/" . $Account . "\n";
                    $num++;
                }
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'UmemeYakaAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'umemeYakaConfirm')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function umemeYakaValidate() {

        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $utilityAccount = Cache::get($this->msisdn . 'umemeYaka');

        $DataToSend = 'FORMID:M-:MERCHANTID:007001012:ACCOUNTID:' . $utilityAccount . ':CUSTOMERID:' . $CustomerID . ':INFOFIELD9:' . $this->msisdn . ':MOBILENUMBER:' . $this->msisdn . ':BANKNAME:' . $this->BankName . ':SHORTCODE:' . $this->shortcode . ':BANKID:' . $this->DefaultBankID . ':COUNTRY:' . $this->Country . ':ACTION:GETNAME:UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
        $this->logErr($this->msisdn, "UMEME YAKA VALIDATE REQUEST : " . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $responseData = explode(':', strip_tags($ElmaResponse));
        $this->logErr($this->msisdn, "UMEME YAKA VALIDATE RESPONSE : " . strip_tags($ElmaResponse));

        if ($responseData[1] == "000" || $responseData[1] == "OK") {
            //$Data = explode('|',$responseData[3]);
            $response = (object) array('id' => 'umemeYakaValidate', 'action' => 'con', 'response' => $responseData[3] . "\n1.Accept\n00.Back", 'map' => array((object) array('menu' => 'umemeYakaAmount')), 'type' => 'form');
        } else {
            $response = (object) array('id' => 'umemeYaka', 'action' => 'con', 'response' => "Invalid account: Enter your Umeme Yaka account number\n00.Back\n0.Main Menu", 'map' => array((object) array('menu' => 'umemeYakaValidate')), 'type' => 'form');
        }

        return $response;
    }

    public function umemeYakaConfirm() {
        $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
        $Selected = Cache::has($this->msisdn . 'UmemeYakaAccounts') ? Cache::get($this->msisdn . 'UmemeYakaAccounts') : "";
        $Index = $Selected - 1;
        $Account = $Accounts[$Index];

        $utilityAccount = Cache::get($this->msisdn . 'umemeYaka');
        $Amount = Cache::get($this->msisdn . 'umemeYakaAmount');

        $message = "Umeme Yaka\nPay " . $Amount . " UGX to account " . $utilityAccount . "\n Reply with:\n1. Accept\n2. Cancel";

        $response = (object) array('id' => 'umemeYakaConfirm', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'umemeYakaPin')), 'type' => 'form');

        return $response;
    }
	
	public function umemeYakaPin(){
		
		if(Cache::get($this->msisdn.'umemeYakaConfirm')!=="2")
		{
			   return (object) array('id' => 'umemeYakaPin', 'action' => 'con', 'response' => 'Enter Pin to complete transaction ' . PHP_EOL . '0. Home ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'umemeYakaPinConfirm')), 'type' => 'form');
		}
		else{
			 return (object) array('id' => 'umemeYakaPin', 'action' => 'con', 'response' => "Transaction request was cancelled: \n0. Home \n000. Logout", 'map' => array((object) array('menu' => 'umemeYakaPin')), 'type' => 'form');
		}
	}

    public function umemeYakaPinConfirm() {    

                $Selected = Cache::get($this->msisdn . 'UmemeYakaAccounts');
                $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
                $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
                $Index = $Selected - 1;
                $Account = $Accounts[$Index];
                $utilityAccount = Cache::get($this->msisdn . 'umemeYaka');
                $Amount = Cache::get($this->msisdn . 'umemeYakaAmount');
				$pin = Cache::get($this->msisdn . 'umemeYakaPin');

                $DataToSend = 'FORMID:M-:MERCHANTID:007001012:BANKACCOUNTID:' . $Account . ':INFOFIELD9:'.$this->msisdn.':INFOFIELD1:' . $utilityAccount . ':ACCOUNTID:' . $utilityAccount . ':TMPIN:'.$pin.':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':BANKNAME:' . $this->BankName . ':SHORTCODE:' . $this->shortcode . ':BANKID:' . $this->DefaultBankID . ':COUNTRY:' . $this->Country . ':AMOUNT:' . $Amount . ':NP05052005:YES:ACTION:PAYBILL:QUICKPAY:NO:UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
                $this->logErr($this->msisdn, "UMEME YAKA REQUEST : " . $DataToSend);
                $ElmaResponse = $this->ElmaU($DataToSend);
                $responseData = explode(':', strip_tags($ElmaResponse));
                $this->logErr($this->msisdn, "UMEME YAKA RESPONSE : " . strip_tags($ElmaResponse));

                if (count($responseData) < 2) {
                    $response = (object) array('id' => 'umemeYakaPinConfirm', 'action' => 'end', 'response' => "There was a problem processing your request. Please try again later:\n00.Back\n0.Main Menu", 'map' => array((object) array('menu' => 'umemeYakaPinConfirm')), 'type' => 'static');
                } else {
                    if ($responseData[1] == "000" || $responseData[1] == "OK") {
                        $response = (object) array('id' => 'umemeYakaPinConfirm', 'action' => 'con', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'umemeYakaPinConfirm')), 'type' => 'form');
                    } else {
                        $response = (object) array('id' => 'umemeYakaPinConfirm', 'action' => 'con', 'response' => $responseData[3] . ":\n00.Back\n0.Main Menu", 'map' => array((object) array('menu' => 'umemeYakaPinConfirm')), 'type' => 'form');
                    }
                }
            
        return $response;
    }

}
