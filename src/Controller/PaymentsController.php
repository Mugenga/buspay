<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class PaymentsController extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->RequestHandler->renderAs($this, 'json');
    }

    public function create()
    {
        $request = file_get_contents('php://input');
        if (empty($request) == false) {
            //TO DO: verify if user does n't exist

            //Register 
            $data = json_decode($request);
            $response_array = $this->Payments->handlePayment($data);

        } else {
            $response_array['status_code'] = 101;
            $response_array['response_msg'] = 'Invalid Request.';
        }
       
        return $this->json($response_array);
    }

    public function PaytComplete() {
        $request = file_get_contents('php://input');
        if (empty($request) == false) {
            $data = json_decode($request, true);
            $resp_array = $this->Payments->PaymentCompletedAlertHandler($data);
        } else {
            $resp_array['status_code'] = 999;
            $resp_array['response_msg'] = 'Invalid Request.';
        }
        
        return $this->json($resp_array);
    }


    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->RequestHandler->ext = 'json';
    }

}
