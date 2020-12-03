<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class ClientsTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('clients');
    }
    
    function handleRegistration($data) {
        //Close the request.
        $postData['first_name'] = $data->first_name;
        $postData['last_name'] = $data->last_name;
        $postData['phone_number'] = $data->phone_number;
  
        $client = $this->newEntity($postData);
        if ($this->save($client)) {
            $response_array['response_code'] = 100;
            $response_array['user'] = $client;
            $response_array['response_status'] = 'Success';
            $response_array['response_message'] = "User Created successfully.";
        } else {
            $response_array['response_code'] = 102;
            $response_array['response_message'] = "Could not register use!";
        }

        return $response_array;
    }

    public function GetClientBalance($id)
    {
        return $this->find("all", array(
            'conditions' => [
                "id" => $id
            ],
            'fields' => "balance"
        ))->first();
    }

    public function GetClientData($data) 
    {
        $client = $this->get($data->client_id)->toArray();
        if($client) {
            $response_array['response_code'] = 100;
            $response_array['response_message'] = "Success";
            $response_array['client'] = $client;
        }
        else{
            $response_array['response_code'] = 101;
            $response_array['status'] = "failed";
            $response_array['response_message'] = "Could not find user";
        }
        return $response_array;
    }

    public function getClient($id)
    {
        return $this->get($id)->toArray();
    }
}
