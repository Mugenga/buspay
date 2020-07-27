<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class TransactionsController  extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadModel('Transactions');
        $this->RequestHandler->renderAs($this, 'json');
    }
    
    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->RequestHandler->ext = 'json';
    }

    public function momoPayment()
    {
        $request = file_get_contents('php://input');
        if (empty($request) == false) {
            //Create Transaction and mark it as NEW in Transactions
            $data = json_decode($request);
            $response_array = $this->Transactions->createTransaction($data);

            //TO DO: Call Mobile Money API

            //UPDATE Transactions to mark PENDING 
            $response_array = $this->Transactions->updateTransactionToPending($response_array['transaction_id']);
            //UPDATE Transaction to mark COMPLETED After successfully MOMO PAYMENT
            $response_array = $this->Transactions->updateTransactionToCompleted($response_array['transaction_id']);
            //UPDATE Client payments
            
            //UPDATE Client balance

            //UPDATE Transaction as closed

        } else {
            $response_array['status_code'] = 101;
            $response_array['response_msg'] = 'Invalid Request.';
        }
       
        return $this->json($response_array);
    }

    function transactionStatus() 
    {

    }

    public function recharge() 
    {

    }

}
