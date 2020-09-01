<?php

namespace App\UssdTraits;

trait Menus {

    public function menu() {
        return '{"menu":[{"id":"root","response":"...",
		   "map":[{"key":"next","menu":"home"}],
		   "type":"static",
		   "action":"con"
		  },{"id":"home",
		   "response":"1-Loans\n2-Xpress savings\n3-Fixed Deposits\n000-Exit",
		   "map":[{"key":"1","menu":"loanss"},{"key":"2","menu":"savings"},{"key":"3","menu":"deposits"}],
		   "type":"dynamic",
		   "action":"con"
          },{"id":"loanss",
		   "response":"Reply with\n1. Request Loan\n2. Loan Balance\n3. Ministatement\n4. Loan Limit\n5. Info\n6. Terms And Conditions\n0. Home",
		   "map":[{"key":"1","menu":"requestLoan"},{"key":"2","menu":"BalanceRequestAccounts"},{"key":"3","menu":"MinistatementRequestAccounts"},{"key":"4","menu":"GetCustomerLoanLimit"},{"key":"5","menu":"info"},{"key":"6","menu":"tandc"}],
		   "type":"dynamic",
		   "action":"con"
		  },{"id":"deposits",
		   "response":"Reply with:\n1. Request Fixed Deposit\n2. Preclose Fixed Deposit\n3. Info\n4. Terms And Conditions\n0. Home",
		   "map":[{"key":"1","menu":"loanRepayAmount"},{"key":"2","menu":"loanRepayAmount"},{"key":"3","menu":"info"},{"key":"4","menu":"tandc"}],
		   "type":"dynamic",
		   "action":"con"
          },{"id":"savings",
			"response":"Reply with:\n1. Add Money\n2. Withdraw\n3. Savings Balance\n4. Savings Ministatement\n5. Info\n6. Terms And Conditions\n0. Home",
		   "map":[{"key":"1","menu":"addmoney"},{"key":"2","menu":"withdrawmoney"},{"key":"3","menu":"BalanceRequestAccounts"},{"key":"4","menu":"MinistatementRequestAccounts"},{"key":"5","menu":"info"},{"key":"6","menu":"tandc"}],
		   "type":"dynamic",
		   "action":"con"
          },{"id":"repayLoan",
		   "response":"Reply with:\n1-From Micro Savings\n2-From MTN Mobile Money\n0. Home",
		   "map":[{"key":"1","menu":"loanRepayAmount"},{"key":"2","menu":"loanRepayAmount"}],
		   "type":"dynamic",
		   "action":"con"
          },{"id":"RequestFixedDeposit",
		   "response":"Reply with:\n1-From Micro Savings\n2-From MTN Mobile Money\n0. Home",
		   "map":[{"key":"1","menu":"RequestFixedDepositAmount"},{"key":"2","menu":"RequestFixedDepositAmount"}],
		   "type":"dynamic",
		   "action":"con"
          },{"id":"RequestFixedDepositAmount",
		   "response":"Enter amount",
		   "map":[{"key":"1","menu":"RequestFixedDepositConfirmProducts"}],
		   "type":"form",
		   "link":"",
		   "action":"con"
		  },{"id":"Ministatement",
		   "response":"Reply with:\n1-Micro loan\n2-Micro Savings\n3-Fixed Deposit\n0. Home",
		   "map":[{"key":"1","menu":"MinistatementPin"},{"key":"2","menu":"MinistatementPin"},{"key":"2","menu":"MinistatementPin"}],
		   "type":"dynamic",
		   "action":"con"
          },{"id":"addmoney",
		   "response":"How much do you want to save today? Enter your amount\n000. Cancel\n2. Send\n0. Home",
		   "map":[{"key":"1","menu":"cancel"},{"key":"1","menu":"addmoneySavings"}],
		   "type":"form",
		   "action":"con"
          },{"id":"tandc",
		   "response":"Welcome to Xpress Savings by Zanaco, to view our T & Cs please visit www.zanaco.co.zm.\n0. Home",
		   "map":[{"key":"1","menu":"tandc"}],
		   "type":"form",
		   "action":"con"
          },{"id":"cancel",
		   "response":"Transaction cancelled.\n0. Home",
		   "map":[{"key":"1","menu":"cancel"}],
		   "type":"static",
		   "action":"con"
          },{"id":"info",
		   "response":"Welcome to Xpress Savings by Zanaco, for more information go to www.zanaco.co.zm.\n0. Home",
		   "map":[{"key":"1","menu":"info"}],
		   "type":"form",
		   "action":"con"
          },{"id":"Balance",
		   "response":"Reply with:\n1-Micro loan\n2-Micro Savings\n3-Fixed Deposit\n0. Home",
		   "map":[{"key":"1","menu":"BalancePin"},{"key":"2","menu":"BalancePin"},{"key":"2","menu":"BalancePin"}],
		   "type":"dynamic",
		   "action":"con"
          },{"id":"MinistatementPin",
		   "response":"Enter mobile money pin",
		   "map":[{"key":"1","menu":"MinistatementRequest"}],
		   "type":"form",
		   "link":"",
		   "action":"con"
		  },{"id":"RequestFixedDepositConfirmPin",
		   "response":"Enter mobile money pin",
		   "map":[{"key":"1","menu":"RequestFixedDepositRenewalModes"}],
		   "type":"form",
		   "link":"",
		   "action":"con"
		  },{"id":"BalancePin",
		   "response":"Enter mobile money pin",
		   "map":[{"key":"1","menu":"BalanceRequest"}],
		   "type":"form",
		   "link":"",
		   "action":"con"
		  },{"id":"loanRepayAmount",
		   "response":"Enter Amount to pay",
		   "map":[{"key":"1","menu":"loanRepayAmountConfirm"}],
		   "type":"form",
		   "link":"",
		   "action":"con"
		  },{"id":"newAccount",
		   "response":"Please visit the banks you want to connect",
		   "map":[],
		   "type":"static",
		   "link":"",
		   "action":"con"
		  },{"id":"pinend",
		   "response":"Your PIN has been changed successfully. Please exit and login again with the new PIN.",
		   "map":[{"key":"next","menu":"home"}],
		   "type":"static",
		   "link":"",
		   "action":"end"
		  },{"id":"pinenderror",
		   "response":"Pin change encountered an error. Please try again later.",
		   "map":[{"key":"next","menu":"home"}],
		   "type":"static",
		   "link":"",
		   "action":"end"
		  },{"id":"cancel",
		   "response":"Transaction canceled, Reply with 0 for home menu",
		   "map":[{"key":"next","menu":"home"}],
		   "type":"static",
		   "link":"",
		   "action":"con"
		  }]}';
    }

}
