<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Services\AppServices\ExceptionServices;
use App\Services\AppServices\Validator;
use App\Services\MacDBReportServices;

class MacDBReportController extends Controller
{
    protected $ExceptionServices;
    protected $validator;
    protected $MachineReportServices;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->ExceptionServices = new ExceptionServices();
        $this->validator = new Validator();
        $this->MachineReportServices = new MacDBReportServices();
    }

    public function getCount($request, $response)
    {

        try {
            $machine_serial = $request->getAttribute('key');
            $date = $request->getAttribute('fdate');
            $data = $this->MachineReportServices->getCount($machine_serial, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'Plant List Fetching Failed';
        }
        echo json_encode($data);
    }

    public function getCyclingTotalCount($request, $response)
    {

        try {
            $machine_serial = $request->getAttribute('key');
            $date = $request->getAttribute('fdate');
            $data = $this->MachineReportServices->getCyclingTotalCount($machine_serial, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'Plant List Fetching Failed';
        }
        echo json_encode($data);
    }

    public function getIdleCount($request, $response)
    {

        try {
            $machine_serial = $request->getAttribute('key');
            $date = $request->getAttribute('fdate');
            $data = $this->MachineReportServices->getIdleCount($machine_serial, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'Plant List Fetching Failed';
        }
        echo json_encode($data);
    }

    public function getPauseCountPid($request, $response)
    {

        try {
            $machine_serial = $request->getAttribute('key');
            $date = $request->getAttribute('fdate');
            $data = $this->MachineReportServices->getPauseCountPid($machine_serial, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'Plant List Fetching Failed';
        }
        echo json_encode($data);
    }

    public function getAllCountPid($request, $response)
    {

        try {
            $machine_serial = $request->getAttribute('key');
            $date = $request->getAttribute('fdate');
            $data = $this->MachineReportServices->getAllCountPid($machine_serial, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'Plant List Fetching Failed';
        }
        echo json_encode($data);
    }

    public function detailsonmacwise($request, $response)
    {
        try {
            $machine_serial = $request->getAttribute("machine_serial");
            $start_date  = $request->getAttribute("start_date");
            $data = $this->MachineReportServices->detailsonmacwise($machine_serial, $start_date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Illuminate\Database\QueryException $e) {

            $data = $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = "Failed";
        }
        echo json_encode($data);
    }

    public function getCountAcs($request, $response)
    {
        try {
            $machine_serial = $request->getAttribute("machine_serial");
            $start_date  = $request->getAttribute("start_date");
            $data = $this->MachineReportServices->getCountAcs($machine_serial, $start_date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Illuminate\Database\QueryException $e) {

            $data = $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = "Failed";
        }
        echo json_encode($data);
    }

    public function ovrsCountDayMachhinetWise($request, $response)
    {
        try {
            $machine_serial = $request->getAttribute('machine_serial');
            $start_date  = $request->getAttribute("start_date");

            $data = $this->MachineReportServices->ovrsCountDayMachhinetWise($machine_serial, $start_date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'machine List Fetching Failed';
        }
        echo json_encode($data);
    }

    public function oeeCountDayMachineWise($request, $response)
    {

        try {
            $machine_serial = $request->getAttribute('machine_serial');
            $start_date  = $request->getAttribute("start_date");

            $data = $this->MachineReportServices->oeeCountDayMachineWise($machine_serial, $start_date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'machine List Fetching Failed';
        }
        echo json_encode($data);
    }

    public function getDetails($request, $response)
    {

        try {
            $machine_serial = $request->getAttribute('machine_serial');
            $date  = $request->getAttribute("date");
            $v  = $request->getAttribute("v");

            $data = $this->MachineReportServices->getDetails($machine_serial, $date, $v);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'machine List Fetching Failed';
        }
        echo json_encode($data);
    }

    public function getAlramDetails($request, $response)
    {

        try {
            $machine_serial = $request->getAttribute('machine_serial');
            $date  = $request->getAttribute("date");
            $data = $this->MachineReportServices->getAlramDetails($machine_serial, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'machine List Fetching Failed';
        }
        echo json_encode($data);
    }

    public function getAcsDetails($request, $response)
    {

        try {
            $machine_serial = $request->getAttribute('machine_serial');
            $date  = $request->getAttribute("date");
            $data = $this->MachineReportServices->getAcsDetails($machine_serial, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'machine List Fetching Failed';
        }
        echo json_encode($data);
    }

    public function getOvrDetails($request, $response)
    {

        try {
            $machine_serial = $request->getAttribute('machine_serial');
            $date  = $request->getAttribute("date");
            $data = $this->MachineReportServices->getOvrDetails($machine_serial, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'machine List Fetching Failed';
        }
        echo json_encode($data);
    }

    public function cellProduction($request, $response)
    {

        try {
            $cell = $request->getParam('key');
            $date = $request->getParam('date');

            $data = $this->MachineReportServices->cellProduction($cell, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'Fetching Failed';
        }
        echo json_encode($data);
    }

    public function cellAlarm($request, $response)
    {

        try {
            $cell = $request->getParam('key');
            $date = $request->getParam('date');

            $data = $this->MachineReportServices->cellAlarm($cell, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'Fetching Failed';
        }
        echo json_encode($data);
    }

    public function cellAcs($request, $response)
    {

        try {
            $cell = $request->getParam('key');
            $date = $request->getParam('date');

            $data = $this->MachineReportServices->cellAcs($cell, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'Fetching Failed';
        }
        echo json_encode($data);
    }

    public function cellOvr($request, $response)
    {

        try {
            $cell = $request->getParam('key');
            $date = $request->getParam('date');

            $data = $this->MachineReportServices->cellOvr($cell, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'Fetching Failed';
        }
        echo json_encode($data);
    }

    public function cellOEE($request, $response)
    {

        try {
            $cell = $request->getParam('key');
            $date = $request->getParam('date');

            $data = $this->MachineReportServices->cellOEE($cell, $date);

            if ($data['success'] == 0) {
                $data =  $this->ExceptionServices->service_level_exception($data, $this->lang);
            }
        } catch (\Exception $e) {
            $data =  $this->ExceptionServices->controller_level_exception($e, $this->lang);
            $data['msg'] = 'Fetching Failed';
        }
        echo json_encode($data);
    }
}
