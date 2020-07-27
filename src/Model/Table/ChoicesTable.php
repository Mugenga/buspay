<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class ChoicesTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('vnd_smpp_choices');

       $this->hasMany('Requests')->setForeignKey('record_id')->setDependent(true);
    }

}