<?php

namespace App\Controllers;

use App\Controllers\Controller as ControllersController;
use App\Services\AppServices\ExceptionServices;
use App\Services\AppServices\Validator;
use App\Services\MgmtDBServices;

class MgmtDBController extends ControllersController
{
    protected $ExceptionServices;
    protected $validator;
    protected $MgmtDBServices;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->ExceptionServices = new ExceptionServices();
        $this->Validator = new Validator();
        $this->MgmtDBServices = new MgmtDBServices();

    }

    public function getMachines($request, $response)
    {
        try {
            $plant_key = $request->getAttribute('plant_key');
            $data = $this->MgmtDBServices->getMachines($plant_key);

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        echo json_encode($data);
    }

    public function getPowerReport($request, $response)
    {
        try {
            $input = [
                'customer_key' => $request->getParam('customer_key'),
                'plant_key' => $request->getParam('plant_key'),
                'type' => $request->getParam('type'),
            ];

            $data = $this->MgmtDBServices->getPowerReport($input);

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        echo json_encode($data);
    }

    public function operatorUtilization($request, $response)
    {
        try {

            $input = [
                'customer_key' => $request->getParam('customer_key'),
                'plant_key' => $request->getParam('plant_key'),
                'type' => $request->getParam('type'),
            ];
            $data = $this->MgmtDBServices->operatorUtilization($input);

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        echo json_encode($data);
    }
    public function machineUtilization($request, $response)
    {
        try {
            $input = [
                'customer_key' => $request->getParam('customer_key'),
                'plant_key' => $request->getParam('plant_key'),
                'type' => $request->getParam('type'),
            ];
            $data = $this->MgmtDBServices->machineUtilization($input);
        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        echo json_encode($data);
    }
    public function partsUtilization($request, $response)
    {
        try {
            $input = [
                'customer_key' => $request->getParam('customer_key'),
                'plant_key' => $request->getParam('plant_key'),
                'type' => $request->getParam('type'),
            ];
            $data = $this->MgmtDBServices->partsUtilization($input);

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        echo json_encode($data);
    }

}
