<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class APIRespsTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('vnd_api_response_codes');
    }
    
    function GetResp($code){
        return $this->find('all', array('conditions'=>array('status_code'=>$code)))->toArray();
    }

}