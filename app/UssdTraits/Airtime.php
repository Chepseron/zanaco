<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Session;
use Carbon\Carbon;

trait Airtime {

    public function AirtimeOwnAccounts() {
        $all_accounts = Cache::get($this->msisdn . 'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if (count($Accounts) > 0) {
            $message = "Select Source Account \n";

            $num = 1;
            foreach ($Accounts as $Account) {
                if ($Account != "") {
                    $message .= $num . ". Ugafode/" . $Account . "\n";
                    $num++;
                }
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'AirtimeOwnAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'Airtime')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function AirtimeOtherAccounts() {
        $all_accounts = Cache::get($this->msisdn . 'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if (count($Accounts) > 0) {
            $message = "Select Source Account \n";

            $num = 1;
            foreach ($Accounts as $Account) {
                if ($Account != "") {
                    $message .= $num . ". " . $Account . "\n";
                    $num++;
                }
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'AirtimeOtherAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'Airtime')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function Airtime() {
        $otherPhone = Cache::has($this->msisdn . 'pinlessMsisdn2') ? Cache::get($this->msisdn . 'pinlessMsisdn2') : "";
        $phone = "";
        if ($otherPhone != "") {
            $phone = "256" . substr($otherPhone, 1);
        } else {
            $phone = $this->msisdn;
        }
        $TopUpAmount = Cache::get($this->msisdn . 'PinlessAirtimeAmount');

        $prefix = substr($phone, 0, 5);
        $network = ""; 
        $webservice = "";
        if ($prefix == "25677" || $prefix == "25678" || $prefix == "25639" ) {
            $network = "MTN";
            $webservice = "MTNUGAIRTIME";
       } else if ($prefix == "25670" || $prefix == "25675" || $prefix == "25620") {
            $network = "AIRTEL";
            $webservice = "AIRTELUG";
        } else if ($prefix == "25679") {
            $network = "Africell";
            $webservice = "AFRICELUG";
        } else if ($prefix == "25671") {
            $network = "UTL";
            $webservice = "UTLUG";
        }

        Cache::add($this->msisdn . 'NETWORK', $network, 2);
        Cache::add($this->msisdn . 'WEBSERVICE', $webservice, 2);
        Cache::add($this->msisdn . 'TOPUPPHONE', $phone, 2);

        $Selected = Cache::has($this->msisdn . 'AirtimeOwnAccounts') ? Cache::get($this->msisdn . 'AirtimeOwnAccounts') : Cache::get($this->msisdn . 'AirtimeOtherAccounts');
        $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $Index = $Selected - 1;
        $Account = $Accounts[$Index];

        $response = "";
        $response = "You are buying airtime of " . $TopUpAmount . " from  Ugafode/" . $Account . " for ".$phone."  Reply with:\n1. Accept\n2. Cancel \n0. Home";

        return (object) array('id' => 'Airtime', 'action' => 'con', 'response' => $response, 'map' => array((object) array('menu' => 'AirtimePin')), 'type' => 'form');
    }
	
	public function AirtimePin()
	{
		if(Cache::get($this->msisdn.'Airtime')!=="2")
		{
			 return (object) array('id' => 'AirtimePin', 'action' => 'con', 'response' => 'Enter Pin to complete transaction ' . PHP_EOL . '0. Home ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'AirtimeConfirm')), 'type' => 'form');
		}
		else{
			 return (object) array('id' => 'AirtimePin', 'action' => 'con', 'response' => 'Transaction request was cancelled. ' . PHP_EOL . '00. Home ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'AirtimePin')), 'type' => 'form');
		}
	}

    public function AirtimeConfirm() {    
                $trx_start = microtime(true);
                $Selected = Cache::has($this->msisdn . 'AirtimeOwnAccounts') ? Cache::get($this->msisdn . 'AirtimeOwnAccounts') : Cache::get($this->msisdn . 'AirtimeOtherAccounts');
                $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
                $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
                $Index = $Selected - 1;
                $Account = $Accounts[$Index];
                $phone = Cache::has($this->msisdn . 'TOPUPPHONE') ? Cache::get($this->msisdn . 'TOPUPPHONE') : "";

				$pin = Cache::get($this->msisdn . 'AirtimePin');
				
                $network = Cache::get($this->msisdn . 'NETWORK');
                $webservice = Cache::get($this->msisdn . 'WEBSERVICE');

                $TopUpAmount = Cache::get($this->msisdn . 'PinlessAirtimeAmount');
                $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
                $BankName = "FTB";
                $DataToSend = 'FORMID:M-:MERCHANTID:' . $webservice . ':INFOFIELD1:' . $phone . ':ACCOUNTID:' . $phone . ':AMOUNT:' . $TopUpAmount . ':TMPIN:'.$pin.':NP05052005:YES:BANKNAME:' . $this->BankName . ':BANKACCOUNTID:' . $Account . ':ACTION:PAYBILL:CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':SHORTCODE:' . $this->shortcode . ':BANKID:' . $this->DefaultBankID . ':COUNTRY:' . $this->Country . ':QUICKPAY:NO:UNIQUEID:' . $this->guid() . ':';
                $this->logErr($this->msisdn, "AIRTIME REQUEST : " . $DataToSend);
                $ElmaResponse = $this->ElmaU($DataToSend);
                $this->logErr($this->msisdn, "AIRTIME RESPONSE : " . strip_tags($ElmaResponse));
                $responseData = explode(':', strip_tags($ElmaResponse));

                $trx_end = microtime(true);
                Log::notice(Carbon::now() . " - " . $this->msisdn . "- AIRTIME TRANSACTION TIME : " . ($trx_start - $trx_end));

                $response = "";
                if (count($responseData) > 1) {
                    if ($responseData[1] == "000") {
                        $response = (object) array('action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'AirtimeConfirm')), 'type' => 'static');
                    } else if ($responseData[1] == "091") {
                        $response = (object) array('action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'groupsave')), 'type' => 'static');
                    } else {
                        $response = (object) array('action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'groupsave')), 'type' => 'static');
                    }
                } else {
                    $response = (object) array('action' => 'end', 'response' => "There was a problem processing your request. Please try again later.", 'map' => array((object) array('menu' => 'groupsave')), 'type' => 'static');
                }
                return $response;
          
    }

}
