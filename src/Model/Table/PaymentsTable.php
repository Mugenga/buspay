<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class PaymentsTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('client_payments');
        $this->hasMany('Transactions');
        $this->belongsTo('Clients');
        $this->belongsTo('Conductors');
    }

    public function handlePayment($data)
    {
        //Close the request.
        $toPost['client_id'] = $data->client_id;
        $toPost['conductor_id'] = $data->conductor_id;
        $toPost['transaction_amount'] = $data->transaction_amount;
    
        $payment = $this->newEntity($toPost);
        if ($this->save($payment)) {
            
            // Create a Transaction
            $transaction = $this->Transactions->createTransaction($payment, "payment");
            $updatePaymentPending = $this->get($payment->id);
            if($transaction != null) {
                $updatePaymentPending->payment_status = "pending" ;
                if ($this->save($updatePaymentPending)) { }else{
                    //Log
                }

                //Get Client
                $client = $this->Clients->get($data->client_id);
                //Get Conductor
                $conductor = $this->Conductors->get($data->conductor_id);

                if($client != '' && $conductor != ''){
                    $client->balance = $client->balance - $data->transaction_amount;
                    $conductor->balance = $conductor->balance + $data->transaction_amount;
                    if($this->Clients->save($client) && $this->Conductors->save($conductor)){
                        //Payment
                        $updatePayment = $this->get($payment->id);
                        $updatePayment->client_balance = $client->balance;
                        $updatePayment->conductor_balance = $conductor->balance;
                        $updatePayment->payment_status = "completed" ;

                        if ($this->save($updatePayment)) {
                            $response_array['success'] = true;
                            $response_array['code'] = 100;
                            $response_array['payment'] = $updatePayment;
                            $response_array['message'] = "Payment completed successfully";
                        }

                    }else{
                        // Log
                        $response_array['success'] = false;
                        $response_array['code'] = 103;
                        $response_array['response_message'] = "Could not update user or conductor balance";
                    }
                }

            } else {
                $updatePaymentPending->payment_status = "failed" ;
                $this->save($updatePaymentPending);

                $response_array['response_code'] = 102;
                $response_array['response_status'] = 'failed';
                $response_array['response_message'] = "Could not create transaction";
            }
        
                } else {
                    $response_array['response_code'] = 101;
                    $response_array['response_status'] = 'failed';
                    $response_array['response_message'] = "Could not Recharge Account";
                    // Log this transaction
                }
        
                return $response_array;
    }
}
