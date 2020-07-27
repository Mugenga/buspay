<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class RechargesTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('client_recharges');
        $this->hasMany('Transactions');
        $this->belongsTo('Clients');
    }

    public function handleRecharge($data) 
    {
        //Close the request.
        $toPost['client_id'] = $data->client_id;
        $toPost['agent_id'] = $data->agent_id;
        $toPost['transaction_amount'] = $data->transaction_amount;
        $toPost['recharging_status'] = 'new';
    
        $recharge = $this->newEntity($toPost);
        if ($this->save($recharge)) {

            // Create a Transaction
            $transaction = $this->Transactions->createTransaction($recharge, 'request');
            $updateRechargePending = $this->get($recharge->id);
            
            if($transaction != null) {
                $updateRechargePending->recharge_status = "pending" ;
                if ($this->save($updateRechargePending)) { }else{
                    //Log
                }

                //Get Client
                $client = $this->Clients->get($data->client_id);

                if($client != ''){
                    $client->balance = $client->balance + $data->transaction_amount;
                    if($this->Clients->save($client)){
                        //recharge
                        $updateRecharge = $this->get($recharge->id);
                        $updateRecharge->client_balance = $client->balance;
                        $updateRecharge->recharging_status = "completed" ;

                        if ($this->save($updateRecharge)) {
                            $response_array['response_code'] = 100;
                            $response_array['recharge'] = $updateRecharge;
                            $response_array['response_message'] = "Recharge completed successfully";
                        }

                    }else{
                        // Log
                        $response_array['response_code'] = 103;
                        $response_array['response_status'] = 'failed';
                        $response_array['response_message'] = "Could not update user balance";
                    }
                }

            } else {
                $updateRechargePending->recharge_status = "failed" ;
                $this->save($updateRechargePending);

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
