<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class ClientsController  extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadModel('Clients');
        $this->RequestHandler->renderAs($this, 'json');
    }
    
    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->RequestHandler->ext = 'json';
    }

 
    public function register() 
    {
        $request = file_get_contents('php://input');
        if (empty($request) == false) {
            //TO DO: verify if user does n't exist

            //Register 
            $data = json_decode($request);
            $response_array = $this->Clients->handleRegistration($data);

        } else {
            $response_array['status_code'] = 101;
            $response_array['response_msg'] = 'Invalid Request.';
        }
       
        return $this->json($response_array);
    }

    public function momoPaymnet()
    {
        $request = file_get_contents('php://input');
        if (empty($request) == false) {
            //Create Transaction and mark it as NEW in Transactions
            $data = json_decode($request);
            $response_array = $this->Transactions->createTransaction($data);

            //TO DO: Call Mobile Money API

            //UPDATE Transactions to mark PENDING 

            //UPDATE Transaction to mark COMPLETED After successfully MOMO PAYMENT

            //UPDATE Client payments

            //UPDATE Client balance

            //UPDATE Transaction as closed

        } else {
            $response_array['status_code'] = 101;
            $response_array['response_msg'] = 'Invalid Request.';
        }
       
        return $this->json($response_array);
    }

    public function recharge() 
    {

    }

    public function view()
    {
        $request = file_get_contents('php://input');
        if (empty($request) == false) {
            //TO DO: verify if user does n't exist

            //Register 
            $data = json_decode($request);
            $response_array = $this->Clients->GetClientData($data);

        } else {
            $response_array['status_code'] = 101;
            $response_array['response_msg'] = 'Invalid Request.';
        }
       
        return $this->json($response_array);
    }

}
