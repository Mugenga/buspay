<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class ResponsesTable extends Table {

    public $response_array = array(
        'getcreditcertificate' => array(
            '200' => 115,
            '602' => 115,
            '212' => 113,
            '301' => 114,
            '402' => 999
        ),
        'verifymobileuser' => array(
            '200' => 118,
            '402' => 999
        ),
        'getcreditreport' => array(
            '200' => 108,
            '602' => 108,
            '301' => 112,
            '402' => 999
        ),
        'creditstatus' => array(
            '200' => 104,
            '202' => 110,
            '402' => 999
        ),
        'register' => array(
            '200' => 132,
            '210' => 132,
            '402' => 999
        ),
        'getcreditstatus' => array(
            '200' => 104,
            '202' => 110,
            '402' => 999
        )
    );
    public $other_resp = array(
        'register' => 102,
        'getcreditstatus' => 110,
        'getcreditreport' => 117,
        'verifymobileuser' => 119,
        'getcreditcertificate' => 117
    );

    public function initialize(array $config) {
        parent::initialize($config);
        $this->table('vnd_smpp_response_codes');
        $this->hasMany("Requests");
    }

    function ProcessTUResponse($resp, $request) {
        $key = strtolower($request['request_type']);
        $val = $resp['return']['responseCode'];
        $standard_key = $this->response_array[$key][$val];
        if ($standard_key == '') {
            $stan = $this->other_resp[$key];
        } else {
            $stan = $standard_key;
        }
        $resp_array['retcode'] = $resp['return']['responseCode'];
        $resp_array['resp_code'] = $stan;
        if ($key == 'getcreditstatus' && $resp_array['retcode'] == 200) {
            $resp_array['retmessage'] = $this->FormatCreditSatusResponse($resp['return']['accountList']);
        }

        $this->CompleteRequest($resp_array, $request);
    }

    function CompleteRequest($resp_array, $req) {
        //Get Request
        $request = $this->Requests->get($req['record_id']);
        $request->response_time = date("Y-m-d G:i:s");
        $request->resp_code = $resp_array['resp_code'];
        $request->tu_respcode = $resp_array['retcode'];
        if (array_key_exists('retmessage', $resp_array)) {
            $request->tu_respstring = $resp_array['retmessage'];
        }

        $request->request_status = 'Completed';
        $this->Requests->save($request);
        //Triger Send Response
        $this->SendUserResponse($req['record_id']);
    }

    function SendUserResponse($rid) {
        //Request
        $request = $this->Requests->get($rid)->toArray();
        if ($request['key_word'] == 'CS' && $request['tu_respcode'] == '200') {
            $resptxt = $request['tu_respstring'];
        } else {
            $respmsg = $this->find('all', array('conditions' => array('response_code' => $request['resp_code'])))->toArray();
            $resptxt = $respmsg[0]['text_en'];
        }

        $msgarray = array(
            'msg_from' => '2272',
            'msg_to' => $request['phone_number'],
            'text' => $resptxt
        );

        if ($request['request_source'] == 'WA') {
            //Send to WhatsApp Bot.
            $this->sendMessage($request['chatid'], $resptxt);
        } else {
            //Send Via SMS.
            $this->SendSMSRequest($msgarray);
        }
    }

    function SendSMSRequest($array) {
        $sender = urlencode($array['msg_from']);
        $phone = urlencode($array['msg_to']);
        $message = urlencode($array['text']);
        $url = SMS_URL . "/cgi-bin/sendsms?username=" . SMS_USER . "&password=" . SMS_PASS . "&to=$phone&text=$message&from=$sender";
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url
        ]);
        $resp = curl_exec($curl);

        if (curl_errno($curl)) {
            $contarray = array('responsecode' => 999, 'responsemsg' => curl_error($curl));
            $content = json_encode($contarray);
        } else {
            $content = "0:Accept for delivery1";
        }
        curl_close($curl);
        return $content;
    }
    
    public function sendMessage($chatId, $text) {
        $data = array('chatId' => $chatId, 'body' => $text);
        $this->sendRequest('message', $data);
    }

    public function sendRequest($method, $data) {
        $url = WA_URL . $method . '?token=' . WA_TOKEN;
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $resource = ['http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/json',
                'content' => $data]];

        $options = stream_context_create($resource);
        $response = file_get_contents($url, false, $options);
    }

    /*
     * Possibly Useful:
     */

    function FormatCreditSatusResponse($status) {
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

}
