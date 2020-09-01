<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait UmemePostpaid {

    public function UmemePostpaidAccounts() {
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

            $response = (object) array('id' => 'UmemePostpaidAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'umemePostpaidConfirm')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function umemePostpaidValidate() {

        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $utilityAccount = Cache::get($this->msisdn . 'umemePostpaid');

        $DataToSend = 'FORMID:M-:MERCHANTID:007001002:ACCOUNTID:' . $utilityAccount . ':INFOFIELD9:'.$this->msisdn.':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':BANKNAME:' . $this->BankName . ':SHORTCODE:' . $this->shortcode . ':BANKID:' . $this->DefaultBankID . ':COUNTRY:' . $this->Country . ':ACTION:GETNAME:UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
        $this->logErr($this->msisdn, "UMEME POSTPAID VALIDATE REQUEST : " . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $responseData = explode(':', strip_tags($ElmaResponse));
        $this->logErr($this->msisdn, "UMEME POSTPAID VALIDATE RESPONSE : " . strip_tags($ElmaResponse));

        if ($responseData[1] == "000" || $responseData[1] == "OK") {
            //$Data = explode('|',$responseData[3]);
            $response = (object) array('id' => 'umemePostpaidValidate', 'action' => 'con', 'response' => $responseData[3] . "\n1.Accept\n00.Back", 'map' => array((object) array('menu' => 'umemePostpaidAmount')), 'type' => 'form');
        } else {
            $response = (object) array('id' => 'umemePostpaid', 'action' => 'con', 'response' => "Invalid account: Enter your smart card number or DSTV account number:r:\n00.Back\n0.Main Menu", 'map' => array((object) array('menu' => 'umemePostpaidValidate')), 'type' => 'form');
        }

        return $response;
    }

    public function umemePostpaidConfirm() {
        $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
        $Selected = Cache::has($this->msisdn . 'UmemePostpaidAccounts') ? Cache::get($this->msisdn . 'UmemePostpaidAccounts') : "";
        $Index = $Selected - 1;
        $Account = $Accounts[$Index];

        $utilityAccount = Cache::get($this->msisdn . 'umemePostpaid');
        $Amount = Cache::get($this->msisdn . 'umemePostpaidAmount');

        $message = "Umeme Postpaid\nPay " . $Amount . " UGX to account " . $utilityAccount . "\n Reply with:\n1. Accept\n2. Cancel";

        $response = (object) array('id' => 'umemePostpaidConfirm', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'umemePostPaidPin')), 'type' => 'form');

        return $response;
    }
	
	
	public function umemePostPaidPin(){
		if(Cache::get($this->msisdn.'umemePostpaidConfirm') !== "2")
		{
			   return (object) array('id' => 'umemePostPaidPin', 'action' => 'con', 'response' => 'Enter Pin to complete transaction ' . PHP_EOL . '0. Home ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'umemePostpaidPinConfirm')), 'type' => 'form');
		}
		else{
				return (object) array('id' => 'umemePostPaidPin', 'action' => 'con', 'response' => "Transaction request was cancelled: \n0. Home \n000. Logout", 'map' => array( (object) array('menu' => 'umemePostPaidPin')), 'type' => 'form');
		}
	}


    public function umemePostpaidPinConfirm() {


                $Selected = Cache::get($this->msisdn . 'UmemePostpaidAccounts');
                $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
                $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
                $Index = $Selected - 1;
                $Account = $Accounts[$Index];
                $utilityAccount = Cache::get($this->msisdn . 'umemePostpaid');
                $Amount = Cache::get($this->msisdn . 'umemePostpaidAmount');
				
				$pin = Cache::get($this->msisdn . 'umemePostPaidPin');

                $DataToSend = 'FORMID:M-:MERCHANTID:007001002:BANKACCOUNTID:' . $Account . ':INFOFIELD9:'.$this->msisdn.':INFOFIELD1:' . $utilityAccount . ':ACCOUNTID:' . $utilityAccount . ':TMPIN:'.$pin.':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':BANKNAME:' . $this->BankName . ':SHORTCODE:' . $this->shortcode . ':BANKID:' . $this->DefaultBankID . ':COUNTRY:' . $this->Country . ':AMOUNT:' . $Amount . ':NP05052005:YES:ACTION:PAYBILL:QUICKPAY:NO:UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
                $this->logErr($this->msisdn, "UMEME POSTPAID REQUEST : " . $DataToSend);
                $ElmaResponse = $this->ElmaU($DataToSend);
                $responseData = explode(':', strip_tags($ElmaResponse));
                $this->logErr($this->msisdn, "UMEME POSTPAID RESPONSE : " . strip_tags($ElmaResponse));

                if (count($responseData) < 2) {
                    $response = (object) array('id' => 'umemePostpaidPinConfirm', 'action' => 'end', 'response' => "There was a problem processing your request. Please try again later:\n00.Back\n0.Main Menu", 'map' => array((object) array('menu' => 'umemePostpaidPinConfirm')), 'type' => 'static');
                } else {
                    if ($responseData[1] == "000" || $responseData[1] == "OK") {
                        $response = (object) array('id' => 'umemePostpaidPinConfirm', 'action' => 'con', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'umemePostpaidPinConfirm')), 'type' => 'form');
                    } else {
                        $response = (object) array('id' => 'umemePostpaidPinConfirm', 'action' => 'con', 'response' => $responseData[3] . ":\n00.Back\n0.Main Menu", 'map' => array((object) array('menu' => 'umemePostpaidPinConfirm')), 'type' => 'form');
                    }
                }
           
        return $response;
    }

}
