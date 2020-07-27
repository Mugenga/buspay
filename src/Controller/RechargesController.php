<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class RechargesController extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->RequestHandler->renderAs($this, 'json');
    }
    
    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->RequestHandler->ext = 'json';
    }

    public function index(){
        $request = file_get_contents('php://input');
        if (empty($request) == false) {
            //TO DO: verify if user does n't exist

            //Register 
            $data = json_decode($request);
            $response_array = $this->Recharges->handleRecharge($data);

        } else {
            $response_array['status_code'] = 101;
            $response_array['response_msg'] = 'Invalid Request.';
        }
       
        return $this->json($response_array);
    }

}
