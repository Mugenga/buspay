<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TransactionsTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('transaction_history');
        $this->belongsTo('Recharges');
        $this->belongsTo('Payments');
    }
    
    function createTransaction($data, $type) {
        if($type == 'request'){
            $postData['request_id'] = $data->id;
        }elseif($type == 'payment'){
            $postData['payment_id'] = $data->id;
        }
        //Close the request.
        $postData['user_id'] = $data->client_id;
        $postData['payment_amount'] = $data->transaction_amount;
        $postData['request_type'] = $type;
        $postData['transaction_status'] = "completed";
  
        $transaction = $this->newEntity($postData);
        if ($this->save($transaction)) {return $transaction->id; } 
        else {
            // Log 
            return null;
        }
    }

    function updateTransactionToPending($id) 
    {
        $client = $this->get($id);
        $client->transaction_status = 'pending';
        if ($this->save($client)) {
            $response_array['response_code'] = 100;
            $response_array['transaction_id'] = $client->id;
            $response_array['response_message'] = "Transaction is pending";
        } else {
            $response_array['response_code'] = 102;
            $response_array['response_message'] = "Could not update transaction";
        }

        return $response_array;
    }

    function updateTransactionToCompleted($id) 
    {
        $transaction = $this->get($id);
        $transaction->transaction_status = 'completed';
        if ($this->save($transaction)) {
            $response_array['response_code'] = 100;
            $response_array['transaction_id'] = $transaction->id;
            $response_array['response_message'] = "Transaction is Completed";

            $response_array = $this->Payments->createPayment($transaction);
        } else {
            $response_array['response_code'] = 102;
            $response_array['response_message'] = "Could not update transaction";
        }

        return $response_array;
    }
}
