<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UssdTraits\Menus;
use App\UssdTraits\Dstv;
use App\UssdTraits\StarTimes;
use App\UssdTraits\UmemePostpaid;
use App\UssdTraits\UmemeYaka;
use App\UssdTraits\KisWater;
use App\UssdTraits\Water;
use App\UssdTraits\Balance;
use App\UssdTraits\MiniStatement;
use App\UssdTraits\FullStatement;
use App\UssdTraits\Airtime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Session;
use Carbon\Carbon;

class UssdController extends Controller {

    use Menus;
    use Dstv;
    use StarTimes;
    use UmemePostpaid;
    use UmemeYaka;
    use KisWater;
    use Water;
    use Balance;
    use MiniStatement;
    use FullStatement;
    use Airtime;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $msisdn;
    public $shortcode;
    public $DefaultBankID;
    public $BankName;
    public $Country;
    public $month;
    public $day;
    public $year;

    public function __construct(Request $request) {
        $this->msisdn = $request->msisdn;
        $this->shortcode = $request->shortcode;
        $this->DefaultBankID = env('BANK_ID');
        $this->BankName = env('BANKNAME');
        $this->Country = env('COUNTRY');
    }

    public function mainMenu(Request $request) {
        if (Cache::get($this->msisdn . 'loggedIn')) {
            Cache::forget($this->msisdn . 'menu');
            Cache::forget($this->msisdn . 'KITS');
            $menu = $this->MenuHandler('home');
            $this->logErr($this->msisdn, "MENU HERE : " . $menu->response);
            Cache::add($this->msisdn . 'menu', 'home', 2);
            $menu->action = "con";
            return $menu->action . " " . str_replace('VirtualACC', 'You Qualify for Kes ' . Cache::get($this->msisdn . 'LOANLIMIT') . ' Timiza Loan', $menu->response);
        }
    }

    public function back(Request $request) {
        if (Cache::get($this->msisdn . 'loggedIn')) {
            Cache::forget($this->msisdn . 'menu');
            $menu = $this->MenuHandler('home');
            $this->logErr($this->msisdn, "MENU HERE : " . $menu->response);
            Cache::add($this->msisdn . 'menu', 'home', 2);
            $menu->action = "con";
            return $menu->action . " " . str_replace('VirtualACC', 'You Qualify for Kes ' . number_format(Cache::get($this->msisdn . 'LOANLIMIT')) . ' Timiza Loan', $menu->response);
        }
    }

    public function logout() {
        if (Cache::get($this->msisdn . 'loggedIn')) {
            Cache::forget($this->msisdn . 'menu');
            Cache::forget($this->msisdn . 'KITS');
            $this->logErr($this->msisdn, "Logout Log");
            return "end Thank you for banking with us";
        }
    }

    public function index(Request $request) {
//
        $menu = "";

        if ($request == "000") {
            if (Cache::get($this->msisdn . 'loggedIn')) {
                Cache::forget($this->msisdn . 'menu');
                Cache::forget($this->msisdn . 'loggedIn');
                Cache::forget($this->msisdn . 'reset');
                $this->logErr($this->msisdn, "Logout Log");
//return "end Thank you for banking with us";
                return (object) array('id' => 'Exit', 'action' => 'end', 'Thank you for banking with us', 'map' => array((object) array('menu' => 'Exit')), 'type' => 'static');
            }
        }

        Cache::add($this->msisdn . 'msisdn', $request->msisdn, 2);
        if ($request->response == "") {
//Cache::flush();
            Cache::forget($this->msisdn . 'loggedIn');
            Cache::forget($this->msisdn . 'reset');
            Cache::forget($this->msisdn . "menu");
            Cache::forget($this->msisdn . 'formWaterArea');
            $start_time = microtime(true);
            $getCustomer = $this->GetCustomer($this->msisdn, $this->shortcode);
            $getCustomer = strip_tags($getCustomer);
            $getCustomer = explode(':', $getCustomer);
            $this->logErr($this->msisdn, "GET CUSTOMER" . $getCustomer[1]);

            Cache::add($this->msisdn . 'menu', 'menu', 10);
            $end_time = microtime(true);
            $this->logErr($this->msisdn, "GET CUSTOMER TIME : " . (($end_time - $start_time) * 0.000001));
            Cache::forget($this->msisdn . 'menu');
            Cache::add($this->msisdn . 'pin', $request->response, 2);
            Cache::add($this->msisdn . 'menu', 'home', 2);
            Cache::add($this->msisdn . 'loggedIn', 1, 2);
            $res = $this->MenuHandler(Cache::get($this->msisdn . 'menu'));
            $res->action = "con";
            $message = "";
            if (Cache::get($this->msisdn . 'LOANLIMIT') > 0) {
                $message = str_replace('VirtualACC', 'You Qualify for Kes ' . number_format(Cache::get($this->msisdn . 'LOANLIMIT')) . ' Timiza Loan', $res->response);
            } else {
                $message = str_replace('VirtualACC', 'Select:' . PHP_EOL, $res->response);
                ;
            }
            return $res->action . " " . $message;
        }

//$menu = Cache::get($this->msisdn.'menu');
//echo $menu;
//$isLoggedIn = Cache::has('loggedIn') ? 1 : 0;
        $isLoggedIn = Cache::has($this->msisdn . 'loggedIn') ? 1 : 0;
        $isRegistration = Cache::has($this->msisdn . 'registration') ? 1 : 0;
        $isPinReset = Cache::has($this->msisdn . 'reset') ? 1 : 0;

        $this->logErr($this->msisdn, "MENU : " . Cache::get($this->msisdn . 'menu'));

        $this->logErr($this->msisdn, "MENU INPUT : " . $request->response);
//echo "LOGGEDIN :".$isLoggedIn;

        if ($isRegistration) {
//=============================== REGISTRATION ==============================
            if ($request->response != "") {
                $menu = Cache::add($this->msisdn . Cache::get($this->msisdn . 'menu'), $request->response, 2);
                $this->logErr($this->msisdn, "MENU RESPONSES : " . Cache::get($this->msisdn . 'menu') . " -> " . Cache::get(Cache::get($this->msisdn . 'menu')));
                $menu = Cache::get($this->msisdn . 'menu');
                $response = "";
                if (Cache::has($this->msisdn . 'menu')) {
                    $this->logErr($this->msisdn, "DOWN : " . Cache::get($this->msisdn . 'menu'));
                    $menu1 = $this->MenuHandler($menu);
                    $this->logErr($this->msisdn, "DOWN MENU : " . serialize($menu1));
//print_r($menu1->type);
                    $menu_type = $menu1->type;
                    if ($menu_type == "dynamic") {
                        $pos = $request->response - 1;
                        if (count($menu1->map) < $pos) {
                            $menu = $this->MenuHandler(Cache::get($this->msisdn . 'menu'));
                            Cache::forget($this->msisdn . 'menu');
                            Cache::add($this->msisdn . 'menu', $menu->id, 2);
                            $response = $menu->action . " " . $menu->response;
                        } else {
                            $menu = $this->MenuHandler($menu1->map[$pos]->menu);
                            Cache::forget($this->msisdn . 'menu');
                            Cache::add($this->msisdn . 'menu', $menu->id, 2);
                            $response = $menu->action . " " . $menu->response;
                        }
                    } else if ($menu_type == "form") {
                        $next_menu = $menu1->map[0]->menu;
                        $menu = $this->MenuHandler($next_menu, $request->response);
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'menu', $next_menu, 2);
                        $response = $menu->action . " " . $menu->response;
                    } else if ($menu_type == "static") {
                        $response = $menu1->action . " " . $menu1->response;
                    }
                } else {
                    $menu = $this->MenuHandler("ZanacoRegistrationValidation");
                    Cache::forget($this->msisdn . 'menu');
                    Cache::add($this->msisdn . 'menu', 'ZanacoRegistrationValidation', 2);
                    $response = $menu->action . " " . $menu->response;
                }

                return $response;
            } else {
                $menu = $this->MenuHandler("ZanacoRegistrationValidation");
                Cache::forget($this->msisdn . 'menu');
                Cache::add($this->msisdn . 'menu', 'ZanacoRegistrationValidation', 2);
                $response = $menu->action . " " . $menu->response;
                return $response;
            }
//=============================== END REGISTRATION ==========================
        } else if (!$isLoggedIn) {
            $response = "";
//Cache::forget('menu');
            Cache::forget($this->msisdn . 'menu');

//session(['menu' => 'pin']);
            Cache::add($this->msisdn . 'menu', 'pin', 2);

            if ($request->response == "") {
                return "con Welcome to Barclays Timiza.Enter your pin to continue";
            } else if (Cache::get($this->msisdn . 'menu') == "pin" && $request->response == "1") {
// return "con Hello " . Cache::get($this->msisdn . 'FIRSTNAME') . ", Welcome to UGAFODE Mobile Banking. Please enter your PIN to proceed";


                Cache::forget($this->msisdn . 'menu');
                Cache::add($this->msisdn . 'pin', $request->response, 2);
                Cache::add($this->msisdn . 'menu', 'home', 2);
                Cache::add($this->msisdn . 'loggedIn', 1, 2);
                $res = $this->MenuHandler(Cache::get($this->msisdn . 'menu'));
                $res->action = "con";
                $message = "";
                if (Cache::get($this->msisdn . 'LOANLIMIT') > 0) {
                    $message = str_replace('VirtualACC', 'You Qualify for Kes ' . number_format(Cache::get($this->msisdn . 'LOANLIMIT')) . ' Timiza Loan', $res->response);
                } else {
                    $message = str_replace('VirtualACC', 'Select:' . PHP_EOL, $res->response);
                    ;
                }
                return $res->action . " " . $message;
            } else if (Cache::get($this->msisdn . 'menu') == "pin" && $request->response == "3") {
                return "con 1. Call us on 0711058000 '.PHP_EOL .'2. E-mail us on pml@Ugafodemicrofinance.co.ug '.PHP_EOL .'3. Chat us on FaceBook - facebook.com/Ugafodeug '.PHP_EOL .'4. Chat us on Twitter - twitter.com/Ugafodeug '.PHP_EOL .'000. Exit";
            } else if (Cache::has($this->msisdn . 'GetCustomerSecurityQuestion') && $request->response != "") {
                return $this->pinReset($request->response);
            } else {
                $validationResponse = $this->Pin($this->msisdn, $this->shortcode, $request->response);
                if ($validationResponse != "") {

                    if ($validationResponse === "000") {
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'pin', $request->response, 2);
                        Cache::add($this->msisdn . 'menu', 'home', 2);
                        Cache::add($this->msisdn . 'loggedIn', 1, 2);
                        $res = $this->MenuHandler(Cache::get($this->msisdn . 'menu'));
                        $res->action = "con";
                        $message = "";
                        if (Cache::get($this->msisdn . 'LOANLIMIT') > 0) {
                            $message = str_replace('VirtualACC', 'You Qualify for Kes ' . number_format(Cache::get($this->msisdn . 'LOANLIMIT')) . ' Timiza Loan', $res->response);
                        } else {
                            $message = str_replace('VirtualACC', 'Select:' . PHP_EOL, $res->response);
                            ;
                        }
                        return $res->action . " " . $message;
                    } else if ($validationResponse === "101") {
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'pin', $request->response, 2);
                        Cache::add($this->msisdn . 'menu', 'newPin11', 2);
                        Cache::add($this->msisdn . 'loggedIn', 1, 2);
                        $res = $this->MenuHandler(Cache::get($this->msisdn . 'menu'));
                        $res->action = "con";
                        return $res->action . " " . $res->response;
                    } else if ($validationResponse === "091") {
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'menu', 'pin', 2);
                        $response = 'con Wrong Pin. Enter your pin to Continue';
                        return $response;
                    } else if ($validationResponse === "102") {
                        Cache::forget($this->msisdn . 'menu');
                        $response = "end Hello, " . Cache::get($this->msisdn . 'FIRSTNAME') . " your PIN is blocked.'.PHP_EOL .' Please visit the nearest branch to reset your PIN. ";
                        return $response;
                    } else if ($validationResponse === "104") {
                        Cache::forget($this->msisdn . 'menu');
                        $response = "end We are verifying your details at the moment, we will get back you shortly";
                        return $response;
                    } else if ($validationResponse === "105") {
                        Cache::forget($this->msisdn . 'menu');
                        $response = "end Hello, we are unable to verify your details.";
                        return $response;
                    } else if ($validationResponse === "106") {
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'pin', $request->response, 2);
                        Cache::add($this->msisdn . 'menu', 'GetBankQuestions', 2);
                        Cache::add($this->msisdn . 'loggedIn', 1, 2);
                        $res = $this->MenuHandler(Cache::get($this->msisdn . 'menu'));
                        $res->action = "con";
                        return $res->action . " " . $res->response;
                    }
                } else {
                    $response = "con Error";
                }
            }

            return $response;
        } else {
//echo "After login :". Cache::get('menu');
            $this->logErr($this->msisdn, "UP : " . Cache::get($this->msisdn . 'menu'));
            if ($request->response != "") {
                if (Cache::has($this->msisdn . Cache::get($this->msisdn . 'menu'))) {
                    Cache::forget($this->msisdn . Cache::get($this->msisdn . 'menu'));
                }
                $menu = Cache::add($this->msisdn . Cache::get($this->msisdn . 'menu'), $request->response, 3);
                $this->logErr($this->msisdn, "MENU RESPONSES : " . Cache::get($this->msisdn . 'menu') . " -> " . Cache::get(Cache::get($this->msisdn . 'menu')));
                $menu = Cache::get($this->msisdn . 'menu');
                $response = "";
                if (Cache::has($this->msisdn . 'menu')) {
                    Cache::forget($this->msisdn . 'menu');
                    Cache::add($this->msisdn . 'menu', 'pinReset', 2);
                    $this->logErr($this->msisdn, "DOWN : " . Cache::get($this->msisdn . 'menu'));
                    $menu1 = $this->MenuHandler($menu);
                    $this->logErr($this->msisdn, "DOWN MENU : " . serialize($menu1));
//print_r($menu1->type);
                    $menu_type = $menu1->type;
                    if ($request->response === "0") {
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'menu', 'home', 2);
                    }
                    if ($menu_type == "dynamic") {
                        $pos = $request->response - 1;
                        if (count($menu1->map) < $request->response) {
                            $menu = $this->MenuHandler(Cache::get($this->msisdn . 'menu'));
                            Cache::forget($this->msisdn . 'menu');
                            Cache::add($this->msisdn . 'menu', $menu->id, 3);
                            $response = $menu->action . " " . $menu->response;
                        } else {
                            $menu = $this->MenuHandler($menu1->map[$pos]->menu);
                            Cache::forget($this->msisdn . 'menu');
                            Cache::add($this->msisdn . 'menu', $menu->id, 3);
                            $response = $menu->action . " " . $menu->response;
                        }
                    } else if ($menu_type == "form") {
                        $next_menu = $menu1->map[0]->menu;
                        $menu = $this->MenuHandler($next_menu, $request->response);
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'menu', $next_menu, 3);
                        $response = $menu->action . " " . $menu->response;
                    } else if ($menu_type == "static") {
                        $response = $menu1->action . " " . $menu1->response;
                    }
                } else {
                    $menu = $this->MenuHandler("home");
                    Cache::forget($this->msisdn . 'menu');
                    Cache::add($this->msisdn . 'menu', 'home', 2);
                    $response = $menu->action . " " . $menu->response;
                }

                return $response;
            } else {
                return "SDFSDfsdf";
            }
        }
    }

    public function MenuHandler($menu, $response = null) {
//
        $menus = json_decode($this->menu());
//var_dump($menu->menu);

        $neededObject = array_filter(
                $menus->menu, function ($e) use ($menu) {
            return $e->id == $menu;
        }
        );

//$this->logErr($this->msisdn, "MENU : ". json_encode($neededObject));
//$this->logErr($this->msisdn, "MENU RESPONSE : ". Cache::get('menu') . " : " . (Cache::has(Cache::get('menu')) ? Cache::get(Cache::get('menu')) : ""));

        if (count(array_values($neededObject)) == 0) {
            $this->logErr($this->msisdn, "ERROR : " . $menu);
            try {
                if (method_exists($this, "LoanProducts")) {

                    $this->logErr($this->msisdn, "MMMMMMM : " . $menu);

                    if (strpos($menu, $this->msisdn)) {
                        $menu = substr($menu, 12);
                    }

                    $res = $this->$menu();

                    if ($res->action == "con") {
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'menu', 'groupsave', 2);
                        return $res;
                    } else if ($res->action == "end") {
                        Cache::forget($this->msisdn . 'menu');
                        return $res;
                    } else {
                        return (object) array('action' => 'end', 'response' => 'Something went wrong', 'map' => array('menu' => 'groupsave'));
                    }
                } else {

                }
            } catch (Exception $ex) {
                return (object) array('action' => 'end', 'response' => 'Something went wrong', 'map' => array('menu' => 'groupsave'));
            }
        } else {
            return array_values($neededObject)[0];
        }
    }

    public function ResponseHandler($request) {
        if (Cache::has($this->msisdn . 'menu')) {
            $menuItem = Session::get($this->msisdn . 'menu');
            $menu = $this->MenuHandler($menuItem);
        }
    }

    public function guid() {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
            $charid = strtolower(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = //chr(123)// "{"
                    ""
                    . substr($charid, 0, 8) . $hyphen
                    . substr($charid, 8, 4) . $hyphen
                    . substr($charid, 12, 4) . $hyphen
                    . substr($charid, 16, 4) . $hyphen
                    . substr($charid, 20, 12)
                    . ""; //.chr(125);// "}"
            return $uuid;
        }
    }

//LOAN REQUEST
    public function requestLoan() {
        try {
            $request = "{\"OurBranchID\":\"004\",\"IDNumber\":" . null . ",\"MobileNumber\":\"" . $this->msisdn . "\"}";
            $this->logErr($this->msisdn, "REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Accounts/GetClientLoanLimit", $request);
            $this->logErr($this->msisdn, "RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = $data['APIResponse'][0]['Response'];

            $realRes = explode('|', $message);
            if ($realRes[1] == 01) {
                return (object) array('id' => 'requestLoan', 'action' => 'con', 'response' => $realRes[3] . PHP_EOL . '00.Back' . PHP_EOL . '0.Main Menu', 'map' => array((object) array('menu' => 'requestLoanProducts')), 'type' => 'form');
            } else {
                return (object) array('id' => 'requestLoan', 'action' => 'con', 'response' =>'Your loan limit is '. $realRes[4] . ' Enter Amount' . PHP_EOL . '00.Back' . PHP_EOL . '0.Main Menu', 'map' => array((object) array('menu' => 'requestLoanProducts')), 'type' => 'form');
            }
        } catch (Exception $e) {
            return (object) array('id' => 'requestLoan', 'action' => 'con', 'response' => 'We are unable to process your request at the moment, try again later' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'requestLoan')), 'type' => 'form');
        }
    }




    public function requestLoanProducts() {
        try {
            $request = "{\"OurBranchID\":\"004\"}";
            $this->logErr($this->msisdn, "REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Products/GetAllProductList", $request);
            $this->logErr($this->msisdn, "RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = "";
            $productID = Array();
			$num = 1;
            foreach ($data['GetProductList'] as $item) {
				
				if($item['ProductID'] == 'FIDE' || $item['ProductID'] == 'MISA'){
					
				}
				else{
					
				$amountDisplay = Cache::get($this->msisdn . 'requestLoan') + $item['Interest']/100*Cache::get($this->msisdn . 'requestLoan')+$item['Fee']/100*Cache::get($this->msisdn . 'requestLoan') . ' in ' . $item['Term'] .' Days loan';
                $message .=$num .'. ZMW '. $amountDisplay . PHP_EOL;
                $productID[] = $item['ProductID'];
				$num ++;
				
				}
            }
            Cache::add($this->msisdn . 'LOANPRODUCTIDS', implode(",", $productID), 4);
            return (object) array('id' => 'requestLoanProducts', 'action' => 'con', 'response' => $message . '' . PHP_EOL . '00.Back' . PHP_EOL . '0.Main Menu', 'map' => array((object) array('menu' => 'confirmRequestLoan')), 'type' => 'form');
        } catch (Exception $e) {
            return (object) array('id' => 'requestLoanProducts', 'action' => 'con', 'response' => 'Your Balance is ZMW 1000,0000' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'requestLoanProducts')), 'type' => 'form');
        }
    }

    public function confirmRequestLoan() {
        return (object) array('id' => 'confirmRequestLoan', 'action' => 'con', 'response' => 'Request Loan ' . Cache::get($this->msisdn . 'requestLoan') . PHP_EOL . '?1.Proceed' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'completeLoanRequest')), 'type' => 'form');
    }

    public function completeLoanRequest() {
        try {
            $selected = Cache::get($this->msisdn . 'requestLoanProducts');
            $index = $selected - 1;
            $Accounts = explode(',', Cache::get($this->msisdn . 'LOANPRODUCTIDS'));


			

            $request = "\"RequestID\": \"" . $this->guid() . "\",   \"OurBranchID\": \“001\”,   \"MobileNumber\": " . $this->msisdn . ",   \"IDNumber\": 0,  \"ProductID\": " . $Accounts[$index] . ",   \"LoanAmount\": " . Cache::get($this->msisdn . 'requestLoan') . ",   \"LoanTerm\": \"2\"";
            $this->logErr($this->msisdn, "COMPLETE LOAN REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Loans/LoanApplicationProcess", $request);
            $this->logErr($this->msisdn, "COMPLETE LOAN RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = $data['APIResponse'][0]['Response'];
            $response = (object) array('id' => 'completeLoanRequest', 'action' => 'con', 'response' => "Choose No:" . PHP_EOL . $message . "0.Back", 'map' => array((object) array('menu' => 'completeLoanRequest')), 'type' => 'form');
            return $response;
        } catch (Exception $e) {
            $response = (object) array('id' => 'completeLoanRequest', 'action' => 'end', 'response' => 'There was a problem processing your request, Please try again' . PHP_EOL . '0. Back', 'map' => array((object) array('menu' => 'completeLoanRequest')), 'type' => 'static');
            return $response;
        }
    }

//LOAN REPAYMENT
    public function loanRepayAmountConfirm() {
        return (object) array('id' => 'loanRepayAmountConfirm', 'action' => 'con', 'response' => 'Repay loan ' . Cache::get($this->msisdn . 'loanRepayAmount') . PHP_EOL . '?1.Proceed' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'loanRepaymentAccounts')), 'type' => 'form');
    }

    public function loanRepaymentAccounts() {
        try {
            $request = "{\"OurBranchID\":\"004\",\"IDNumber\":\"\",\"PhoneNumber\":\"" . $this->msisdn . "\"}";
            $this->logErr($this->msisdn, "REPAYMENT REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Accounts/GetClientByUniqueNumber", $request);
            $this->logErr($this->msisdn, "REPAYMENT RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = "";
            $Accounts = Array();
            $loanRepaymentAccounts = Array();
            $realRes = explode('|', $data['APIResponse'][0]['Response']);

            if ($realRes[1] == 01) {
                return (object) array('id' => 'loanRepaymentAccounts', 'action' => 'con', 'response' => $realRes[3] . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'loanRepayAmountConfirmPinComplete')), 'type' => 'form');
            } else {
                foreach ($data['APIResponse'] as $item) {
                    $message .= $item['Response'];
                    $fullResponse = explode('|', $message);
                    $loanRepaymentAccounts = "Select Destination account" . PHP_EOL . "1-Micro loan " . $fullResponse[11] . PHP_EOL . "2-Fixed Deposit " . $fullResponse[19];
                    $Accounts = $fullResponse[11] . "," . $fullResponse[15] . "," . $fullResponse[19];
                }
                Cache::add($this->msisdn . 'ACCLOANREPAYMENT', $Accounts, 5);
                return (object) array('id' => 'loanRepaymentAccounts', 'action' => 'con', 'response' => $loanRepaymentAccounts . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'loanRepayAmountConfirmPinComplete')), 'type' => 'form');
            }
        } catch (Exception $e) {
            return (object) array('id' => 'loanRepaymentAccounts', 'action' => 'con', 'response' => 'Your request has failed to process, please try again later' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'loanRepaymentAccounts')), 'type' => 'form');
        }
    }

    public function loanRepayAmountConfirmPinComplete() {
        try {
            $selected = Cache::get($this->msisdn . 'loanRepaymentAccounts');
            $index = $selected - 1;
            $Accounts = explode(',', Cache::get($this->msisdn . 'ACCLOANREPAYMENT'));
            $request2 = "";

            if (Cache::get($this->msisdn . 'repayLoan') == "1") {
                $request2 = "\"FromBranchID\": \"01\","
                        . "\"FromAccountID\": \“" . $Accounts[1] . "\”,"
                        . "\"ToBranchID\": \"01\","
                        . "\"ToAccountID\": \“" . $Accounts[$index] . "\”,"
                        . "\"TrxAmount\": " . Cache::get($this->msisdn . 'loanRepayAmount') . ", "
                        . "\"TransactionDescription\": \"Loan repayment\", "
                        . "\"TrxTraceNo\": " . $this->guid() . ","
                        . "\"MobileNo\":\"" . $this->msisdn . "\","
                        . "\"ExtraData\":" . null . "";
            } else {

                $request2 = "\"FromBranchID\": \"01\","
                        . "\"FromAccountID\": \“" . $this->msisdn . "\”,"
                        . "\"ToBranchID\": \"01\","
                        . "\"ToAccountID\": \“" . $Accounts[$index] . "\”,"
                        . "\"TrxAmount\": " . Cache::get($this->msisdn . 'loanRepayAmount') . ", "
                        . "\"TransactionDescription\": \"Loan repayment\", "
                        . "\"TrxTraceNo\": " . $this->guid() . ","
                        . "\"MobileNo\":\"" . $this->msisdn . "\","
                        . "\"ExtraData\":" . null . "";
            }


            $this->logErr($this->msisdn, "REQUEST : " . $request2);
            $results2 = $this->zanacoRequestsPost("Transactions/InternalTransfer", $request2);
            $this->logErr($this->msisdn, "RESULTS : " . $results2);
            $data = json_decode($results2, true);
            $message = $data['APIResponse'][0]['Response'];
            $response = (object) array('id' => 'loanRepayAmountConfirmPinComplete', 'action' => 'con', 'response' => $message . "0.Back", 'map' => array((object) array('menu' => 'loanRepayAmountConfirmPinComplete')), 'type' => 'form');
            return $response;
        } catch (Exception $e) {
            $response = (object) array('id' => 'loanRepayAmountConfirmPinComplete', 'action' => 'end', 'response' => 'There was a problem processing your request, Please try again' . PHP_EOL . '0. Back', 'map' => array((object) array('menu' => 'loanRepayAmountConfirmPinComplete')), 'type' => 'static');
            return $response;
        }
    }

//MINISTATEMENT
    public function MinistatementRequestAccounts() {
        try {
            $request = "{\"OurBranchID\":\"004\",\"IDNumber\":\"\",\"PhoneNumber\":\"" . $this->msisdn . "\"}";
            $this->logErr($this->msisdn, "REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Accounts/GetClientByUniqueNumber", $request);
            $this->logErr($this->msisdn, "RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = "";
            $Accounts = Array();
            $message = $data['APIResponse'][0]['Response'];
            $fullResponse = explode('|', $message);
            if (count($fullResponse) < 11) {
                //Cache::add($this->msisdn . 'ACCOUNTBALANCE', $Accounts, 5);
                return (object) array('id' => 'MinistatementRequestAccounts', 'action' => 'con', 'response' => $fullResponse[3] . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'MinistatementRequestAccounts')), 'type' => 'form');
            } else {
                $ministatementAccount = "1-Micro loan " . $fullResponse[11] . PHP_EOL . "2-Micro Savings " . $fullResponse[15] . PHP_EOL . "3-Fixed Deposit " . $fullResponse[19];
                $Accounts = $fullResponse[11] . "," . $fullResponse[15] . "," . $fullResponse[19];
                Cache::add($this->msisdn . 'ACCMINISTATEMENT', $Accounts, 5);
                return (object) array('id' => 'MinistatementRequestAccounts', 'action' => 'con', 'response' => $ministatementAccount . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'MinistatementPin')), 'type' => 'form');
            }
        } catch (Exception $e) {
            return (object) array('id' => 'MinistatementRequestAccounts', 'action' => 'con', 'response' => 'Your request has failed to process, please try again later' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'MinistatementRequestAccounts')), 'type' => 'form');
        }
    }

    public function MinistatementRequest() {
        try {

            $selected = Cache::get($this->msisdn . 'MinistatementRequestAccounts');
            $index = $selected - 1;
            $Accounts = explode(',', Cache::get($this->msisdn . 'ACCMINISTATEMENT'));

            $request = "{\"OurBranchID\":\"004\",\"AccountID\":\"" . $Accounts[$index] . "\",\"ChargeBit\":0,\"MobileNo\":\"" . $this->msisdn . "\"}";
            $this->logErr($this->msisdn, "REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Accounts/GetMiniStatement", $request);
            $this->logErr($this->msisdn, "RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = $data['APIResponse'][0]['Response'];
            return (object) array('id' => 'MinistatementRequest', 'action' => 'con', 'response' => $message . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'MinistatementRequest')), 'type' => 'form');
        } catch (Exception $e) {
            return (object) array('id' => 'MinistatementRequest', 'action' => 'con', 'response' => 'Your request has failed to process, please try again later' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'MinistatementRequest')), 'type' => 'form');
        }
    }

//BALANCE
    public function BalanceRequestAccounts() {
        try {
            $request = "{\"OurBranchID\":\"004\",\"IDNumber\":\"\",\"PhoneNumber\":\"" . $this->msisdn . "\"}";
            $this->logErr($this->msisdn, "REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Accounts/GetClientByUniqueNumber", $request);
            $this->logErr($this->msisdn, "RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = "";
            $Accounts = Array();
            $message = $data['APIResponse'][0]['Response'];

            $fullResponse = explode('|', $message);

            if (count($fullResponse) < 11) {
                //Cache::add($this->msisdn . 'ACCOUNTBALANCE', $Accounts, 5);
                return (object) array('id' => 'BalanceRequestAccounts', 'action' => 'con', 'response' => $fullResponse[3] . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'BalancePin')), 'type' => 'form');
            } else {
                $balanceAccount = "1-Micro loan " . $fullResponse[11] . PHP_EOL . "2-Micro Savings " . $fullResponse[15] . PHP_EOL . "3-Fixed Deposit " . $fullResponse[19];
                $Accounts = $fullResponse[11] . "," . $fullResponse[15] . "," . $fullResponse[19];
                Cache::add($this->msisdn . 'ACCOUNTBALANCE', $Accounts, 5);
                return (object) array('id' => 'BalanceRequestAccounts', 'action' => 'con', 'response' => $balanceAccount . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'BalancePin')), 'type' => 'form');
            }
        } catch (Exception $e) {
            return (object) array('id' => 'BalanceRequestAccounts', 'action' => 'con', 'response' => 'Your request has failed to process, please try again later' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'BalanceRequestAccounts')), 'type' => 'form');
        }
    }

    public function withdrawmoney() {


        $request = "{\"OurBranchID\":\"004\",\"IDNumber\":\"\",\"PhoneNumber\":\"" . $this->msisdn . "\"}";
        $this->logErr($this->msisdn, "REQUEST : " . $request);
        $results = $this->zanacoRequestsPost("Accounts/GetClientByUniqueNumber", $request);
        $this->logErr($this->msisdn, "RESULTS : " . $results);
        $data = json_decode($results, true);
        $message = "";
        $Accounts = Array();
        $message = $data['APIResponse'][0]['Response'];


        $fullResponse = explode('|', $message);
        $request2 = "{\"OurBranchID\":\"004\",\"AccountID\":\"" . $fullResponse[15] . "\",\"ChargeBit\":0,\"MobileNo\":\"" . $this->msisdn . "\"}";
        $this->logErr($this->msisdn, "REQUEST : " . $request2);
        $results2 = $this->zanacoRequestsPost("Accounts/GetAccountBalance", $request2);
        $this->logErr($this->msisdn, "RESULTS : " . $results2);
        $data = json_decode($results2, true);


        $message2 = $data['APIResponse'][0]['Response'];

        return (object) array('id' => 'withdrawmoney', 'action' => 'con', 'response' => "Your savings account balance is " . $message2 . ". How much do you want to withdraw today? Enter your amount" . PHP_EOL . "000. Cancel " . PHP_EOL . "2. Send" . PHP_EOL . PHP_EOL . '0.Home', 'map' => array((object) array('menu' => 'withdrawmoneyConfirm')), 'type' => 'form');
    }

    public function withdrawmoneyConfirm() {
        return (object) array('id' => 'withdrawmoneyConfirm', 'action' => 'con', 'response' => "Amount entered will be moved from your Xpress Savings account to your Mobile Money Account. " . PHP_EOL . "000. Cancel " . PHP_EOL . "2. Send" . PHP_EOL . PHP_EOL . '0.Home', 'map' => array((object) array('menu' => 'withdrawmoneyConfirmComplete')), 'type' => 'form');
    }

    public function withdrawmoneyConfirmComplete() {
        try {
            $selected = Cache::get($this->msisdn . 'BalanceRequestAccounts');
            $index = $selected - 1;
            $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTBALANCE'));

            $request = "{\"OurBranchID\":\"004\",\"AccountID\":\"" . $Accounts[$index] . "\",\"ChargeBit\":0,\"MobileNo\":\"" . $this->msisdn . "\"}";
            $this->logErr($this->msisdn, "REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Accounts/GetAccountBalance", $request);
            $this->logErr($this->msisdn, "RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = $data['APIResponse'][0]['Response'];
            return (object) array('id' => 'withdrawmoneyConfirmComplete', 'action' => 'con', 'response' => $message . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'withdrawmoneyConfirmComplete')), 'type' => 'form');
        } catch (Exception $e) {
            return (object) array('id' => 'withdrawmoneyConfirmComplete', 'action' => 'con', 'response' => 'Your request has failed to process, please try again later' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'withdrawmoneyConfirmComplete')), 'type' => 'form');
        }
    }

    public function addmoneySavings() {
        return (object) array('id' => 'addmoneySavings', 'action' => 'con', 'response' => "Amount entered will be moved from your Mobile Money account to your Xpress Savings Account at 0% interest rate. " . PHP_EOL . "000. Cancel " . PHP_EOL . "2. Send" . PHP_EOL . PHP_EOL . '0.Home', 'map' => array((object) array('menu' => 'addmoneySavingsComplete')), 'type' => 'form');
    }

    public function addmoneySavingsComplete() {
        try {
            $request = "{\"OurBranchID\":\"004\",\"IDNumber\":\"\",\"PhoneNumber\":\"" . $this->msisdn . "\"}";
            $this->logErr($this->msisdn, "REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Accounts/GetClientByUniqueNumber", $request);
            $this->logErr($this->msisdn, "RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = "";
            $Accounts = Array();
            $message = $data['APIResponse'][0]['Response'];


            $fullResponse = explode('|', $message);

            $request2 = "{\"FromBranchID\":\"004\",\"FromAccountID\":\"" . $this->msisdn . "\",\"ToBranchID\":\"004\",\"ToAccountID\":\"" . $fullResponse[15] . "\",\"TrxAmount\":\"" . Cache::get($this->msisdn . 'addmoney') . "\",\"TransactionDescription\":\"Save cash from Wallet to MISA(Savings Account)\",\"TrxTraceNo\":\"" . $this->guid() . "\",\"MobileNo\":\"" . $this->msisdn . "\",\"ExtraData\":\"\"}";
            $this->logErr($this->msisdn, "REQUEST : " . $request2);
            $results2 = $this->zanacoRequestsPost("Accounts/GetAccountBalance", $request2);
            $this->logErr($this->msisdn, "RESULTS : " . $results2);
            $data2 = json_decode($results2, true);
            $message2 = $data2['APIResponse'][0]['Response'];



            return (object) array('id' => 'addmoneySavingsComplete', 'action' => 'con', 'response' => $message2 . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'addmoneySavingsComplete')), 'type' => 'form');
        } catch (Exception $e) {
            return (object) array('id' => 'addmoneySavingsComplete', 'action' => 'con', 'response' => 'Your request has failed to process, please try again later' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'addmoneySavingsComplete')), 'type' => 'form');
        }
    }

    public function BalanceRequest() {
        try {
            $selected = Cache::get($this->msisdn . 'BalanceRequestAccounts');
            $index = $selected - 1;
            $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTBALANCE'));

            $request = "{\"OurBranchID\":\"004\",\"AccountID\":\"" . $Accounts[$index] . "\",\"ChargeBit\":0,\"MobileNo\":\"" . $this->msisdn . "\"}";
            $this->logErr($this->msisdn, "REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Accounts/GetAccountBalance", $request);
            $this->logErr($this->msisdn, "RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = $data['APIResponse'][0]['Response'];
            return (object) array('id' => 'BalanceRequest', 'action' => 'con', 'response' => $message . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'BalanceRequest')), 'type' => 'form');
        } catch (Exception $e) {
            return (object) array('id' => 'BalanceRequest', 'action' => 'con', 'response' => 'Your request has failed to process, please try again later' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'BalanceRequest')), 'type' => 'form');
        }
    }

//FIXED DEPOSIT
    public function RequestFixedDepositConfirmProducts() {
        try {
            $request = "{\"OurBranchID\":\"004\"}";
            $this->logErr($this->msisdn, "PRODUCTS REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Products/GetAllProductList", $request);
            $this->logErr($this->msisdn, "PRODUCTS RESULTS : " . $results);
            $data = json_decode($results, true);
			
			
            $message = "";
            $productID = Array();
            $Term = Array();
            $Interest = Array();
            foreach ($data['GetProductList'] as $item) {
                if ($item['ProductID'] == 'FIDE') {
                    $message .= $item['ProductID'] . ' ' . $item['ProductTypeName'] . PHP_EOL;
                    $productID[] = $item['ProductID'];
                    $Term[] = $item['Term'];
                    $Interest[] = $item['Interest'];
                }
            }
            Cache::add($this->msisdn . 'LOANPRODUCTIDS', implode(',', $productID), 4);
            Cache::add($this->msisdn . 'LOANTERM', implode(',', $Term), 4);
            Cache::add($this->msisdn . 'LOANINTEREST', implode(',', $Interest), 4);
			
			
            return (object) array('id' => 'RequestFixedDepositConfirmProducts', 'action' => 'con', 'response' => $message . '' . PHP_EOL . '00.Back' . PHP_EOL . '0.Main Menu', 'map' => array((object) array('menu' => 'RequestFixedDepositConfirm')), 'type' => 'form');
        } catch (Exception $e) {
            return (object) array('id' => 'RequestFixedDepositConfirmProducts', 'action' => 'con', 'response' => 'Your request has failed to process, please try again later' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'RequestFixedDepositConfirmProducts')), 'type' => 'form');
        }
    }

    public function RequestFixedDepositConfirm() {
        $selected = Cache::get($this->msisdn . 'RequestFixedDepositConfirmProducts');
        $index = $selected - 1;
        $term = explode(',', Cache::get($this->msisdn . 'LOANTERM'));
        $interest = explode(',', Cache::get($this->msisdn . 'LOANINTEREST'));


        return (object) array('id' => 'RequestFixedDepositConfirm', 'action' => 'con', 'response' => '1.Request FD of K ' . Cache::get($this->msisdn . 'RequestFixedDepositAmount') . ' at  Interest ' . $interest[index] . ' % for ' . $term[index] . ' Months?' . PHP_EOL . PHP_EOL . '1.Proceed' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'RequestFixedDepositConfirmPin')), 'type' => 'form');
    }

    public function RequestFixedDepositRenewalModes() {
        try {
            $request = "{\"OurBranchID\":\"004\"}";
            $this->logErr($this->msisdn, "REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("BranchDetails/GetRenewalModeID ", $request);
            $this->logErr($this->msisdn, "RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = "Select renewal modes";
            $productID = Array();
            $Term = Array();
            $Interest = Array();
            foreach ($data['GetProductList'] as $item) {
                $message .= $item['Description'] . PHP_EOL;
                $productID = $item['RenewalModeID'];
            }
            Cache::add($this->msisdn . 'RENEWALMODES', implode(',', $productID), 4);
            return (object) array('id' => 'RequestFixedDepositRenewalModes', 'action' => 'con', 'response' => $message . '' . PHP_EOL . '00.Back' . PHP_EOL . '0.Main Menu', 'map' => array((object) array('menu' => 'RequestFixedDepositConfirmComplete')), 'type' => 'form');
        } catch (Exception $e) {
            return (object) array('id' => 'RequestFixedDepositRenewalModes', 'action' => 'con', 'response' => 'Your Balance is ZMW 1000,0000' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'RequestFixedDepositRenewalModes')), 'type' => 'form');
        }
    }

    public function RequestFixedDepositConfirmComplete() {
        try {
            $selected = Cache::get($this->msisdn . 'RequestFixedDepositConfirmProducts');
            $index = $selected - 1;
            $Accounts = explode(',', Cache::get($this->msisdn . 'LOANPRODUCTIDS'));
            $term = explode(',', Cache::get($this->msisdn . 'LOANTERM'));
            $request2 = "";


            $selected3 = Cache::get($this->msisdn . 'RequestFixedDepositRenewalModes');
            $index3 = $selected - 1;
            $Accounts3 = explode(',', Cache::get($this->msisdn . 'RENEWALMODES'));



            $request = "{\"OurBranchID\":\"004\",\"IDNumber\":\"\",\"PhoneNumber\":\"" . $this->msisdn . "\"}";
            $this->logErr($this->msisdn, "ACCOUNTS REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Accounts/GetClientByUniqueNumber", $request);
            $this->logErr($this->msisdn, "ACCOUNTS RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = "";
            $Accounts = Array();
            $fullResponse = Array();


            $message = $data['APIResponse'][0]['Response'];
            $fullResponse = explode('|', $message);
            if (Cache::get($this->msisdn . 'RequestFixedDeposit') == "1") {
                $request2 = "\"OperationalAccountID\": \"" . $fullResponse[15] . "\",   \"OurBranchID\": \“001\”,   \"MobileNo\": " . $this->msisdn . ",   \"IDNumber\": 0,  \"ProductID\": " . $Accounts[$index] . ",   \"FDAmount\": " . Cache::get($this->msisdn . 'RequestFixedDepositAmount') . ",   \"Term\": \"" . $term[index] . "\",   \"RenewalTerm\": \"" . $term[index] . "\", \"RenewalModeID\":\"" . $Accounts3[$index3] . "\"";
            } else {
                $request2 = "\"OperationalAccountID\": \"" . $this->msisdn . "\",   \"OurBranchID\": \“001\”,   \"MobileNumber\": " . $this->msisdn . ",   \"IDNumber\": 0,  \"ProductID\": " . $Accounts[$index] . ",   \"FDAmount\": " . Cache::get($this->msisdn . 'RequestFixedDepositAmount') . ",   \"Term\": \"" . $term[index] . "\",   \"RenewalTerm\": \"" . $term[index] . "\", \"RenewalModeID\":\"" . $Accounts3[$index3] . "\"";
            }



            $this->logErr($this->msisdn, "REQUEST FIXED DEPOSIT REQUEST : " . $request2);
            $results2 = $this->zanacoRequestsPost("Accounts/FDAccountOpeningAndInstructions ", $request2);
            $this->logErr($this->msisdn, "REQUEST FIXED DEPOSIT RESULTS : " . $results2);
            $data2 = json_decode($results2, true);
            $message2 = $data2['APIResponse'][0]['Response'];
            $response = (object) array('id' => 'RequestFixedDepositConfirmComplete', 'action' => 'con', 'response' => $message2 . "0.Back", 'map' => array((object) array('menu' => 'RequestFixedDepositConfirmComplete')), 'type' => 'form');
            return $response;
        } catch (Exception $e) {
            $response = (object) array('id' => 'RequestFixedDepositConfirmComplete', 'action' => 'end', 'response' => 'There was a problem processing your request, Please try again' . PHP_EOL . '0. Back', 'map' => array((object) array('menu' => 'RequestFixedDepositConfirmComplete')), 'type' => 'static');
            return $response;
        }
    }

//PRECLOSE FIXED DEPOSIT

    public function PrecloseFixedDeposit() {
        try {
            $request = "{\"OurBranchID\":\"004\",\"IDNumber\":0,\"PhoneNumber\":\"" . $this->msisdn . "\"}";
            $this->logErr($this->msisdn, "FD DETAILS REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Accounts/GetClientFDDetails", $request);
            $this->logErr($this->msisdn, "FD DETAILS RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = "Select FD account";
            $num = 1;
            $receiptID = Array();
            $aCCOUNTid = Array();


            $resultss = $data['APIResponse'][0]['Response'];

            $realRes = explode('|', $resultss);

            if ($realRes[1] == 01) {
                return (object) array('id' => 'PrecloseFixedDeposit', 'action' => 'con', 'response' => $realRes[3] . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'PrecloseFixedDepositConfirm')), 'type' => 'form');
            } else {
                $actualResponse = explode('~', $resultss);
                foreach ($actualResponse as $Account) {
                    if ($Account != "") {

                        if ($num == 1) {
                            $ActualRes = explode('|', $Account);
                            $receiptID = $ActualRes[7];
                            $aCCOUNTid = $ActualRes[5];
                            $message = $ActualRes[7] . ' ' . $ActualRes[9] . PHP_EOL;
                        } else {
                            $ActualRes = explode('|', $Account);
                            $receiptID = $ActualRes[3];
                            $aCCOUNTid = $ActualRes[1];
                            $message = $ActualRes[3] . ' ' . $ActualRes[5] . PHP_EOL;
                        }
                    }
                    $num = $num + 1;
                }

                Cache::add($this->msisdn . 'FDRECEIPT', implode(',', $receiptID), 4);
                Cache::add($this->msisdn . 'FDACCOUNTS', implode(',', $aCCOUNTid), 4);

                return (object) array('id' => 'PrecloseFixedDeposit', 'action' => 'con', 'response' => $message . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'PrecloseFixedDepositConfirm')), 'type' => 'form');
            }
        } catch (Exception $e) {
            return (object) array('id' => 'PrecloseFixedDeposit', 'action' => 'con', 'response' => 'There was a problem processing your request, Please try again later' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'PrecloseFixedDeposit')), 'type' => 'form');
        }
    }

    public function PrecloseFixedDepositConfirm() {
        return (object) array('id' => 'PrecloseFixedDepositConfirm', 'action' => 'con', 'response' => 'You are closing your fixed deposit account' . PHP_EOL . '1.Proceed' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'PrecloseFixedDepositPin')), 'type' => 'form');
    }

    public function PrecloseFixedDepositPin() {
        return (object) array('id' => 'PrecloseFixedDepositPin', 'action' => 'con', 'response' => 'Enter mobile money PIN' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'PrecloseFixedDepositPinComplete')), 'type' => 'form');
    }

    public function PrecloseFixedDepositPinComplete() {
        try {
            $selected = Cache::get($this->msisdn . 'PrecloseFixedDeposit');
            $index = $selected - 1;

            $Receipt = explode(',', Cache::get($this->msisdn . 'FDRECEIPT'));
            $Accounts = explode(',', Cache::get($this->msisdn . 'FDACCOUNTS'));



            $request = "{\"OurBranchID\":\"004\",\"AccountID\":\"" . $Accounts[$index] . "\",\"ReceiptID\":\"" . $Receipt[$index] . "\",,\"DepositSeries\":\"1\"}";
            $this->logErr($this->msisdn, "FD CLOSE REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Transactions/FDCloseDetailst", $request);
            $this->logErr($this->msisdn, "FD CLOSE RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = $data['APIResponse'][0]['Response'];
            return (object) array('id' => 'PrecloseFixedDepositPinComplete', 'action' => 'con', 'response' => $message . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'PrecloseFixedDepositPinComplete')), 'type' => 'form');
        } catch (Exception $e) {
            return (object) array('id' => 'PrecloseFixedDepositPinComplete', 'action' => 'con', 'response' => 'There was a problem processing your request, Please try again later' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'PrecloseFixedDepositPinComplete')), 'type' => 'form');
        }
    }

    public function GetCustomerLoanLimit() {
        try {
            $request = "{\"OurBranchID\":\"004\",\"IDNumber\":0,\"MobileNumber\":\"" . $this->msisdn . "\"}";
            $this->logErr($this->msisdn, "LOAN LIMITS REQUEST : " . $request);
            $results = $this->zanacoRequestsPost("Accounts/GetClientLoanLimit", $request);
            $this->logErr($this->msisdn, "LOAN LIMITS RESULTS : " . $results);
            $data = json_decode($results, true);
            $message = $data['APIResponse'][0]['Response'];
            $realRes = explode('|', $message);
            return (object) array('id' => 'GetCustomerLoanLimit', 'action' => 'con', 'response' =>'Client limit ' . $realRes[4] . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'GetCustomerLoanLimit')), 'type' => 'form');
        } catch (Exception $e) {
            return (object) array('id' => 'GetCustomerLoanLimit', 'action' => 'con', 'response' => 'There was a problem processing your request, Please try again later' . PHP_EOL . PHP_EOL . '0.Home' . PHP_EOL . '000.Cancel', 'map' => array((object) array('menu' => 'GetCustomerLoanLimit')), 'type' => 'form');
        }
    }

    public function zanacoRequestsPost($url, $request) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://172.17.20.34:26000/api/api/' . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $headers = array();
        $headers[] = 'Authorization:Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1bmlxdWVfbmFtZSI6IkNIQVJMRVMiLCJuYmYiOjE1Nzk5NTcxMTIsImV4cCI6MTczNzgwOTkxMiwiaWF0IjoxNTc5OTU3MTEyfQ.VK7e-cmNGBL2y4fQm3iFx-2C45-05YbVEie1DZNNdVE';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $results = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return $results;
    }

    public function getToken() {
        $ch = curl_init();
        $requestS = "{\"ConsumerKey\":\"004\",\"ConsumerSecret\":\"\"}";
        $this->logErr($this->msisdn, "TOKEN REQUEST : " . $requestS);
        curl_setopt($ch, CURLOPT_URL, 'http://172.17.20.34:26000/api/A_RequestConnection ');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestS);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $results = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        $this->logErr($this->msisdn, "TOKEN RESULTS : " . $results);
        $data = json_decode($results, true);
        $message = "";
        foreach ($data['APIResponse'] as $item) {
            $message = $item['token'];
        }
        return $message;
    }

    public function ElmaU($DataToSend) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://172.17.20.34:23000/MobileMallUSSD/MobileMall.asmx/U?b=' . urlencode($DataToSend));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function GetCustomer($msisdn, $shortcode) {
        $DataToSend = 'FORMID:GETCUSTOMER:MOBILENUMBER:' . $msisdn . ':SHORTCODE:' . $shortcode . ':COUNTRY:' . $this->Country . ':DEVICEID:' . $msisdn . $shortcode . ':UNIQUEID:' . $this->guid() . ':';
        $this->logErr($this->msisdn, "GET CUSTOMER REQUEST :" . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $this->logErr($this->msisdn, "GET CUSTOMER RESPONSE : " . strip_tags($ElmaResponse));
        $ElmaResponse = $ElmaResponse;
//dd($ElmaResponse);
        $responseData = explode(':', strip_tags($ElmaResponse));

        try {
            if ($responseData[1] == "000" || $responseData[1] == "101") {
                Cache::add($this->msisdn . 'FIRSTNAME', $responseData[7], 2);
                Cache::add($this->msisdn . 'LASTNAME', $responseData[9], 2);
                if (Cache::has($this->msisdn . 'CUSTOMERID')) {
                    Cache::forget($this->msisdn . 'CUSTOMERID');
                }
                Cache::add($this->msisdn . 'CUSTOMERID', $responseData[3], 3);
                Cache::add($this->msisdn . 'BANKNAME', $responseData[11], 2);
            }

            return $ElmaResponse;
        } catch (\Exception $ex) {
            return "Error";
//return (object) array('action' => 'end', 'response' => 'Enter your Account number:'.PHP_EOL .'00.Back'.PHP_EOL .'0.Main Menu', 'map' => array( (object) array('menu' => 'ServiceDown')), 'type' => 'static');
        }
    }

    public function pinReset($answer) {
        $BankAccountID = Cache::get($this->msisdn . 'ACCOUNTS');
//$Answer = Cache::get($this->msisdn.'GetCustomerSecurityQuestion');
        $Answer = $answer;
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $DataToSend = 'FORMID:RESETPIN2:BANKID:' . $this->DefaultBankID . ':ANSWER:' . $Answer . ':SHORTCODE:' . $this->shortcode . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':COUNTRY:' . $this->Country . ':UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
        $this->logErr($this->msisdn, "PIN RESET REQUEST : " . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $responseData = explode(':', strip_tags($ElmaResponse));
        $this->logErr($this->msisdn, "PIN RESET RESPONSE : " . strip_tags($ElmaResponse));

        Cache::forget($this->msisdn . 'GetCustomerSecurityQuestion');
        $response = "";
        if ($responseData[1] == "000") {
            $FirstName = Cache::get($this->msisdn . 'FIRSTNAME');
            $message = 'Hello, ' . strtoupper($FirstName) . ' you have successfully reset your PIN. You will receive a new PIN shortly via SMS.';
            $response = (object) array('id' => 'pinReset', 'action' => 'end', 'response' => $message, 'map' => array((object) array('menu' => 'pinReset')), 'type' => 'static');
        } else {
            $response = (object) array('id' => 'pinReset', 'action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'pinReset')), 'type' => 'static');
        }

        return $response->action . " " . $response->response;
    }

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++



    public function Pin($msisdn, $shortcode, $pin) {
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $DataToSend = 'FORMID:LOGIN:CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':BANKID:' . $this->DefaultBankID . ':BANKNAME:' . $this->BankName . ':SHORTCODE:' . $this->shortcode . ':COUNTRY:' . $this->Country . ':DEVICEID:' . $msisdn . $shortcode . ':LOGINMPIN:' . $pin . ':UNIQUEID:' . $this->guid() . ':';
//$this->Logger($msisdn, $DataToSend);
        $this->logErr($this->msisdn, "LOGIN REQUEST : " . strip_tags($DataToSend));
        $ElmaResponse = $this->ElmaU($DataToSend);
        $this->logErr($this->msisdn, "LOGIN RESPONSE :" . strip_tags($ElmaResponse));
        $responseData = explode(':', strip_tags($ElmaResponse));

        if ($responseData[1] == "000" || $responseData[1] == "101" || $responseData[1] == "102") {
            if (Cache::has($this->msisdn . 'ACCOUNTS')) {
                Cache::forget($this->msisdn . 'ACCOUNTS');
            }
            $AccountsRaw = explode(":", $responseData[3]);
            $Accounts = array();
            foreach ($AccountsRaw as $Account) {
                if ($Account != "") {
                    $Accounts[] = $Account;
                }
            }
            $this->logErr($this->msisdn, "LOGIN ACCOUNTS :" . implode(":", $Accounts));
            Cache::add($this->msisdn . 'ACCOUNTS', implode(":", $Accounts), 3);
        }

        return $responseData[1];
    }

    public function LoanProducts() {
        $BankAccountID = Cache::get($this->msisdn . 'ACCOUNTS');
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $LoanAmount = Cache::get($this->msisdn . 'loansamt');
        $LoanLimit = Cache::get($this->msisdn . 'LOANLIMIT');
        $Assessment = Cache::get($this->msisdn . 'ASSESSMENT');
        $accept = Cache::has($this->msisdn . 'LoanProducts') ? Cache::get($this->msisdn . 'LoanProducts') : "";

        if ($accept == "") {
            /* $DataToSend = 'FORMID:M-:MERCHANTID:INDIVIDUALACTIVITY:INFOFIELD1:INDIVIDUALACTIVITY:INFOFIELD2:SAFARICOM:INFOFIELD3:VALIDATEAMOUNT:INFOFIELD4:LOANREQUEST:INFOFIELD5:'. $LoanAmount .':INFOFIELD9:'. $this->msisdn .':METERNUMBER:'. $BankAccountID .':_WEBSERVICE_CALL_:MOBILELENDING:BANK:BARCLAYS:CUSTOMERID:'. $CustomerID .':ACCOUNTID:'.$BankAccountID.':BANKID:'. $this->DefaultBankID .':ACTION:GETNAME:COUNTRY:'. $this->Country .':';
              $this->logErr($this->msisdn, "LOAN PRODUCTS  REQUEST : ". $DataToSend );
              $ElmaResponse = $this->ElmaU($DataToSend);
              $responseData = explode(':', strip_tags($ElmaResponse));
              $this->logErr($this->msisdn, "LOAN PRODUCTS  RESPONSE : ". strip_tags($ElmaResponse)); */

//$response = "";
//if($responseData[1] == "000"){
            $response = (object) array('id' => 'LoanProducts', 'action' => 'con', 'response' => "Confirm loan application of'.PHP_EOL .'Kshs. " . $LoanAmount . " for  30days?'.PHP_EOL .'Applicable interest of 1.083%'.PHP_EOL .'and facility fee of 5 percent.'.PHP_EOL .'Reply with:'.PHP_EOL .'1. Accept'.PHP_EOL .'2. Cancel", 'map' => array((object) array('menu' => 'LoanProducts')), 'type' => 'form');
            /* }else{
              $response = (object) array('id' => 'LoanProducts', 'action' => 'end', 'response' => $responseData[3], 'map' => array( (object) array('menu' => 'LoanProducts')), 'type' => 'static');
              } */

            return $response;
        } else if ($accept == "1") {
            $DataToSend = 'FORMID:M-:MERCHANTID:INDIVIDUALACTIVITY:INFOFIELD1:INDIVIDUALACTIVITY:INFOFIELD2:SAFARICOM:INFOFIELD3:LOAN:INFOFIELD4:' . $LoanLimit . ':INFOFIELD5:1:INFOFIELD6:' . $LoanAmount . ':INFOFIELD9:' . $this->msisdn . ':INFOFIELD7:' . $Assessment . ':INFOFIELD8:New:AMOUNT:' . $LoanAmount . ':METERNUMBER::_WEBSERVICE_CALL_:MOBILELENDING:BANK:BARCLAYS:CUSTOMERID:' . $CustomerID . ':ACCOUNTID:' . $BankAccountID . ':BANKID:' . $this->DefaultBankID . ':ACTION:GETNAME:COUNTRY:' . $this->Country . ':';
            $ElmaResponse = $this->ElmaU($DataToSend);
            $responseData = explode(':', $ElmaResponse);
            $this->logErr($this->msisdn, "GET LOAN  REQUEST : " . $DataToSend);

            $response = (object) array('id' => 'LoanProducts', 'action' => 'end', 'response' => 'Your Loan Request has been submitted.' . PHP_EOL . 'We will Notify you shortly.' . PHP_EOL . '0.Main Menu', 'map' => array((object) array('menu' => 'LoanProducts')), 'type' => 'static');

            return $response;
        }
    }

    public function Logger($msisdn, $log) {
        $today = Carbon::parse(Carbon::today('Africa/Nairobi'))->format('Y-m-d');
        $myfile = fopen("/var/log/Ugafode2/" . $today . "/" . $msisdn . ".log", "w") or die("Unable to open file!");
        $txt = $log . "'.PHP_EOL .'";
        fwrite($myfile, $txt);
        fclose($myfile);
    }

    function logErr($msisdn, $log) {
        $today = Carbon::parse(Carbon::today('Africa/Nairobi'))->format('Y-m-d');
        $time = Carbon::now('Africa/Nairobi');
        $log_filename = $_SERVER['DOCUMENT_ROOT'] . "/log/ZANACO/" . $today;
//$log_filename = "/home/MobileMall/ussdLogs/UGANDATEST/HFB/".$today;
        if (!file_exists($log_filename)) {
// create directory/folder uploads.
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/' . $msisdn . '.log';
        file_put_contents($log_file_data, $time . " - " . $log . "'.PHP_EOL .'", FILE_APPEND);
    }

    function logReg($msisdn, $log) {
        $today = Carbon::parse(Carbon::today('Africa/Nairobi'))->format('Y-m-d');
        $time = Carbon::now('Africa/Nairobi');
        $log_filename = $_SERVER['DOCUMENT_ROOT'] . "/log/Barclays/Registration/" . $today;
        if (!file_exists($log_filename)) {
// create directory/folder uploads.
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/' . $msisdn . '.log';
        file_put_contents($log_file_data, $time . " - " . $log . "'.PHP_EOL .'", FILE_APPEND);
    }

    public function ZanacoRegistrationValidation() {
        $postData = "{meterDetails:{'UniqueID':'" . $this->guid() . "','MobileNumber':'" . $this->msisdn . "','BankID':'03','CustomerID':'25400003','Request':'','Response':'','StanNumber':'0','ConnectionString':'','ServiceName':'MOBILELENDING','FunctionName':'GETNAME','MeterNumber':'','Country':'KENYA','ExternalRequest':'YES','InfoField1':'REGISTRATION','InfoField2':'SAFARICOM','InfoField3':'VALIDATE','InfoField4':'USSD','InfoField5':'','InfoField6':'','InfoField7':'','InfoField8':'','InfoField9':'" . $this->msisdn . "','InfoField10':'','TrxSource':'USSD','MerchantReference':'" . time() . "','Amount':'0','CustomerFullName':'Barclays BANK','StoredProcedureName':''}}";
        $headers = array(
            'Content-Type:application/json',
            'Authorization: Basic ' . base64_encode("UserID:PassWord") // <---
        );

        $this->logReg($this->msisdn, "REGISTRATION VALIDATION REQUEST : " . $postData);

        $url = "http://172.17.20.25:51102/MobileLendingBBK/MobileLending.asmx/Barclays";

        $ch = curl_init();
//set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, $postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//execute post
        $result = curl_exec($ch);
//close connection
        curl_close($ch);

        $this->logReg($this->msisdn, "REGISTRATION VALIDATION REPONSE : " . $result);

        $res = explode(":", $result);

        if ($res[1] == "091") {
            $response = (object) array('id' => 'ZanacoRegistrationValidation', 'action' => 'con', 'response' => "Welcome to Timiza!'.PHP_EOL .'Please enter your ID Number to register:'.PHP_EOL .''.PHP_EOL .'I accept Timiza Product T&Cs visit www.barclays.co.ke- Product Terms and conditions section'.PHP_EOL .'000. Exit", 'map' => array((object) array('menu' => 'TimizaRegistrationConfirm')), 'type' => 'form');
        } else {
            $response = (object) array('id' => 'ZanacoRegistrationValidation', 'action' => 'end', 'response' => $res[3], 'map' => array((object) array('menu' => 'default')), 'type' => 'static');
        }

        return $response;
    }

    public function TimizaRegistrationConfirm() {
        $IDNumber = Cache::get($this->msisdn . 'ZanacoRegistrationValidation');
        $postData = "{meterDetails:{'UniqueID':'" . $this->guid() . "','MobileNumber':'" . $this->msisdn . "','BankID':'03','CustomerID':'25400003','Request':'','Response':'','StanNumber':'0','ConnectionString':'','ServiceName':'MOBILELENDING','FunctionName':'GETNAME','MeterNumber':'','Country':'KENYA','ExternalRequest':'YES','InfoField1':'REGISTRATION','InfoField2':'SAFARICOM','InfoField3':'NEW','InfoField4':'USSD','InfoField5':'" . $IDNumber . "','InfoField6':'','InfoField7':'','InfoField8':'','InfoField9':'" . $this->msisdn . "','InfoField10':'','TrxSource':'USSD','MerchantReference':'" . time() . "','Amount':'0','CustomerFullName':'Barclays BANK','StoredProcedureName':''}}";
        $headers = array(
            'Content-Type:application/json',
            'Authorization: Basic ' . base64_encode("UserID:PassWord") // <---
        );

        $this->logReg($this->msisdn, "REGISTRATION REQUEST : " . $postData);

        $url = "http://172.17.20.25:51102/MobileLendingBBK/MobileLending.asmx/Barclays";

        $ch = curl_init();
//set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, $postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//execute post
        $result = curl_exec($ch);
//close connection
        curl_close($ch);

        $this->logReg($this->msisdn, "REGISTRATION RESPONSE : " . $result);

        $res = explode(":", $result);

        if ($res[1] == "") {
            $response = (object) array('id' => 'TimizaRegistrationConfirm', 'action' => 'end', 'response' => $res[3], 'map' => array((object) array('menu' => 'default')), 'type' => 'static');
        } else {
            $response = (object) array('id' => 'TimizaRegistrationConfirm', 'action' => 'end', 'response' => $res[3], 'map' => array((object) array('menu' => 'default')), 'type' => 'static');
        }
        return $response;
    }

    public function ServiceDown() {
        return $response = (object) array('action' => 'end', 'response' => 'Service is currently unavailable please try again later ' . PHP_EOL . '00. Back ' . PHP_EOL . '000. Logout', 'map' => array((object) array('menu' => 'ServiceDown')), 'type' => 'static');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
//
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
//
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
//
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
//
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
//
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
//
    }

}
