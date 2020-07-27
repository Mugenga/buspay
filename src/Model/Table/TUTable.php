<?php

class CRBRequest {

    function __construct() {
        $this->stan = new Standardizer();
        $this->logs = new Logs();
        $this->model = new Model();
        $options = array(
            'login' => SVC_LOGIN,
            'password' => SVC_PASSD,
            'trace' => 1
        );
        $todayDate = date("ymdhis");
        $this->transID = $todayDate . rand();
        $this->client = new SoapClient(SVC_URL, $options);
    }

    function RequestHandler($trans, $trans_id = false, $trans_array = false) {

        // print_r($trans_array);die();
        $response = '';
        $this->logs->ExeLog($trans, 'CRBRequest::RequestHandler Function Call With Data Set ', 2);
        try {
            if (strtolower($trans['requesttype']) == 'verifymobileuser') {
                $response = $this->VerifyMobileUser($trans);
            }if (strtolower($trans['requesttype']) == 'registermember') {
                $response = $this->RegisterMember($trans);
            } else {
                $regstatus = $this->CheckMemberRegistration($trans);
                if ($regstatus == 200) {

                    $response = $this->{trim($trans['requesttype'])}($trans, $trans_id, $trans_array);
                } else if ($regstatus == 202) {
                    $response['phone'] = $trans['phone'];
                    $response['resp_code'] = 124;
                } else if ($regstatus == 211) {

                    $response['phone'] = $trans['phone'];
                    $response['resp_code'] = 116;
                } else {
                    $response['phone'] = $trans['phone'];
                    $response['resp_code'] = 117;
                }
            }
        } catch (Exception $e) {

            $this->logs->ExeLog($trans, 'CRBRequest::RequestHandler Exception message ' . var_export($e, true), 2);
            $response['phone'] = $trans['phone'];
            $response['resp_code'] = 117;
        }

        $this->logs->ExeLog($trans, 'CRBRequest::RequestHandler response message ' . var_export($response, true), 2);
        $response_xml = $this->CreateResponseXML($response);

        return $response_xml;
    }

    function CheckMemberRegistration($data) {//finished 200=success
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'message' => 'User Verification',
            'phoneNumber' => $data['phone'],
            'reportSector' => '2',
            'reportReason' => '4',
        );
        $response = $this->client->getProduct401($request);
        $response = json_decode(json_encode($response), true);
        $this->logs->ExeLog($data, 'CRBRequest::CheckMemberRegistration Function Call With Data Set ' . var_export($response, true), 2);

        return $response['return']['responseCode'];
    }

    function RegisterMember($data) {//finished
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'documentNumber' => $data['documentid'],
            'names' => $data['surname'] . " " . $data['othernames'],
            'phoneNumber' => $data['phone'],
            'reportSector' => '2',
            'reportReason' => '4',
        );

        $response = $this->client->getProduct402($request);
//  $this->logs->ExeLog($data,'CRBRequest::RegisterMember Last Request sent ' . var_export($this->client->__getLastRequest() , true), 2);	
        $response = json_decode(json_encode($response), true);
        $this->logs->ExeLog($data, 'CRBRequest::RegisterMember Function Call With Data Set ' . var_export($response, true), 2);

        //print_r($response);die();
        $response_msg = '';
        $response_msg['phone'] = $data['phone'];
        if ($response['return']['responseCode'] == 200) {
            $response_msg['resp_code'] = 111;
            $response_msg['firstname'] = $data['othernames'];
            $response_msg['lastname'] = $data['surname'];
        } else if ($response['return']['responseCode'] == 210) {
            $response_msg['resp_code'] = 125;
        } else {
            $response_msg['resp_code'] = 102;
        }
        
        return $response_msg;
    }


    function ThirdPartyPayment($data) {
//print_r($data);die();	
        $response_msg['phone'] = $data['phone'];
        $response_msg['resp_code'] = 120;
        $response = $this->CreatePaymentResponseXML($response_msg);
        ob_start();
        echo $response;
        header('Connection: close');
        header('Content-Length: ' . ob_get_length());
        ob_end_flush();
        ob_flush();
        flush();
        sleep(15);
        $trans_id = $this->model->CreateTransactionRecord($data);
        $request = $this->ProcessPaymentRequest($data, $trans_id);
        $trans_resp_array = $this->HandlePaymentResponse($data, $trans_id, $request);
        return $trans_resp_array;
    }

    function ProcessPaymentRequest($data, $trans_id) {


        $trans_data = $this->model->GetTransaction($trans_id);
        $trans_data = $trans_data[0];
        $trans_data['reason'] = 'CRB';
        $f_template = 'merchantpaymentreq';
        $trans_data['vendor'] = $this->model->getVendorName($trans_data['phone_number']);
        sleep(20);
        $trans_xml = $this->model->WriteGeneralXML($trans_data, $f_template);
        $contact = $this->model->determineNetwork($trans_data['phone_number']);
        if ($contact == 'MTN') {

            $result = $this->model->SendByCURL(MOBILE_URL_MTN, $trans_xml);
        } else if ($contact == 'AIRTEL') {

            $result = $this->model->SendByCURL(MOBILE_URL_AIRTEL, $trans_xml);
        } else if ($contact == 'TIGO') {

            $result = $this->model->SendByCURL(MOBILE_URL_TIGO, $trans_xml);
        }
        //print_r($trans_xml);die();	
//print_r($trans_xml);die();		
        $this->logs->ExeLog($data, 'CRBRequest::ProcessPaymentRequest Function Call With Data Set ' . var_export($result, true), 2);

        return $result;
    }

    function HandlePaymentResponse($data, $trans_id, $merchant_resp) {
        //Parse The Merchant Response from XML to Array Format Data
        $merch_resp_array = $this->stan->ParseXMLRequest($merchant_resp);
//print_r($merch_resp_array);die();		
        //March Merchant Response to Platform Responses
        if ($merch_resp_array['responsecode'] == 1000) {
            //Pending.
            $merch_resp_array['status'] = 'pending';
            $this->model->UpdateTransactionStatus($data, $trans_id, $merch_resp_array);
        } else if ($merch_resp_array['responsecode'] == 100) {
            $payment = $this->paymentcompletedrequest($data, $trans_id, $merch_resp_array);
            //complete payment
        } else if ($merch_resp_array['responsecode'] == 108) {
            $trans_data = $this->model->GetTransaction($trans_id);
            $this->model->ProcessTosendSms($trans_data[0], $data);
        } else {
            // close transaction
            $merch_resp_array['status'] = 'failed';
            $this->model->UpdateTransactionStatus($data, $trans_id, $merch_resp_array);
        }
        return $merch_resp_array;
    }

    function paymentcompletedrequest($data, $trans_id, $merch_resp_array) {


        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'dest' => '0780442232',
            'origin' => 'MTN',
            'receivedTimeStamp' => date("Y-m-d H:i:s"),
            'transactionDate' => date("d/m/y"),
            'transactionTime' => date("g:i a"),
            'textToUser' => 'Payment Made',
            'transactionCode' => date("YmdHis"),
            'transactionAccount' => 'P607',
            'senderNames' => $data['fname'] . " " . $data['lname'],
            'transactionAmount' => $data['amount'],
            'transactionMobileNo' => $data['phone'],
        );

        $response = $this->client->getProduct405($request);
        $response = json_decode(json_encode($response), true);
        $this->logs->ExeLog($data, 'CRBRequest::paymentcompletedrequest Function Call With Data Set ' . var_export($response, true), 2);


        if ($response['return']['responseCode'] == 200) {
            //Completed.
            $merch_resp_array['status'] = 'completed';
            $merch_resp_array['responsemsg'] = 'COMPLETED';
            $merch_resp_array['responsecode'] = 100;
            $this->model->UpdateTransactionStatus($data, $trans_id, $merch_resp_array);
        } else {
            //Failed.
            $merch_resp_array['status'] = 'failed';
            $merch_resp_array['responsemsg'] = 'FAILED';
            $this->model->UpdateTransactionStatus($data, $trans_id, $merch_resp_array);
            exit();
        }

        return $response_msg;

        /*
          $response_msg='';
          $response_msg['phone']=$data['phone'];
          if($response['return']['responseCode']==200){
          $response_msg['resp_code']=120;
          }else{
          $response_msg['resp_code']=112;
          }
         */
    }

    function GetCreditReport($data) {
        //refused to work

        if ($data['language'] == 'text_kin') {
            $language = 'rw_RW';
        } else {
            $language = 'en_RW';
        }

        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'emailAddress' => $data['email'],
            'phoneNumber' => $data['phone'],
            'language' => $language,
            'reportSector' => '2',
            'reportReason' => '4',
        );
        $response = $this->client->getProduct406($request);

        $response = json_decode(json_encode($response), true);

        $this->logs->ExeLog($data, 'CRBRequest::GetCreditReport Function Call With Data Set ' . var_export($response, true), 2);

        $response_msg = '';
        $response_msg['phone'] = $data['phone'];
        //print_r($response);die();	
        if ($response['return']['responseCode'] == 200 || $response['return']['responseCode'] == 602) {
            $response_msg['resp_code'] = 108;
        } else if ($response['return']['responseCode'] == 301) {
            $response_msg['resp_code'] = 112;

            $response = $this->CreatePaymentResponseXML($response_msg);

            ob_start();
            echo $response;
            header('Connection: close');
            header('Content-Length: ' . ob_get_length());
            ob_end_flush();
            ob_flush();
            flush();

            sleep(5);
            $this->logs->ExeLog($data, 'CRBRequest::GetCreditReport Data used data ' . var_export($data, true), 2);

            $productdetails = $this->model->GetProductAmount('credit_report', $data['merchant_id']);
            $this->logs->ExeLog($data, 'CRBRequest::GetCreditReport::GetProductAmount data' . var_export($productdetails, true), 2);

            $data['amount'] = $productdetails[0]['product_price'];
            $data['requestid'] = $this->transID;
            $trans_id = $this->model->CreateTransactionRecord($data);
            $request = $this->ProcessPaymentRequest($data, $trans_id);
            $trans_resp_array = $this->HandlePaymentResponse($data, $trans_id, $request);
        } else {
            $response_msg['resp_code'] = 117;
        }

        return $response_msg;
    }

    function VerifyMobileUser($data) {//finished
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'documentNumber' => $data['documentid'],
            'phoneNumber' => $data['phone'],
        );
        $response = $this->client->getProduct407($request);
        $response = json_decode(json_encode($response), true);
        $this->logs->ExeLog($data, 'CRBRequest::VerifyMobileUser Function Call With Data Set ' . var_export($response, true), 2);

        $response_msg = '';
        $response_msg['phone'] = $data['phone'];
        if ($response['return']['responseCode'] == 200) {
            $response_msg['resp_code'] = 118;
        } else {
            $response_msg['resp_code'] = 119;
        }
        
        return $response_msg;
    }

    function GetUserCreditCertificate($data) {//
        if ($data['language'] == 'text_kin') {
            $language = 'rw_RW';
        } else {
            $language = 'en_RW';
        }

        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'emailAddress' => $data['email'],
            'phoneNumber' => $data['phone'],
            'language' => $language
        );
        $response = $this->client->getProduct408($request);

        $this->logs->ExeLog($data, 'CRBRequest::GetUserCreditCertificate Last Request sent ' . var_export($this->client->__getLastRequest(), true), 2);
        $response = json_decode(json_encode($response), true);
        $this->logs->ExeLog($data, 'CRBRequest::GetUserCreditCertificate Function Call With Data Set ' . var_export($response, true), 2);

//print_r($response);die();	
        $response_msg = '';
        $response_msg['phone'] = $data['phone'];
        if ($response['return']['responseCode'] == 200 || $response['return']['responseCode'] == 602) {
            $response_msg['resp_code'] = 115;
        } else if ($response['return']['responseCode'] == 212) {
            $response_msg['resp_code'] = 113;
        } else if ($response['return']['responseCode'] == 301) {
            $response_msg['resp_code'] = 114;
            $response = $this->CreatePaymentResponseXML($response_msg);

            ob_start();
            echo $response;
            header('Connection: close');
            header('Content-Length: ' . ob_get_length());
            ob_end_flush();
            ob_flush();
            flush();

            sleep(5);
            $this->logs->ExeLog($data, 'CRBRequest::GetCreditReport Data used data ' . var_export($data, true), 2);

            $productdetails = $this->model->GetProductAmount('clearance_certificate', $data['merchant_id']);
            $this->logs->ExeLog($data, 'CRBRequest::GetCreditReport::GetProductAmount data' . var_export($productdetails, true), 2);

            $data['amount'] = $productdetails[0]['product_price'];
            $data['requestid'] = $this->transID;
            $trans_id = $this->model->CreateTransactionRecord($data);
            $request = $this->ProcessPaymentRequest($data, $trans_id);
            $trans_resp_array = $this->HandlePaymentResponse($data, $trans_id, $request);
        } else {
            $response_msg['resp_code'] = 117;
        }
        return $response_msg;
    }

    function GetUserCreditScore($data) {//finished
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'phoneNumber' => $data['phone'],
        );
        $response = $this->client->getProduct409($request);
        $response = json_decode(json_encode($response), true);
        $this->logs->ExeLog($data, 'CRBRequest::GetUserCreditScore Function Call With Data Set ' . var_export($response, true), 2);
        return $response;
    }

    function CheckUserReportStatus($data) {//finished 301 if customer is to be billed.
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'phoneNumber' => $data['phone'],
        );
        $response = $this->client->getProduct410($request);
        $response = json_decode(json_encode($response), true);
        $this->logs->ExeLog($data, 'CRBRequest::CheckUserReportStatus Function Call With Data Set ' . var_export($response, true), 2);

        return $response;
    }

    function CheckNonPerformingLoanStatus($data) { //finished 212 if customer is not eligible for a clearance certificate.
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'message' => 'Testing',
            'phoneNumber' => $data['phone'],
        );
        $response = $this->client->getProduct411($request);
        $response = json_decode(json_encode($response), true);
        $this->logs->ExeLog($data, 'CRBRequest::CheckNonPerformingLoanStatus Function Call With Data Set ' . var_export($response, true), 2);

        return $response;
    }

    function getServerInformation($data) {//finished
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
        );
        $response = $this->client->getServerInfo($request);
        $response = json_decode(json_encode($response), true);
        $this->logs->ExeLog($data, 'CRBRequest::getServerInformation Function Call With Data Set ' . var_export($response, true), 2);

        return $response;
    }

    function formatCreditSatusResponse($status) {


        $stan_array = '';

        $stan_array = "CREDIT STATUS:";
        if (isset($status[0])) {
            $len = count($status);
            $i = 0;
            foreach ($status as $key => $value) {

                if ($i == $len - 1) {// last
                    if ($value['subscriberShortCode'] == '') {
                        $stan_array .= " None:";
                    } else {
                        $stan_array .= " " . $value['subscriberShortCode'] . ":";
                    }
                    $stan_array .= " Bal=" . number_format($value['balanceAmount']) . ":";
                    $stan_array .= " Status=" . $value['category'] . " ";
                } else {//not last
                    if ($value['subscriberShortCode'] == '') {
                        $stan_array .= " None:";
                    } else {
                        $stan_array .= " " . $value['subscriberShortCode'] . ":";
                    }
                    $stan_array .= " Bal=" . number_format($value['balanceAmount']) . ":";
                    $stan_array .= " Status=" . $value['category'] . "/";
                }
                $i++;
            }
        } else {//single status
            if ($value['subscriberShortCode'] == '') {
                $stan_array .= " None:";
            } else {
                $stan_array .= " " . $value['subscriberShortCode'] . ":";
            }
            $stan_array .= " " . number_format($status['balanceAmount']) . ":";
            $stan_array .= " Status=" . $status['category'];
        }
        return $stan_array;
    }

    function CreateResponseXML($array) {
        $xml = '';
        $xml .= '<response>';
        $xml .= '<response_code>' . $array['resp_code'] . '</response_code>';
        $xml .= '<msisdn>' . $array['phone'] . '</msisdn>';
        if (isset($array['resp_message'])) {
            $xml .= '<response_msg>' . $array['resp_message'] . '</response_msg>';
        }
        if (isset($array['firstname'])) {
            $xml .= '<first_name>' . $array['firstname'] . '</first_name>';
            $xml .= '<last_name>' . $array['lastname'] . '</last_name>';
        }

        $xml .= '</response>';

        return $xml;
    }

    function CreatePaymentResponseXML($array) {
        $xml = '';
        $xml .= '<response>';
        $xml .= '<responsecode>' . $array['resp_code'] . '</responsecode>';
        $xml .= '<msisdn>' . $array['phone'] . '</msisdn>';
        if (isset($array['resp_message'])) {
            $xml .= '<responsemsg>' . $array['resp_message'] . '</responsemsg>';
        }
        if (isset($array['firstname'])) {
            $xml .= '<first_name>' . $array['firstname'] . '</first_name>';
            $xml .= '<last_name>' . $array['lastname'] . '</last_name>';
        }
        $xml .= '</response>>';

        return $xml;
    }

}

?>
