<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class ConductorsTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('conductors');
        $this->hasMany('Payments');
    }

    public function GetConductorTransactions($data)
    {
        // Check if Conductor exisits
        // TO DO

        $options = array(
            'conditions' => array(
                'conductor_id' => $data->conductor_id,
                'payment_status' => 'completed'
            )
        );
        $payments = $this->Payments->find('all', $options);
        $balance = $this->get($data->conductor_id);

        $response_array['success'] = true;
        $response_array['code'] = 100;
        $response_array['balance'] = $balance->balance;
        $response_array['no_of_transactions'] = $payments->count();
        $response_array['transactions'] = $payments;
        $response_array['message'] = "Conductor transactions query was successfull";

        return $response_array;
    }
}
