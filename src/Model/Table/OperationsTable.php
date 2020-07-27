<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class OperationsTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('network_operators');
         $this->hasMany('Requests')->setForeignKey('operator_id')->setDependent(true);
         
    }
}