<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class RequestsTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('vnd_smpp_requests');

        $this->hasMany('Transactions')->setForeignKey('record_id')->setDependent(true);
        $this->hasMany("APIResps");
        $this->hasMany("Responses");

        $this->belongsTo('Operations', ['foreignKey' => 'operator_id', 'joinType' => 'INNER']);
        $this->belongsTo('Choices', ['foreignKey' => 'record_id', 'joinType' => 'INNER']);
        $this->belongsTo('Members', ['foreignKey' => 'record_id', 'joinType' => 'INNER']);
        $this->hasMany("Turequests");
    }

    function VerifyMSISDN($msisdn) {
        $member = $this->Members->GetMemberByMSISDN($msisdn);
        if (!empty($member) && $member[0]['reg_status'] == 'Completed') {
            $resp_array['flag'] = true;
            $resp_array['language'] = $member[0]['language'];
        } else {
            $resp_array['flag'] = false;
        }
        return $resp_array;
    }

    function GetCreditReport($data) {
        $res_array = $this->CreateNewReqest($data);
        if (!empty($res_array)) {
            if (array_key_exists('tid', $res_array)) {
                $resp_array['status_code'] = 703;
                $resp_array['response_msg'] = 'Successfully created Credit Report Request. Approve Payment Request to Complete.';
                $resp_array['transid'] = $res_array['tid'];
                $resp_array['amount'] = $res_array['amount'];
            } else {
                $resp_array['status_code'] = 704;
                $resp_array['response_msg'] = 'Successfully created Credit Report Request.';
            }
        } else {
            $resp_array['status_code'] = 705;
            $resp_array['response_msg'] = 'Request for a Credit Report Could not be completed at this time.';
        }

        return $resp_array;
    }

    function GetCreditCertificate($data) {
        $res_array = $this->CreateNewReqest($data);
        if (!empty($res_array)) {
            if (array_key_exists('tid', $res_array)) {
                $resp_array['status_code'] = 706;
                $resp_array['response_msg'] = 'Successfully created Credit Certificate Request. Approve Payment Request to Complete.';
                $resp_array['transid'] = $res_array['tid'];
                $resp_array['amount'] = $res_array['amount'];
            } else {
                $resp_array['status_code'] = 707;
                $resp_array['response_msg'] = 'Successfully created Credit Certificate Request.';
            }
        } else {
            $resp_array['status_code'] = 708;
            $resp_array['response_msg'] = 'Request for a Credit Certificate Could not be completed at this time.';
        }

        return $resp_array;
    }

    function GetCreditStatus($data) {
        $res_array = $this->CreateNewReqest($data);
        if (!empty($res_array)) {
            if (array_key_exists('tid', $res_array)) {
                $resp_array['status_code'] = 711;
                $resp_array['response_msg'] = 'Successfully created Credit Status Request. Approve Payment Request to Complete.';
                $resp_array['transid'] = $res_array['tid'];
                $resp_array['amount'] = $res_array['amount'];
            } else {
                $resp_array['status_code'] = 709;
                $resp_array['response_msg'] = 'Successfully created Credit Status Request.';
            }
        } else {
            $resp_array['status_code'] = 710;
            $resp_array['response_msg'] = 'Request for your Credit Status Could not be completed at this time.';
        }

        return $resp_array;
    }

    function CreateNewReqest($data) {
        //Check if it is a CC/CR Request
        if ($data['keyword'] == "CC" || $data['keyword'] == "CR") {
            $this->CheckEmailRecord($data);
        }
        $res_array = array();
        $smpprequest = $this->Choices->find('all', array('conditions' => array('smpp_request' => $data['keyword'])))->toArray();
        $res_array['request_id'] = $this->RecordRequest($data, $smpprequest);
        if ($smpprequest[0]['is_chargable'] == "Yes") {
            $res_array['tid'] = $this->RecordTransaction($data, $smpprequest, $res_array['request_id']);
            $res_array['amount'] = $smpprequest[0]['amount'];
        }

        return $res_array;
    }

    function CheckEmailRecord($data) {
        $member = $this->Members->GetMemberByMSISDN($data['phone_number']);
        if ($member[0]['email_address'] == " " && $data['email'] != " ") {
            $memupdate = array('email_address' => $data['email']);
            $conditions = array('record_id' => $member[0]['record_id']);
            $this->Members->updateAll($memupdate, $conditions);
        }
    }

    function RecordRequest($data, $smpprequest) {
        if (!isset($data['query_string'])) {
            $data['query_string'] = " ";
        }
        if (array_key_exists('telephone_number', $data)) {
            $telephone = $data['telephone_number'];
        } else {
            $telephone = $data['phone_number'];
        }

        $postData = array(
            'date' => date("Y-m-d H:i:s"),
            'phone_number' => $telephone,
            'query_string' => $data['query_string'],
            'request_string' => $data['request_string'],
            'request_type' => $data['request_type'],
            'key_word' => $smpprequest[0]['smpp_request'],
            'is_chargable' => $smpprequest[0]['is_chargable'],
            'charge_amount' => $smpprequest[0]['amount'],
            'request_source' => $data['request_source'],
            'request_source_id' => $data['request_source_id']
        );
        if (isset($data['chatid'])): $postData['chatid'] = $data['chatid'];
        endif;
        $memberrequest = $this->newEntity($postData);
        $result = $this->save($memberrequest);
        if (isset($result->record_id)) {
            $record_id = $result->record_id;
        } else {
            $record_id = 0;
        }
        return $record_id;
    }

    function RecordTransaction($data, $smpprequest, $reqid) {
        $postData = array(
            'transaction_date' => date("Y-m-d G:i:s"),
            'request_id' => $reqid,
            'msisdn' => $data['phone_number'],
            'transaction_amount' => $smpprequest[0]['amount'],
            'transaction_status' => 'New',
            'last_updated' => date("Y-m-d G:i:s")
        );

        $record = $this->Transactions->newEntity($postData);
        $result = $this->Transactions->save($record);
        if (isset($result->transaction_id)) {
            $transactionid = $result->transaction_id;
        }
        return $transactionid;
    }

    function GetByChoice($data) {
        $smpprequest = $this->Choices->find('all', array('conditions' => array('smpp_request' => $data)));
        return $smpprequest;
    }

    function HandleNewRegistration($data) {
        $nidflag = $this->ValidateNID($data['national_id']);
        $emailflag = $this->ValidateEmail($data['email']);
        if ($nidflag == 1 && $emailflag == 1) {
            $resp_array = $this->Members->CreateNewMember($data);
            if (isset($resp_array['data']['record_id'])) {
                //Handle Member Registration Continuation Here....
                $resparray = $this->ProcessResponse($resp_array['status_code'], $data['telephone_number']);
                ob_start();
                echo json_encode($resparray);
                ob_end_flush();
                flush();
                //Get Request.
                $this->Members->CompleteRegistration($resp_array['data']['record_id']);
                die();
            }
        } else {
            $resp_array['status_code'] = 714;
            $resp_array['response_msg'] = 'Incorrect NID or Email Submitted';
        }

        return $resp_array;
    }

    function ValidateNID($nid) {
        $flag = 1;
        if (strlen($nid) != 16 || !is_numeric($nid)) {
            $flag = 0;
        }
        return $flag;
    }

    function ValidateEmail($email) {
        $flag = 1;
        return $flag;
    }

    function ProcessResponse($respcode, $msisdn) {
        //Get the Response Message Array
        $resparray = $this->APIResps->GetResp($respcode);
        //Get the Member Record
        $member = $this->Members->GetMemberByMSISDN($msisdn);

        if ($member[0]['language'] == '') {
            $flag = 'text_kin';
        } else {
            $flag = $member[0]['language'];
        }

        //Prep Response
        $resp_array['status_code'] = $respcode;
        $resp_array['response_msg'] = $resparray[0][$flag];

        return $resp_array;
    }

    function FlagRequest($trans, $resp) {
        $transrec = $trans->toArray();
        $req_record = $this->get($transrec['request_id']);
        if ($resp['status_code'] == '100') {
            $req_record->request_status = 'Processed';
        } else {
            $req_record->request_status = 'Failed';
        }
        $req_record->response_time = date("Y-m-d G:i:s");
        $this->save($req_record);
    }
    
    function RetryRequest($id){
        $req = $this->get($id);
        $request = $req->toArray();
        if($request['request_type'] == 'Register'){
            //Check Whether the registration completed.
            $member = $this->Members->GetMemberByMSISDN($request['phone_number']);
            if($member[0]['reg_status'] == 'Failed' && $member[0]['fname'] == '' && $member[0]['lname'] == ''){
                //Registration Failed, Please Try Again...
                 $this->Members->CompleteRegistration($member[0]['record_id']);
            }
        }else{
            //Just Fire It to TU.
            $this->Turequests->MakeTURequest($request);
        }
        die();
    }

}
