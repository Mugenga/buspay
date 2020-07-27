<?php

namespace App\Controller;

use App\Controller\AppController;

class RequestsController extends AppController {

    public function initialize() {
        parent::initialize();
    }

    public function Index() {
        $request = file_get_contents('php://input');
        if (empty($request) == false) {
            $data = json_decode($request, true);
            $verifynumber = $this->Requests->VerifyMSISDN($data['phone_number']);
            if ($verifynumber['flag']) {
                $resp_array = $this->Requests->{$data['request_type']}($data);
            } else {
                $resp_array['status_code'] = 701;
                $resp_array['response_msg'] = 'You are not registered for this service.';
            }
        } else {
            $resp_array['status_code'] = 999;
            $resp_array['response_msg'] = 'Invalid Request.';
        }
        
        $response = $this->ProcessResponse($resp_array['status_code'], $data['phone_number']);

        return $this->json($response);
    }

    function ApproveCharge($msisdn = false, $sessionid = false) {
        if (empty($msisdn) == false || empty($sessionid)) {
            $resp_array['status_code'] = 700;
            $resp_array['response_msg'] = 'success';
        } else {
            $resp_array['status_code'] = 999;
            $resp_array['response_msg'] = 'Invalid Request';
        }

        return $this->json($resp_array);
    }

    public function Register() {
        $request = file_get_contents('php://input');
        if (empty($request) == false) {
            $data = json_decode($request, true);
            $returnval = $this->Requests->CreateNewReqest($data);
            $data['request_id'] = $returnval['request_id'];
            $resp_array = $this->Requests->HandleNewRegistration($data);
        } else {
            $resp_array['status_code'] = 999;
            $resp_array['response_msg'] = 'Invalid Request';
        }

        $response = $this->ProcessResponse($resp_array['status_code'], $data['telephone_number']);

        return $this->json($response);
    }
    
    public function Retry($id){
        $this->Requests->RetryRequest($id);
    }
            
    function ProcessResponse($respcode, $msisdn){
        //Get the Message
        $resparray = $this->Requests->ProcessResponse($respcode, $msisdn);
        
        return $resparray;
    }
    
}
