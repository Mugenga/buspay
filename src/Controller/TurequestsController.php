<?php

namespace App\Controller;

use App\Controller\AppController;

class TurequestsController extends AppController {

    public function initialize() {
        parent::initialize();
    }

    public function Index() {
        $this->Turequests->ProcessQueuedRequests();
        die();
    }

}
