<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TurequestsTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->hasMany('Requests');
        $this->hasMany('Members');
        $this->hasMany("Responses");
        $options = array(
            'login' => SVC_LOGIN,
            'password' => SVC_PASSD,
            'trace' => 1
        );

        $this->_soapclient = new \SoapClient(SVC_URL, $options);
    }

    function ProcessQueuedRequests() {
        $requests = $this->Requests->find('all', array(
                    'conditions' => array('request_status' => 'Queued'
            )))->toArray();

        foreach ($requests as $key => $value) {
            $this->MakeTURequest($value);
        }
    }

    function MakeTURequest($req) {
        $response = $this->{$req['request_type']}($req);
        $resp_array = json_decode(json_encode($response), true);
        $this->Responses->ProcessTUResponse($resp_array, $req);
    }

    function GetCreditStatus($data) {
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'phoneNumber' => $data['phone_number'],
            'reportSector' => '2',
            'reportReason' => '4',
        );
        $response = $this->_soapclient->getProduct403($request);

        return $response;
    }

    function GetCreditCertificate($data) {
        $member = $this->Members->GetMemberByMSISDN($data['phone_number']);
        $languageflag = $this->GetLanguageFlag($member[0]['language']);

        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'emailAddress' => $member[0]['email_address'],
            'phoneNumber' => $data['phone_number'],
            'language' => $languageflag
        );
        $response = $this->_soapclient->getProduct408($request);

        return $response;
    }

    function GetCreditReport($data) {
        $member = $this->Members->GetMemberByMSISDN($data['phone_number']);
        $languageflag = $this->GetLanguageFlag($member[0]['language']);

        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'emailAddress' => $member[0]['email_address'],
            'phoneNumber' => $data['phone_number'],
            'language' => $languageflag,
            'reportSector' => '2',
            'reportReason' => '4',
        );
        $response = $this->_soapclient->getProduct406($request);

        return $response;
    }

    function GetUserCreditScore($data) {//finished
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'phoneNumber' => $data['phone_number'],
        );
        $response = $this->_soapclient->getProduct409($request);
        return $response;
    }

    function CheckUserReportStatus($data) {
        //finished 301 if customer is to be billed.
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'phoneNumber' => $data['phone_number'],
        );
        $response = $this->_soapclient->getProduct410($request);

        return $response;
    }

    function CheckNonPerformingLoanStatus($data) {
        //finished 212 if customer is not eligible for a clearance certificate.
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'message' => 'Testing',
            'phoneNumber' => $data['phone'],
        );
        $response = $this->_soapclient->getProduct411($request);

        return $response;
    }

    function CheckMemberRegistration($data) {//finished 200=success
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'message' => 'User Verification',
            'phoneNumber' => $data['phone_number'],
            'reportSector' => '2',
            'reportReason' => '4',
        );
        $response = $this->_soapclient->getProduct401($request);

        return $response;
    }

    function Register($data) {//finished
        $member = $this->Members->GetMemberByMSISDN($data['phone_number']);
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'documentNumber' => $member[0]['national_id'],
            'names' => $member[0]['fname'] . " " . $member[0]['lname'],
            'phoneNumber' => $data['phone_number'],
            'reportSector' => '2',
            'reportReason' => '4',
        );

        $response = $this->_soapclient->getProduct402($request);

        return $response;
    }

    function VerifyMobileUser($data) {//finished
        $member = $this->Members->GetMemberByMSISDN($data['phone_number']);
        $request = array(
            'username' => REQ_UNAME,
            'password' => REQ_PASS,
            'code' => REQ_CODE,
            'infinityCode' => REQ_INFINITYCode,
            'documentNumber' => $member[0]['national_id'],
            'phoneNumber' => $data['phone'],
        );
        $response = $this->_soapclient->getProduct407($request);

        return $response;
    }

    function PaymentCompletedRequest($data) {
        $member = $this->Members->GetMemberByMSISDN($data['phone_number']);
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
            'senderNames' => $member[0]['fname'] . " " . $member[0]['lname'],
            'transactionAmount' => $data['charge_amount'],
            'transactionMobileNo' => $data['phone_number'],
        );

        $response = $this->_soapclient->getProduct405($request);

        return $response;
    }

    /*
     * Supporting Functions
     */

    function GetLanguageFlag($lang) {
        if ($lang == 'text_kin') {
            $language = 'rw_RW';
        } else {
            $language = 'en_RW';
        }

        return $language;
    }

}
