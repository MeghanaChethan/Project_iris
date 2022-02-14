<?php

namespace App\Services;

use App\Services\AppServices\ConfigServices;
use App\Services\AppServices\DBServices;
use Illuminate\Database\Capsule\Manager as DB;

class MgmtDBServices
{
    private $dbObj;
    private $ConfigServices;
    public function __construct()
    {
        $this->dbObj = new DBServices();
        $this->ConfigServices = new ConfigServices();
    }

    public function getMachines($plant_key)
    {
        try {
            $machineData = DB::table('machines')
                ->leftjoin('cells', 'cells.cell_key', 'machines.cell_key')
                ->leftjoin('plants', 'plants.plant_key', 'cells.plant_key')
                ->where('plants.plant_key', '=', $plant_key)
                ->where('machines.is_subscribed', '=', 1)
                ->select('machines.machine_serial')
                ->get();

            if ($machineData->count() > 0) {
                $data['success'] = 1;
                $data['data'] = $machineData;
                $data['msg'] = 'View Successfully';
            } else {
                $data['success'] = 0;
                $data['data'] = [];
                $data['msg'] = 'No Data Found';
            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        return $data;
    }

    public function getPowerReport($input)
    {
        try {

            $plant_key = $input['plant_key'];
            $customer_key = $input['customer_key'];

            $to_date1 = date('Y-m-d');
            $from_date1 = date('Y-m-d', strtotime('-7 day', strtotime($to_date1)));

            $curdateyear = date('Y');
            $from_dateyear = date('Y', strtotime($from_date1));

            $machineData1 = [];
            $machineData2 = [];
            $type = $input['type'];

            if ($curdateyear == $from_dateyear) {

                $tableName = "zz_splife_" . $from_dateyear . "_" . $customer_key;

                if (DB::schema()->hasTable($tableName)) {

                    $machineData = DB::table($tableName)
                        ->leftjoin('machines', 'machines.machine_serial', '=', "$tableName.machine_serial")
                        ->leftjoin('cells', 'cells.cell_key', 'machines.cell_key')
                        ->leftjoin('plants', 'plants.plant_key', 'cells.plant_key')
                        ->where('plants.plant_key', '=', $plant_key)
                        ->where('machines.is_subscribed', '=', 1)
                        ->where('date', '>=', $from_date1)
                        ->where('date', '<=', $to_date1)
                        ->select("$tableName.date", "$tableName.total_kwh", 'plants.per_unit_rate', "$tableName.machine_serial")
                        ->get();

                }

            } else {

                $sparelife_table_from = "zz_splife_" . $from_dateyear . "_" . $customer_key;
                $sparelife_table_to = "zz_splife_" . $curdateyear . "_" . $customer_key;

                if ((DB::schema()->hasTable($sparelife_table_from)) && (DB::schema()->hasTable($sparelife_table_to))) {

                    $machineData1 = DB::table($sparelife_table_from)
                        ->leftjoin('machines', 'machines.machine_serial', '=', "$sparelife_table_from.machine_serial")
                        ->leftjoin('cells', 'cells.cell_key', 'machines.cell_key')
                        ->leftjoin('plants', 'plants.plant_key', 'cells.plant_key')
                        ->where('plants.plant_key', '=', $plant_key)
                        ->where('machines.is_subscribed', '=', 1)
                        ->where('date', '>=', $from_date1)
                        ->where('date', '<=', $to_date1)
                        ->select("$sparelife_table_from.date", "$sparelife_table_from.total_kwh", 'plants.per_unit_rate', "$sparelife_table_from.machine_serial")
                        ->get();

                    $machineData2 = DB::table($sparelife_table_to)
                        ->leftjoin('machines', 'machines.machine_serial', '=', "$sparelife_table_to.machine_serial")
                        ->leftjoin('cells', 'cells.cell_key', 'machines.cell_key')
                        ->leftjoin('plants', 'plants.plant_key', 'cells.plant_key')
                        ->where('plants.plant_key', '=', $plant_key)
                        ->where('machines.is_subscribed', '=', 1)
                        ->where('date', '>=', $from_date1)
                        ->where('date', '<=', $to_date1)
                        ->select("$sparelife_table_to.date", "$sparelife_table_to.total_kwh", 'plants.per_unit_rate', "$sparelife_table_to.machine_serial")
                        ->get();

                    $machineData = $machineData1->merge($machineData2);

                } else if (DB::schema()->hasTable($sparelife_table_from)) {

                    $machineData = DB::table($sparelife_table_from)
                        ->leftjoin('machines', 'machines.machine_serial', '=', "$sparelife_table_from.machine_serial")
                        ->leftjoin('cells', 'cells.cell_key', 'machines.cell_key')
                        ->leftjoin('plants', 'plants.plant_key', 'cells.plant_key')
                        ->where('plants.plant_key', '=', $plant_key)
                        ->where('machines.is_subscribed', '=', 1)
                        ->where('date', '>=', $from_date1)
                        ->where('date', '<=', $to_date1)
                        ->select("$sparelife_table_from.date", "$sparelife_table_from.total_kwh", 'plants.per_unit_rate', "$sparelife_table_from.machine_serial")
                        ->get();

                } else if (DB::schema()->hasTable($sparelife_table_to)) {
                    $machineData = DB::table($sparelife_table_to)
                        ->leftjoin('machines', 'machines.machine_serial', '=', "$sparelife_table_to.machine_serial")
                        ->leftjoin('cells', 'cells.cell_key', 'machines.cell_key')
                        ->leftjoin('plants', 'plants.plant_key', 'cells.plant_key')
                        ->where('plants.plant_key', '=', $plant_key)
                        ->where('machines.is_subscribed', '=', 1)
                        ->where('date', '>=', $from_date1)
                        ->where('date', '<=', $to_date1)
                        ->select("$sparelife_table_to.date", "$sparelife_table_to.total_kwh", 'plants.per_unit_rate', "$sparelife_table_to.machine_serial")
                        ->get();

                } else {
                    $machineData = array();
                }
            }
            $report = [];

            if ($machineData->count() > 0) {
                if ($type == 1) {
                    $report = $this->daywiseEnergyReport($machineData);

                }
                if ($type == 2) {
                    $report = $this->machinewiseEnergyReport($machineData);

                }
                $data['success'] = 1;
                $data['data'] = $report;
                $data['msg'] = 'Success';

            } else {
                $data['success'] = 0;
                $data['data'] = [];
                $data['msg'] = 'No Data Found';
            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        return $data;
    }
    public function daywiseEnergyReport($machineData)
    {
        try {
            $sumArray = [];
            foreach ($machineData as $resultkey => $resultValue) {

                $per_unit_rate = $resultValue->per_unit_rate;

                if (!isset($sumArray[$resultValue->date])) {
                    $sumArray[$resultValue->date] = $resultValue;
                } else {

                    $sumArray[$resultValue->date]->total_kwh += $resultValue->total_kwh;

                }

            }

            $sumArray = array_values(($sumArray));

            foreach ($sumArray as $value1) {

                $machinesData[] = [
                    'date' => $value1->date,
                    'total_kwh' => round($value1->total_kwh, 2),
                    'unit_rate' => round(($value1->total_kwh * $per_unit_rate), 2),

                ];

            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        return $machinesData;
    }
    public function machinewiseEnergyReport($machineData)
    {
        try {
            $sumArray = [];
            foreach ($machineData as $resultkey => $resultValue) {

                $per_unit_rate = $resultValue->per_unit_rate;

                if (!isset($sumArray[$resultValue->machine_serial])) {
                    $sumArray[$resultValue->machine_serial] = $resultValue;
                } else {

                    $sumArray[$resultValue->machine_serial]->total_kwh += $resultValue->total_kwh;

                }

            }

            $sumArray = array_values(($sumArray));

            foreach ($sumArray as $value1) {

                $machinesData[] = [
                    'machine_serial' => $value1->machine_serial,
                    'total_kwh' => round($value1->total_kwh, 2),
                    'unit_rate' => round(($value1->total_kwh * $per_unit_rate), 2),

                ];

            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        return $machinesData;
    }
    public function shiftCriteria($from_date, $to_date, $shift)
    {
        $from = date('Y-m-d', strtotime("-2 days", strtotime($from_date)));
        $to = date('Y-m-d', strtotime("+2 days", strtotime($to_date)));

        $res['time_cri'] = " time >= " . strtotime($from) . "000000000 AND time <= " . strtotime($to) . "000000000";
        $res['shift_cri'] = '';

        while (strtotime($from_date) <= strtotime($to_date)) {

            if ($res["shift_cri"] != "") {
                $res["shift_cri"] .= " OR ";
            }

            switch ($shift) {
                case "all":

                    $res["shift_cri"] .= " shift=~/" . date("d-m-y", strtotime($from_date)) . "*/  ";
                    break;
                default:

                    $res["shift_cri"] .= " shift= '" . date("d-m-y", strtotime($from_date)) . "-" . $shift . "' ";
            }

            $from_date = date("Y-m-d", strtotime("+1 days", strtotime($from_date)));
        }
        return $res;
    }

    public function operatorUtilization($input)
    {
        try {

            $to = date('Y-m-d');
            $from = date('Y-m-d', strtotime('-7 day', strtotime($to)));

            $in_criteria = $this->shiftCriteria($from, $to, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $type = $input['type'];

            $database = $this->ConfigServices->influx_iris_v2c();
            $customer_key = $input['customer_key'];
            $plant_key = $input['plant_key'];

            $machineData = DB::table('machines')
                ->leftjoin('cells', 'cells.cell_key', 'machines.cell_key')
                ->leftjoin('plants', 'plants.plant_key', 'cells.plant_key')
                ->leftjoin('customers', 'customers.customer_key', 'plants.customer_key')
                ->where('plants.plant_key', '=', $plant_key)
                ->where('customers.customer_key', '=', $customer_key)
                ->where('machines.is_subscribed', '=', 1)
                ->select('machines.machine_serial')
                ->get();

            $mec = '';

            if ($machineData->count() > 0) {
                foreach ($machineData as $machineValue) {

                    $mec .= " M=~/" . $machineValue->machine_serial . "*/ ";
                    $mec .= " OR ";
                }
                $allMachines = substr($mec, 0, -3);

                $operatordata = $database->query("SELECT * FROM operators WHERE V=1 AND ($allMachines) AND ($shifts) AND ($time)")->getPoints();
                $report = [];
                if (sizeof($operatordata) > 0) {

                    if ($type == 1) {
                        $report = $this->operatorwiseUtilization($operatordata, $customer_key);
                    }
                    if ($type == 2) {
                        $report = $this->operatorDaywiseUtilization($operatordata, $customer_key);
                    }

                    $data['success'] = 1;
                    $data['data'] = $report['data'];
                    $data['msg'] = 'Success';

                } else {
                    $data['success'] = 0;
                    $data['data'] = [];
                    $data['msg'] = 'No Data Found';
                }
            } else {
                $data['success'] = 0;
                $data['data'] = [];
                $data['msg'] = 'No Data Found';
            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        return $data;
    }

    public function operatorwiseUtilization($operatordata, $customer_key)
    {
        try {
            $sumArray = [];

            foreach ($operatordata as $operatorValue) {

                if (!isset($sumArray[$operatorValue['opid']])) {
                    $sumArray[$operatorValue['opid']] = $operatorValue;
                } else {

                    $sumArray[$operatorValue['opid']]['d'] += $operatorValue['d'];

                }

            }
            $sumArray = array_values($sumArray);

            foreach ($sumArray as $value1) {

                $userdata = DB::table('users')
                    ->leftjoin('customers', 'customers.customer_key', 'users.customer_key')
                    ->where('users.customer_key', '=', $customer_key)
                    ->where('users.user_key', '=', $value1['opid'])
                    ->select('users.user_key', 'users.per_hr_rate', 'users.username', 'users.user_code', 'users.first_name')
                    ->get();

                if ($userdata->count() > 0) {

                    $per_hr_rate = $userdata[0]->per_hr_rate;
                    $seconds = $per_hr_rate / 3600;

                    $operatorsdata[] = [
                        'opid' => $value1['opid'],
                        'username' => $userdata[0]->username,
                        'usercode' => $userdata[0]->user_code,
                        'first_name' => $userdata[0]->first_name,
                        'duration' => round($value1['d'], 2),
                        'per_hr_rate' => round(($value1['d'] * $seconds), 2),

                    ];

                    $data['success'] = 1;
                    $data['data'] = $operatorsdata;
                    $data['msg'] = 'Success';

                } else {
                    $data['success'] = 0;
                    $data['data'] = [];
                    $data['msg'] = 'No Data Found';
                }

            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = [];
            $data['msg'] = 'No Data Found';
        }
        return $data;
    }
    public function operatorDaywiseUtilization($operatordata, $customer_key)
    {
        try {
            $c = [];
            $sumArray = [];
            foreach ($operatordata as $operatorValue) {
                $shiftdate = substr($operatorValue['shift'], 0, -3);

                $dateformat = date_create_from_format('d-m-y', $shiftdate);
                $date = date_format($dateformat, 'Y-m-d');

                $userdata = DB::table('users')
                    ->leftjoin('customers', 'customers.customer_key', 'users.customer_key')
                    ->where('users.customer_key', '=', $customer_key)
                    ->where('users.user_key', '=', $operatorValue['opid'])
                    ->select('users.user_key', 'users.per_hr_rate')
                    ->get();

                if ($userdata->count() > 0) {

                    $per_hr_rate = $userdata[0]->per_hr_rate;
                    $seconds = $per_hr_rate / 3600;

                    $c[$date][$userdata[0]->user_key]['opid'] = $userdata[0]->user_key;
                    $c[$date][$userdata[0]->user_key]['per_hr_rate'] = $seconds;

                    $sumVal = $operatorValue['d'];

                    if (isset($c[$date][$userdata[0]->user_key]['du'])) {

                        $c[$date][$userdata[0]->user_key]['du'] += $sumVal;
                        $c[$date][$userdata[0]->user_key]['per_hr_rate'] += $c[$date][$userdata[0]->user_key]['du'] * $seconds;
                        $c[$date][$userdata[0]->user_key]['date'] = $date;

                    } else {
                        $c[$date][$userdata[0]->user_key]['du'] = $sumVal;
                        $c[$date][$userdata[0]->user_key]['per_hr_rate'] += $c[$date][$userdata[0]->user_key]['du'] * $seconds;
                        $c[$date][$userdata[0]->user_key]['date'] = $date;
                    }

                } else {
                    $data['success'] = 0;
                    $data['data'] = [];
                    $data['msg'] = 'No Data Found';
                }
            }

            $c1 = array_values($c);

            foreach ($c1 as $aarrayValue) {

                foreach ($aarrayValue as $arrValue) {

                    if (!isset($sumArray[$arrValue['date']])) {
                        $sumArray[$arrValue['date']] = $arrValue;

                    } else {

                        $sumArray[$arrValue['date']]['du'] += $arrValue['du'];
                        $sumArray[$arrValue['date']]['per_hr_rate'] += $arrValue['per_hr_rate'];
                    }

                }
            }

            $sumArray1 = array_values(($sumArray));

            foreach ($sumArray1 as $value1) {

                $operatorsdata[] = [
                    'date' => $value1['date'],
                    'duration' => round($value1['du'], 2),
                    'per_hr_rate' => round($value1['per_hr_rate'], 2),

                ];

                $data['success'] = 1;
                $data['data'] = $operatorsdata;
                $data['msg'] = 'Success';
            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        return $data;
    }
    public function machineUtilization($input)
    {
        try {

            $to = date('Y-m-d');
            $from = date('Y-m-d', strtotime('-7 day', strtotime($to)));

            $in_criteria = $this->shiftCriteria($from, $to, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $type = $input['type'];
            $sumArray = [];

            $database = $this->ConfigServices->influx_iris_v2c();
            $customer_key = $input['customer_key'];
            $plant_key = $input['plant_key'];

            $machineData = DB::table('machines')
                ->leftjoin('cells', 'cells.cell_key', 'machines.cell_key')
                ->leftjoin('plants', 'plants.plant_key', 'cells.plant_key')
                ->leftjoin('customers', 'customers.customer_key', 'plants.customer_key')
                ->where('plants.plant_key', '=', $plant_key)
                ->where('customers.customer_key', '=', $customer_key)
                ->where('machines.is_subscribed', '=', 1)
                ->select('machines.machine_serial')
                ->get();

            $mec = '';

            if ($machineData->count() > 0) {
                foreach ($machineData as $machineValue) {
                    $mec .= " M=~/" . $machineValue->machine_serial . "*/ ";
                    $mec .= " OR ";
                }
                $allMachines = substr($mec, 0, -3);

                $productionsdata = $database->query("SELECT * FROM productions WHERE V=1 AND ( $allMachines) AND ($shifts) AND ($time)")->getPoints();

                $macwiseData = [];
                if (sizeof($productionsdata) > 0) {
                    if ($type == 1) {
                        $macwiseData = $this->macWiseUtilization($productionsdata);
                    }if ($type == 2) {
                        $macwiseData = $this->macDaywiseUtilization($productionsdata);
                    }

                    $data['success'] = 1;
                    $data['data'] = $macwiseData['data'];
                    $data['msg'] = 'Success';

                } else {
                    $data['success'] = 0;
                    $data['data'] = [];
                    $data['msg'] = 'No Data Found';

                }

            } else {
                $data['success'] = 0;
                $data['data'] = [];
                $data['msg'] = 'No Data Found';

            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        return $data;
    }

    public function macWiseUtilization($productionsdata)
    {
        try {
            $sumArray = [];
            foreach ($productionsdata as $productionsValue) {
                $machine_serial = $productionsValue['M'];

                if (!isset($sumArray[$machine_serial])) {
                    $sumArray[$machine_serial] = $productionsValue;

                } else {

                    $sumArray[$machine_serial]['d'] += $productionsValue['d'];

                }

            }

            $sumArray1 = array_values($sumArray);

            foreach ($sumArray1 as $value1) {
                $machine_serial = $value1['M'];
                $machines_data = DB::table('machines')
                    ->where('machine_serial', '=', $machine_serial)
                    ->select('per_hr_rate')
                    ->get();

                if ($machines_data->count() > 0) {

                    $per_hr_rate = $machines_data[0]->per_hr_rate;
                    $seconds = $per_hr_rate / 3600;

                    $machinesConsolidateddata[] = [
                        'machine_serail' => $machine_serial,
                        'duration' => round($value1['d'], 2),
                        'per_hr_rate' => round(($value1['d'] * $seconds), 2),

                    ];

                    $data['success'] = 1;
                    $data['data'] = $machinesConsolidateddata;
                    $data['msg'] = 'Success';

                } else {
                    $data['success'] = 0;
                    $data['data'] = [];
                    $data['msg'] = 'No Data Found';
                }
            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        return $data;
    }
    public function macDaywiseUtilization($productionsdata)
    {
        try {
            $sumArray = [];
            $c = [];
            foreach ($productionsdata as $productionsValue) {
                $machine_serial = $productionsValue['M'];

                $shiftdate = substr($productionsValue['shift'], 0, -3);

                $dateformat = date_create_from_format('d-m-y', $shiftdate);
                $date = date_format($dateformat, 'Y-m-d');

                $machines_data = DB::table('machines')
                    ->where('machine_serial', '=', $machine_serial)
                    ->select('machine_serial', 'per_hr_rate')
                    ->get();

                if ($machines_data->count() > 0) {

                    $per_hr_rate = $machines_data[0]->per_hr_rate;
                    $seconds = $per_hr_rate / 3600;

                    $c[$date][$machines_data[0]->machine_serial]['machine_serial'] = $machines_data[0]->machine_serial;
                    $c[$date][$machines_data[0]->machine_serial]['per_hr_rate'] = $seconds;

                    $sumVal = $productionsValue['d'];

                    if (isset($c[$date][$machines_data[0]->machine_serial]['du'])) {

                        $c[$date][$machines_data[0]->machine_serial]['du'] += $sumVal;
                        $c[$date][$machines_data[0]->machine_serial]['per_hr_rate'] += $c[$date][$machines_data[0]->machine_serial]['du'] * $seconds;
                        $c[$date][$machines_data[0]->machine_serial]['date'] = $date;

                    } else {
                        $c[$date][$machines_data[0]->machine_serial]['du'] = $sumVal;
                        $c[$date][$machines_data[0]->machine_serial]['per_hr_rate'] += $c[$date][$machines_data[0]->machine_serial]['du'] * $seconds;
                        $c[$date][$machines_data[0]->machine_serial]['date'] = $date;
                    }

                } else {
                    $data['success'] = 0;
                    $data['data'] = [];
                    $data['msg'] = 'No Data Found';
                }

            }
            $c1 = array_values($c);

            foreach ($c1 as $aarrayValue) {
                foreach ($aarrayValue as $arrValue) {

                    if (!isset($sumArray[$arrValue['date']])) {
                        $sumArray[$arrValue['date']] = $arrValue;

                    } else {

                        $sumArray[$arrValue['date']]['du'] += $arrValue['du'];
                        $sumArray[$arrValue['date']]['per_hr_rate'] += $arrValue['per_hr_rate'];
                    }

                }
            }

            $sumArray1 = array_values(($sumArray));

            foreach ($sumArray1 as $value1) {

                $productionsConsolidatedata[] = [
                    'date' => $value1['date'],
                    'duration' => round($value1['du'], 2),
                    'per_hr_rate' => round($value1['per_hr_rate'], 2),

                ];

                $data['success'] = 1;
                $data['data'] = $productionsConsolidatedata;
                $data['msg'] = 'Success';
            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        return $data;
    }
    public function partsUtilization($input)
    {
        try {
            $to = date('Y-m-d');
            $from = date('Y-m-d', strtotime('-7 day', strtotime($to)));

            $in_criteria = $this->shiftCriteria($from, $to, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $type = $input['type'];

            $database = $this->ConfigServices->influx_iris_v2c();
            $customer_key = $input['customer_key'];
            $plant_key = $input['plant_key'];

            $machineData = DB::table('machines')
                ->leftjoin('cells', 'cells.cell_key', 'machines.cell_key')
                ->leftjoin('plants', 'plants.plant_key', 'cells.plant_key')
                ->leftjoin('customers', 'customers.customer_key', 'plants.customer_key')
                ->where('plants.plant_key', '=', $plant_key)
                ->where('customers.customer_key', '=', $customer_key)
                ->where('machines.is_subscribed', '=', 1)
                ->select('machines.machine_serial')
                ->get();

            $mec = '';

            if ($machineData->count() > 0) {
                foreach ($machineData as $machineValue) {
                    $mec .= " M=~/" . $machineValue->machine_serial . "*/ ";
                    $mec .= " OR ";
                }
                $allMachines = substr($mec, 0, -3);

                $productionsdata = $database->query("SELECT * FROM productions WHERE V=1 AND ( $allMachines) AND ($shifts) AND ($time)")->getPoints();

                $pertwiseData = [];
                if (sizeof($productionsdata) > 0) {
                    if ($type == 1) {
                        $pertwiseData = $this->partswiseUtilization($productionsdata);

                    }if ($type == 2) {
                        $pertwiseData = $this->partsDaywiseUtilization($productionsdata);

                    }

                    $data['success'] = 1;
                    $data['data'] = $pertwiseData['data'];
                    $data['msg'] = 'Success';

                } else {
                    $data['success'] = 0;
                    $data['data'] = [];
                    $data['msg'] = 'No Data Found';

                }

            } else {
                $data['success'] = 0;
                $data['data'] = [];
                $data['msg'] = 'No Data Found';

            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        return $data;
    }

    public function partswiseUtilization($productionsdata)
    {
        try {

            $c = [];
            foreach ($productionsdata as $productionsValue) {

                $setupname = preg_replace('/[\_\>\@\.\;]+/', ' ', $productionsValue['stno']);
                $partname = preg_replace('/[\_\>\@\.\;]+/', ' ', $productionsValue['ptno']);

                $setup_data = DB::table('setup_master')
                    ->leftjoin('parts_master', 'parts_master.part_key', 'setup_master.part_key')
                    ->where('parts_master.part_name', '=', $partname)
                    ->where('setup_master.setup_name', '=', $setupname)
                    ->select('parts_master.part_name', 'setup_master.setup_name', 'per_comp_rate')
                    ->get();

                if ($setup_data->count() > 0) {

                    $part_name = $setup_data[0]->part_name;
                    $per_comp_rate = $setup_data[0]->per_comp_rate;

                    $c[$part_name][$setup_data[0]->setup_name]['setup_name'] = $setup_data[0]->setup_name;
                    $c[$part_name][$setup_data[0]->setup_name]['per_comp_rate'] = $per_comp_rate;

                    $sumVal = $productionsValue['part_count'];

                    if (isset($c[$part_name][$setup_data[0]->setup_name]['part_count'])) {

                        $c[$part_name][$setup_data[0]->setup_name]['part_count'] += $sumVal;
                        $c[$part_name][$setup_data[0]->setup_name]['per_comp_rate'] = $c[$part_name][$setup_data[0]->setup_name]['part_count'] * $per_comp_rate;
                        $c[$part_name][$setup_data[0]->setup_name]['part_name'] = $part_name;

                    } else {
                        $c[$part_name][$setup_data[0]->setup_name]['part_count'] = $sumVal;
                        $c[$part_name][$setup_data[0]->setup_name]['per_comp_rate'] = $c[$part_name][$setup_data[0]->setup_name]['part_count'] * $per_comp_rate;
                        $c[$part_name][$setup_data[0]->setup_name]['part_name'] = $part_name;
                    }

                } else {
                    $data['success'] = 0;
                    $data['data'] = [];
                    $data['msg'] = 'No Data Found';
                }

            }

            $c1 = array_values($c);

            foreach ($c1 as $aarrayValue) {
                foreach ($aarrayValue as $arrValue) {

                    $setupConsolidatedata[] = [
                        'part_name' => $arrValue['part_name'],
                        'setup_name' => $arrValue['setup_name'],
                        'part_count' => round($arrValue['part_count'], 2),
                        'per_comp_rate' => round($arrValue['per_comp_rate'], 2),

                    ];
                    $data['success'] = 1;
                    $data['data'] = $setupConsolidatedata;
                    $data['msg'] = 'Success';
                }
            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        return $data;
    }
    public function partsDaywiseUtilization($productionsdata)
    {
        try {
            $sumArray = [];
            $c = [];
            foreach ($productionsdata as $productionsValue) {

                $setupname = preg_replace('/[\_\>\@\.\;]+/', ' ', $productionsValue['stno']);
                $partname = preg_replace('/[\_\>\@\.\;]+/', ' ', $productionsValue['ptno']);

                $shiftdate = substr($productionsValue['shift'], 0, -3);

                $dateformat = date_create_from_format('d-m-y', $shiftdate);
                $date = date_format($dateformat, 'Y-m-d');

                $setup_data = DB::table('setup_master')
                    ->leftjoin('parts_master', 'parts_master.part_key', 'setup_master.part_key')
                    ->where('parts_master.part_name', '=', $partname)
                    ->where('setup_master.setup_name', '=', $setupname)
                    ->select('parts_master.part_name', 'setup_master.setup_name', 'per_comp_rate')
                    ->get();

                if ($setup_data->count() > 0) {

                    $part_name = $setup_data[0]->part_name;
                    $per_comp_rate = $setup_data[0]->per_comp_rate;

                    $c[$date][$part_name][$setup_data[0]->setup_name]['setup_name'] = $setup_data[0]->setup_name;
                    $c[$date][$part_name][$setup_data[0]->setup_name]['per_comp_rate'] = $per_comp_rate;

                    $sumVal = $productionsValue['part_count'];

                    if (isset($c[$date][$part_name][$setup_data[0]->setup_name]['part_count'])) {

                        $c[$date][$part_name][$setup_data[0]->setup_name]['part_count'] += $sumVal;
                        $c[$date][$part_name][$setup_data[0]->setup_name]['per_comp_rate'] = $c[$date][$part_name][$setup_data[0]->setup_name]['part_count'] * $per_comp_rate;
                        $c[$date][$part_name][$setup_data[0]->setup_name]['part_name'] = $part_name;
                        $c[$date][$part_name][$setup_data[0]->setup_name]['date'] = $date;

                    } else {
                        $c[$date][$part_name][$setup_data[0]->setup_name]['part_count'] = $sumVal;
                        $c[$date][$part_name][$setup_data[0]->setup_name]['per_comp_rate'] = $c[$date][$part_name][$setup_data[0]->setup_name]['part_count'] * $per_comp_rate;
                        $c[$date][$part_name][$setup_data[0]->setup_name]['part_name'] = $part_name;
                        $c[$date][$part_name][$setup_data[0]->setup_name]['date'] = $date;
                    }

                } else {
                    $data['success'] = 0;
                    $data['data'] = [];
                    $data['msg'] = 'No Data Found';
                }

            }

            $c1 = array_values($c);

            foreach ($c1 as $aarrayValue) {
                foreach ($aarrayValue as $arrValue1) {
                    foreach ($arrValue1 as $arrValue) {

                        if (!isset($sumArray[$arrValue['date']])) {
                            $sumArray[$arrValue['date']] = $arrValue;

                        } else {

                            $sumArray[$arrValue['date']]['part_count'] += $arrValue['part_count'];
                            $sumArray[$arrValue['date']]['per_comp_rate'] += $arrValue['per_comp_rate'];
                        }

                    }

                }
            }
            $sumArray1 = array_values(($sumArray));

            foreach ($sumArray1 as $value1) {

                $setupConsolidatedata[] = [
                    'date' => $value1['date'],
                    'part_count' => round($value1['part_count'], 2),
                    'per_comp_rate' => round($value1['per_comp_rate'], 2),

                ];

                $data['success'] = 1;
                $data['data'] = $setupConsolidatedata;
                $data['msg'] = 'Success';
            }

        } catch (\Exception $e) {
            $data['success'] = 0;
            $data['data'] = $e->getMessage();
            $data['msg'] = 'No Data Found';
        }
        return $data;
    }

}
