<?php

namespace App\Services;

use App\Services\AppServices\ConfigServices;
use App\Services\AppServices\DBServices;
use DateTime;
use Illuminate\Database\Capsule\Manager as DB;

class MacDBReportServices
{

    private $dbObj;
    private $ConfigServices;
    public function __construct()
    {
        $this->dbObj = new DBServices();
        $this->ConfigServices = new ConfigServices();
    }

    public function convertEpoch($dateTime)
    {
        $date = new DateTime($dateTime);
        $v = date_format($date, 'U') . '000000000';
        return number_format($v, 0, '', '');
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

    public function getCount($machine_serial, $start_date)
    {
        try {
            $data['success'] = 1;
            $from = date('Y-m-d', strtotime($start_date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];

            $database = $this->ConfigServices->influx_iris_v2c();

            $vCondition = "V = '-2' OR V = 2 OR V = 4 OR V = 5 OR V = 6 OR V = 12 OR V = 13 OR V = '-3' OR V = 1";

            $d = "SELECT * from productions Where M='$machine_serial' AND ($vCondition)  AND ($shifts) AND ($time) ";

            $field_data = 0;
            $field_data = $database->query($d)->getPoints();

            $total_countCycling = 0;
            $total_durationCycling = 0;
            $total_countIdle = 0;
            $total_durationIdle = 0;

            for ($i = 0; $i < sizeof($field_data); $i++) {
                $sig_end = 0;
                if ($field_data[$i]['V'] == 0 || $field_data[$i]['V'] == 1) {
                    $total_durationCycling = $total_durationCycling + $field_data[$i]['d'];
                    $total_countCycling = $total_countCycling + $field_data[$i]['pc'];
                }

                if ($field_data[$i]['V'] == 2 || $field_data[$i]['V'] == 4 || $field_data[$i]['V'] == 5 || $field_data[$i]['V'] == 6 || $field_data[$i]['V'] == 12 || $field_data[$i]['V'] == 13 || $field_data[$i]['V'] == '-2' || $field_data[$i]['V'] == '-3') {
                    $total_durationIdle = $total_durationIdle + $field_data[$i]['d'];
                    $total_countIdle = $total_countIdle + $field_data[$i]['pc'];
                }
            }

            $obj = array(
                'totalCountCycling' => round($total_countCycling, 4),
                'totalDurationCycling' => $total_durationCycling,
                'totalCountIdle' => round($total_countIdle, 4),
                'totalDurationIdle' => $total_durationIdle,
            );
            $sizecycling = sizeof($field_data);

            $data['data'] = array($obj);
            if ($sizecycling > 0) {
                $data['success'] = 1;
                $data['msg'] = "Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No Data For Machine";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function getCyclingTotalCount($machine_serial, $start_date)
    {
        try {
            $data['success'] = 1;
            $from = date('Y-m-d', strtotime($start_date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();
            $pauses = $database->query("SELECT * from pauses Where M='$machine_serial' and ($shifts) AND ($time)")->getPoints();
            $productions = $database->query("SELECT * from productions Where M='$machine_serial' and V = 1 and ($shifts) AND ($time)")->getPoints();
            $total_countPause = 0;
            $total_durationPause = 0;
            $total_countCycling = 0;
            $total_durationCycling = 0;
            if (sizeof($pauses) > 0) {
                foreach ($pauses as $i => $value) {
                    $total_durationPause = $total_durationPause + $pauses[$i]['d'];
                    $total_countPause = $total_countPause + $pauses[$i]['pc'];
                }
            }
            if (sizeof($productions) > 0) {
                foreach ($productions as $i => $value) {
                    $total_durationCycling = $total_durationCycling + $productions[$i]['d'];
                    $total_countCycling = $total_countCycling + $productions[$i]['pc'];
                }
            }
            $obj = array(
                'totalCountCycling' => round($total_countCycling, 4),
                'totalDurationCycling' => $total_durationCycling,
                'totalCountPause' => round($total_countPause, 4),
                'totalDurationPause' => $total_durationPause,
            );

            $sizepause = sizeof($pauses);

            $data['data'] = array($obj);

            if ($sizepause > 0 || sizeof($productions) > 0) {
                $data['success'] = 1;
                $data['msg'] = " Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No Data For Machine";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function getIdleCount($machine_serial, $start_date)
    {
        try {
            $data['success'] = 1;
            $from = date('Y-m-d', strtotime($start_date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];

            $database = $this->ConfigServices->influx_iris_v2c();

            $vCondition = "V = '-2' OR V = 2 OR V = 4 OR V = 5 OR V = 6 OR V = 12 OR V = 13 OR V = '-3' OR V = 1";

            $d = "SELECT * from  productions Where M='$machine_serial' and ($vCondition) and ($shifts) AND ($time)";

            $power_data = $database->query($d)->getPoints();

            $total_countPowerOffIdle = 0;
            $total_durationPowerOffIdle = 0;
            $total_countBreakIdle = 0;
            $total_durationBreakIdle = 0;
            $total_countSetIdle = 0;
            $total_durationSetIdle = 0;
            $total_countUndterminedIdle = 0;
            $total_durationUndeterminedIdle = 0;
            $total_countCyclingIdle = 0;
            $total_durationCyclingIdle = 0;
            $total_countNoPlanIdle = 0;
            $total_durationNoPlanIdle = 0;
            $total_countLoadAndUnloadIdle = 0;
            $total_durationLoadAndUnloadIdle = 0;
            $total_countIdlePopOffIdle = 0;
            $total_durationIdlePopOffIdle = 0;

            for ($i = 0; $i < sizeof($power_data); $i++) {
                $sig_end = 0;
                if ($power_data[$i]['V'] == '-2') {
                    $total_durationPowerOffIdle = $total_durationPowerOffIdle + $power_data[$i]['d'];
                    $total_countPowerOffIdle = $total_countPowerOffIdle + $power_data[$i]['pc'];
                }

                if ($power_data[$i]['V'] == '-3') {
                    $total_durationUndeterminedIdle = $total_durationUndeterminedIdle + $power_data[$i]['d'];
                    $total_countUndterminedIdle = $total_countUndterminedIdle + $power_data[$i]['pc'];
                }

                if ($power_data[$i]['V'] == 2) {
                    $total_durationCyclingIdle = $total_durationCyclingIdle + $power_data[$i]['d'];
                    $total_countCyclingIdle = $total_countCyclingIdle + $power_data[$i]['pc'];
                }

                if ($power_data[$i]['V'] == 4) {
                    $total_durationNoPlanIdle = $total_durationNoPlanIdle + $power_data[$i]['d'];
                    $total_countNoPlanIdle = $total_countNoPlanIdle + $power_data[$i]['pc'];
                }

                if ($power_data[$i]['V'] == 5) {
                    $total_durationLoadAndUnloadIdle = $total_durationLoadAndUnloadIdle + $power_data[$i]['d'];
                    $total_countLoadAndUnloadIdle = $total_countLoadAndUnloadIdle + $power_data[$i]['pc'];
                }

                if ($power_data[$i]['V'] == 12) {
                    $total_durationBreakIdle = $total_durationBreakIdle + $power_data[$i]['d'];
                    $total_countBreakIdle = $total_countBreakIdle + $power_data[$i]['pc'];
                }

                if ($power_data[$i]['V'] == 13) {
                    $total_durationSetIdle = $total_durationSetIdle + $power_data[$i]['d'];
                    $total_countSetIdle = $total_countSetIdle + $power_data[$i]['pc'];
                }

                if ($power_data[$i]['V'] == 6) {
                    $total_durationIdlePopOffIdle = $total_durationIdlePopOffIdle + $power_data[$i]['d'];
                    $total_countIdlePopOffIdle = $total_countIdlePopOffIdle + $power_data[$i]['pc'];
                }
            }

            $obj = array(
                'totalCountPowerOff' => round($total_countPowerOffIdle, 4),
                'totalDurationPowerOff' => $total_durationPowerOffIdle,
                'totalCountBreakIdle' => round($total_countBreakIdle, 4),
                'totalDurationBreakIdle' => $total_durationBreakIdle,
                'totalCountSetIdle' => round($total_countSetIdle, 4),
                'totalDurationSetIdle' => $total_durationSetIdle,
                'totalCountUndeterminedIdle' => round($total_countUndterminedIdle, 4),
                'totalDurationUndeterminedIdle' => $total_durationUndeterminedIdle,
                'totalCountCyclingIdle' => round($total_countCyclingIdle, 4),
                'totalDurationCyclingIdle' => $total_durationCyclingIdle,
                'totalCountNoPlanIdle' => round($total_countNoPlanIdle, 4),
                'totalDurationNoPlanIdle' => $total_durationNoPlanIdle,
                'totalCountLoadAndUnloadIdle' => round($total_countLoadAndUnloadIdle, 4),
                'totalDurationLoadAndUnloadIdle' => $total_durationLoadAndUnloadIdle,
                'totalCountIdlePopOffIdle' => round($total_countIdlePopOffIdle, 4),
                'totalDurationIdlePopOffIdle' => $total_durationIdlePopOffIdle,
            );

            $sizepower = sizeof($power_data);

            $data['data'] = array($obj);

            if ($sizepower > 0) {
                $data['success'] = 1;
                $data['msg'] = "Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No Data For Machine";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function getPauseCountPid($machine_serial, $start_date)
    {
        try {
            $data['success'] = 1;

            $from = date('Y-m-d', strtotime($start_date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();
            $c = "SELECT count(d),sum(d) FROM pauses WHERE M= '$machine_serial' AND ($shifts) AND ($time) group by Des ";
            $a = "SELECT * FROM pauses WHERE M= '$machine_serial' AND ($shifts) AND ($time) group by Des limit 1";
            $total_count = 0;
            $total_duration = 0;
            $total_data = $database->query($c)->getPoints();
            $field_series = $database->query($a)->getSeries();
            $field_points = $database->query($a)->getPoints();
            $final = [];
            $count = 0;
            foreach ($field_points as $key => $value) {
                $temp = [];
                $temp["Des"] = $field_series[$count]['tags']['Des'];
                $temp['start'] = $value['s'];
                $temp['end'] = $value['e'];
                $temp["totalCount"] = $field_points[$count]['pc'];
                $temp["totalDuration"] = $total_data[$count]['sum'];
                array_push($final, $temp);
                $count = $count + 1;
            }

            $data['data'] = $final;

            $size = sizeof($field_series);
            if ($size > 0) {
                $data['success'] = 1;
                $data['msg'] = "pause Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No pause For Machine";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function getAllCountPid($machine_serial, $start_date)
    {
        try {
            $data['success'] = 1;
            $from = date('Y-m-d', strtotime($start_date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();
            $vCondition = "V = '-2' OR V = 2 OR V = 4 OR V = 5 OR V = 6 OR V = 12 OR V = 13 OR V = '-3' OR V = 1";
            $i = "SELECT s,V,e,d,pc from productions Where M='$machine_serial'  and ($vCondition) and ($shifts) AND ($time) ";
            $total_count = 0;
            $total_duration = 0;
            $count = 0;
            $final = [];

            $field_points = $database->query($i)->getPoints();

            $res = [];

            foreach ($field_points as $key => $value) {

                $res['start'] = $value['s'];
                $res['end'] = $value['e'];
                $res['value'] = $value['V'];
                $res["totalCount"] = $value['pc'];
                $res["totalDuration"] = $value['d'];
                array_push($final, $res);
            }

            $data['overall'] = $final;
            $a = "SELECT s,V,e,d,pc FROM pauses WHERE M= '$machine_serial' AND ($shifts)  AND ($time) ";
            $total_count1 = 0;
            $total_duration1 = 0;

            $field_points1 = $database->query($a)->getPoints();
            $final1 = [];
            $count = 0;

            foreach ($field_points1 as $key => $value) {
                $temp = [];
                $temp['start1'] = $value['s'];
                $temp['end1'] = $value['e'];
                $temp['value'] = $value['V'];
                $temp["totalCount1"] = $value['pc'];
                $temp["totalDuration"] = $value['d'];
                array_push($final1, $temp);
            }

            $data['pause'] = $final1;

            $size = sizeof($field_points);
            $size1 = sizeof($field_points1);

            if ($size > 0 || $size1 > 0) {
                $data['success'] = 1;
                $data['msg'] = "overalll Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No overall For Machine";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function detailsonmacwise($machine_serial, $start_date)
    {
        try {

            $data['success'] = 1;
            $from = date('Y-m-d', strtotime($start_date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();
            $total = "SELECT * from almstoppages Where M='$machine_serial' and ($shifts) and ($time)";
            $alarms = $database->query($total)->getPoints();
            $machine_not_in_alarm_duration = 0;
            $machine_not_in_alarm_count = 0;
            $machine_in_alarm_duration = 0;
            $machine_in_alarm_count = 0;
            $poweroff_duration = 0;
            $poweroff_count = 0;
            $undetermined_duration = 0;
            $undetermined_count = 0;

            for ($i = 0; $i < sizeof($alarms); $i++) {
                $sig_end = 0;
                if ($alarms[$i]['V'] == '0') {
                    $machine_not_in_alarm_duration = $machine_not_in_alarm_duration + $alarms[$i]['d'];
                    $machine_not_in_alarm_count = $machine_not_in_alarm_count + $alarms[$i]['pc'];
                }

                if ($alarms[$i]['V'] == '1') {
                    $machine_in_alarm_duration = $machine_in_alarm_duration + $alarms[$i]['d'];
                    $machine_in_alarm_count = $machine_in_alarm_count + $alarms[$i]['pc'];
                }

                if ($alarms[$i]['V'] == '-2') {
                    $poweroff_duration = $poweroff_duration + $alarms[$i]['d'];
                    $poweroff_count = $poweroff_count + $alarms[$i]['pc'];
                }

                if ($alarms[$i]['V'] == '-3') {
                    $undetermined_duration = $undetermined_duration + $alarms[$i]['d'];
                    $undetermined_count = $undetermined_count + $alarms[$i]['pc'];
                }
            }

            $obj = array(
                'machine_not_in_alarm_duration' => $machine_not_in_alarm_duration,
                'machine_in_alarm_duration' => $machine_in_alarm_duration,
                'poweroff_duration' => $poweroff_duration,
                'undetermined_duration' => $undetermined_duration,
            );

            $size = sizeof($alarms);

            $data['data'] = array($obj);

            if ($size > 0) {
                $data['success'] = 1;
                $data['msg'] = "Alarm Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No Alarm For Plant";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = $e->getMessage();
            $data['data'] = "plants Fetching Failed";
        }
        return $data;
    }

    public function oeeCountDayMachineWise($machine_serial, $from_date)
    {
        $data['success'] = 1;
        $end = date('d-m-y', strtotime($from_date)) . '-S';

        $customers = DB::table('machines')
            ->select('machines.customer_key')
            ->where('machines.machine_serial', $machine_serial)
            ->get();

        if ($customers->count() > 0) {
            $customer_key = $customers[0]->customer_key;
        } else {
            $customer_key = "";
        }

        $date = date('y-m-d', strtotime($from_date));
        $year = substr($date, 0, 2);

        $table = "yn_oee_" . "20" . "$year" . "_" . "$customer_key";

        $total = array(
            "count" => 0,
            "availability" => 0,
            "quality_percentage" => 0,
            "performance" => 0,
            "oee" => 0,
        );

        $total_final = [];
        try {
            if (DB::schema()->hasTable($table)) {

                $total_data = DB::table($table)
                    ->leftJoin("machines", "machines.machine_key", '=', "$table.machine_key")
                    ->where("machines.machine_serial", $machine_serial)
                    ->where("$table.shift_name", 'LIKE', '%' . $end . '%')
                    ->where("$table.oee", '!=', 'NULL')
                    ->where("$table.oee", '!=', 0)
                    ->get();

                if (sizeof($total_data) > 0) {
                    $total['count'] = sizeof($total_data);

                    foreach ($total_data as $rep) {
                        $total['availability'] += $rep->availability;
                        $total['quality_percentage'] += $rep->quality_percentage;
                        $total['performance'] += $rep->performance;
                        $total['oee'] += $rep->oee;
                    }
                    $total_final = [

                        'availability' => round($total['availability'] / $total['count'], 2),
                        'quality_percentage' => round($total['quality_percentage'] / $total['count'], 2),
                        'performance' => round($total['performance'] / $total['count'], 2),
                        'oee' => round($total['oee'] / $total['count'], 2),
                    ];
                    $data['success'] = 1;
                    $data['data'] = [$total_final];
                    $data['msg'] = "success";
                } else {
                    $data['data'] = [];
                    $data['success'] = 0;
                    $data['msg'] = "no data";
                }

            } else {
                $data['data'] = [];
                $data['success'] = 0;
                $data['msg'] = "no data";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "availabilityOnmserialRetrieveFail";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function getCountAcs($machine_serial, $start_date)
    {
        try {
            $data['success'] = 1;
            $from = date('Y-m-d', strtotime($start_date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();
            $i = "SELECT * from acs Where M='$machine_serial' and ($shifts) and ($time)";
            $acs = $database->query($i)->getPoints();
            $non_auto_idle_duration = 0;
            $non_auto_idle_count = 0;
            $non_auto_cycle_duration = 0;
            $non_auto_cycle_count = 0;
            $auto_idle_duration = 0;
            $auto_idle_count = 0;
            $auto_cycle_duration = 0;
            $auto_cycle_count = 0;
            $poweroff_duration = 0;
            $poweroff_count = 0;
            $undetermined_duration = 0;
            $undetermined_count = 0;

            for ($i = 0; $i < sizeof($acs); $i++) {
                $sig_end = 0;
                if ($acs[$i]['V'] == '0') {
                    $non_auto_idle_duration = $non_auto_idle_duration + $acs[$i]['d'];
                    $non_auto_idle_count = $non_auto_idle_count + $acs[$i]['pc'];
                }

                if ($acs[$i]['V'] == '1') {
                    $non_auto_cycle_duration = $non_auto_cycle_duration + $acs[$i]['d'];
                    $non_auto_cycle_count = $non_auto_cycle_count + $acs[$i]['pc'];
                }

                if ($acs[$i]['V'] == '2') {
                    $auto_idle_duration = $auto_idle_duration + $acs[$i]['d'];
                    $auto_idle_count = $auto_idle_count + $acs[$i]['pc'];
                }

                if ($acs[$i]['V'] == '3') {
                    $auto_cycle_duration = $auto_cycle_duration + $acs[$i]['d'];
                    $auto_cycle_count = $auto_cycle_count + $acs[$i]['pc'];
                }

                if ($acs[$i]['V'] == '-2') {
                    $poweroff_duration = $poweroff_duration + $acs[$i]['d'];
                    $poweroff_count = $poweroff_count + $acs[$i]['pc'];
                }

                if ($acs[$i]['V'] == '-3') {
                    $undetermined_duration = $undetermined_duration + $acs[$i]['d'];
                    $undetermined_count = $undetermined_count + $acs[$i]['pc'];
                }
            }

            $obj = array(
                'non_auto_idle_duration' => $non_auto_idle_duration,
                'non_auto_cycle_duration' => $non_auto_cycle_duration,
                'auto_idle_duration' => $auto_idle_duration,
                'auto_cycle_duration' => $auto_cycle_duration,
                'poweroff_duration' => $poweroff_duration,
                'undetermined_duration' => $undetermined_duration,
            );

            $size = sizeof($acs);

            $data['data'] = array($obj);

            if ($size > 0) {
                $data['success'] = 1;
                $data['msg'] = "total Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No total For Machine";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function ovrsCountDayMachhinetWise($machine_serial, $start_date)
    {
        try {

            $data['success'] = 1;
            $from = date('Y-m-d', strtotime($start_date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();
            $i = "SELECT * from ovrs Where M='$machine_serial' and ($shifts) and ($time)";
            $ovr = $database->query($i)->getPoints();

            $feedlhundred_duration = 0;
            $feedlhundred_count = 0;
            $undetermined_duration = 0;
            $undetermined_count = 0;
            $feedehundred_duration = 0;
            $feedehundred_count = 0;
            $feedghundred_duration = 0;
            $feedghundred_count = 0;
            $poweroff_duration = 0;
            $poweroff_count = 0;

            for ($i = 0; $i < sizeof($ovr); $i++) {
                $sig_end = 0;
                if ($ovr[$i]['V'] == '-1') {
                    $feedlhundred_duration = $feedlhundred_duration + $ovr[$i]['d'];
                    $feedlhundred_count = $feedlhundred_count + 1;
                }

                if ($ovr[$i]['V'] == '-3') {
                    $undetermined_duration = $undetermined_duration + $ovr[$i]['d'];
                    $undetermined_count = $undetermined_count + 1;
                }

                if ($ovr[$i]['V'] == 0) {
                    $feedehundred_duration = $feedehundred_duration + $ovr[$i]['d'];
                    $feedehundred_count = $feedehundred_count + 1;
                }

                if ($ovr[$i]['V'] == 1) {
                    $feedghundred_duration = $feedghundred_duration + $ovr[$i]['d'];
                    $feedghundred_count = $feedghundred_count + 1;
                }

                if ($ovr[$i]['V'] == -2) {
                    $poweroff_duration = $poweroff_duration + $ovr[$i]['d'];
                    $poweroff_count = $poweroff_count + 1;
                }
            }

            $obj = array(
                'feedlhundred_duration' => $feedlhundred_duration,
                'feedehundred_duration' => $feedehundred_duration,
                'feedghundred_duration' => $feedghundred_duration,
                'poweroff_duration' => $poweroff_duration,
                'undetermined_duration' => $undetermined_duration,
            );

            $size = sizeof($ovr);

            $data['data'] = array($obj);

            if ($size > 0) {
                $data['success'] = 1;
                $data['msg'] = "total Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No total For Machine";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function getDetails($machine_serial, $date, $v)
    {
        try {

            $data['success'] = 1;
            $from = date('Y-m-d', strtotime($date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();

            if ($v == 'pauses') {
                $i = "SELECT * from pauses Where M ='$machine_serial' and ($shifts) and ($time)";
            } else {
                $i = "SELECT * from productions Where V=$v and M ='$machine_serial' and ($shifts) and ($time)";
            }

            $data1 = $database->query($i)->getPoints();
            $res2 = [];
            foreach ($data1 as $res) {
                $res1 = (array) $res;
                $res1['time'] = date("Y-m-d H:i:s", strtotime($res['time']));
                $res1['e'] = date("Y-m-d H:i:s", substr($res['e'], 0, 10));
                $res1['s'] = date("Y-m-d H:i:s", substr($res['s'], 0, 10));
                array_push($res2, $res1);
            }

            $data['data'] = $res2;

            $size = sizeof($data['data']);

            if ($size > 0) {
                $data['success'] = 1;
                $data['msg'] = "total Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No data For Machine";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }
    public function getAlramDetails($machine_serial, $start_date)
    {
        try {
            $data['success'] = 1;
            $from = date('Y-m-d', strtotime($start_date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();
            $total = "SELECT * from almstoppages Where M='$machine_serial' and ($shifts) and ($time)";
            $alarms = $database->query($total)->getPoints();
            $total1 = [];
            $total2 = [];
            $total3 = [];
            $total4 = [];
            if (sizeof($alarms) > 0) {
                foreach ($alarms as $res) {

                    $res['s'] = date("Y-m-d H:i:s", substr($res['s'], 0, 10));
                    $res['e'] = date("Y-m-d H:i:s", substr($res['e'], 0, 10));

                    if ($res['V'] == '0') {
                        array_push($total1, $res);
                    }

                    if ($res['V'] == '1') {
                        array_push($total2, $res);
                    }

                    if ($res['V'] == '-2') {
                        array_push($total3, $res);
                    }

                    if ($res['V'] == '-3') {
                        array_push($total4, $res);
                    }
                }
            }
            $data['machine_not_in_alarm_duration'] = $total1;
            $data['machine_in_alarm_duration'] = $total2;
            $data['poweroff_duration'] = $total3;
            $data['undetermined_duration'] = $total4;
            $size = sizeof($data);
            if ($size > 0) {
                $data['success'] = 1;
                $data['msg'] = "Alarm Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No Alarm For Plant";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = $e->getMessage();
            $data['data'] = "plants Fetching Failed";
        }
        return $data;
    }

    public function getAcsDetails($machine_serial, $start_date)
    {
        try {
            $data['success'] = 1;
            $from = date('Y-m-d', strtotime($start_date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();
            $i = "SELECT * from acs Where M='$machine_serial' and ($shifts) and ($time)";
            $acs = $database->query($i)->getPoints();
            $total1 = [];
            $total2 = [];
            $total3 = [];
            $total4 = [];
            $total5 = [];
            $total6 = [];

            if (sizeof($acs) > 0) {
                foreach ($acs as $res) {

                    $res['s'] = date("Y-m-d H:i:s", substr($res['s'], 0, 10));
                    $res['e'] = date("Y-m-d H:i:s", substr($res['e'], 0, 10));

                    if ($res['V'] == '0') {
                        array_push($total1, $res);
                    }

                    if ($res['V'] == '1') {
                        array_push($total2, $res);
                    }

                    if ($res['V'] == '2') {
                        array_push($total3, $res);
                    }

                    if ($res['V'] == '3') {
                        array_push($total4, $res);
                    }

                    if ($res['V'] == '-2') {
                        array_push($total5, $res);
                    }

                    if ($res['V'] == '-3') {
                        array_push($total6, $res);
                    }
                }
            }
            $data['non_auto_idle_duration'] = $total1;
            $data['non_auto_cycle_duration'] = $total2;
            $data['auto_idle_duration'] = $total3;
            $data['auto_cycle_duration'] = $total4;
            $data['poweroff_duration'] = $total5;
            $data['undetermined_duration'] = $total6;

            $size = sizeof($data);

            if ($size > 0) {
                $data['success'] = 1;
                $data['msg'] = "total Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No total For Machine";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function getOvrDetails($machine_serial, $start_date)
    {
        try {

            $data['success'] = 1;
            $from = date('Y-m-d', strtotime($start_date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();
            $i = "SELECT * from ovrs Where M='$machine_serial' and ($shifts) and ($time)";
            $ovr = $database->query($i)->getPoints();

            $total1 = [];
            $total2 = [];
            $total3 = [];
            $total4 = [];
            $total5 = [];
            $total6 = [];

            if (sizeof($ovr) > 0) {
                foreach ($ovr as $res) {

                    $res['s'] = date("Y-m-d H:i:s", substr($res['s'], 0, 10));
                    $res['e'] = date("Y-m-d H:i:s", substr($res['e'], 0, 10));

                    if ($res['V'] == '-1') {
                        array_push($total1, $res);
                    }

                    if ($res['V'] == '-3') {
                        array_push($total2, $res);
                    }

                    if ($res['V'] == '0') {
                        array_push($total3, $res);
                    }

                    if ($res['V'] == '1') {
                        array_push($total4, $res);
                    }

                    if ($res['V'] == '-2') {
                        array_push($total5, $res);
                    }
                }
            }
            $data['feedlhundred_duration'] = $total1;
            $data['undetermined_duration'] = $total2;
            $data['feedehundred_duration'] = $total3;
            $data['feedghundred_duration'] = $total4;
            $data['poweroff_duration'] = $total5;

            $size = sizeof($data);
            if ($size > 0) {
                $data['success'] = 1;
                $data['msg'] = "total Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No total For Machine";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    // cell level overview data
    public function cellProduction($cell, $date)
    {
        try {
            $from = date('Y-m-d', strtotime($date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];

            $database = $this->ConfigServices->influx_iris_v2c();

            $vCondition = "V = '-2' OR V = 2 OR V = 4 OR V = 5 OR V = 6 OR V = 12 OR V = 13 OR V = '-3' OR V = 1";

            $macList = DB::table('machines')
                ->select('machines.machine_serial')
                ->where('machines.is_subscribed', 1)
                ->where('machines.cell_key', $cell)
                ->get();

            $finalArr = array();

            $total_countCycling = 0;
            $total_durationCycling = 0;
            $total_countIdle = 0;
            $total_durationIdle = 0;
            foreach ($macList as $val) {
                $d = "SELECT * from productions Where M='$val->machine_serial' AND ($vCondition) AND ($shifts) AND ($time) ";

                $field_data = 0;
                $field_data = $database->query($d)->getPoints();

                for ($i = 0; $i < sizeof($field_data); $i++) {
                    $sig_end = 0;
                    if ($field_data[$i]['V'] == 0 || $field_data[$i]['V'] == 1) {
                        $total_durationCycling = $total_durationCycling + $field_data[$i]['d'];
                        $total_countCycling = $total_countCycling + $field_data[$i]['pc'];
                    }

                    if ($field_data[$i]['V'] == 2 || $field_data[$i]['V'] == 4 || $field_data[$i]['V'] == 5 || $field_data[$i]['V'] == 6 || $field_data[$i]['V'] == 12 || $field_data[$i]['V'] == 13 || $field_data[$i]['V'] == '-2' || $field_data[$i]['V'] == '-3') {
                        $total_durationIdle = $total_durationIdle + $field_data[$i]['d'];
                        $total_countIdle = $total_countIdle + $field_data[$i]['pc'];
                    }
                }

                $finalArr = array(
                    'totalCountCycling' => round($total_countCycling, 4),
                    'totalDurationCycling' => $total_durationCycling,
                    'totalCountIdle' => round($total_countIdle, 4),
                    'totalDurationIdle' => $total_durationIdle,
                );
            }

            if (sizeof($finalArr) > 0) {
                $data['success'] = 1;
                $data['data'] = array($finalArr);
                $data['msg'] = "Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No Prodction For This Cell";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function cellAlarm($cell, $date)
    {
        try {
            $from = date('Y-m-d', strtotime($date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();

            $machine_not_in_alarm_duration = 0;
            $machine_not_in_alarm_count = 0;
            $machine_in_alarm_duration = 0;
            $machine_in_alarm_count = 0;
            $poweroff_duration = 0;
            $poweroff_count = 0;
            $undetermined_duration = 0;
            $undetermined_count = 0;

            $macList = DB::table('machines')
                ->select('machines.machine_serial')
                ->where('machines.is_subscribed', 1)
                ->where('machines.cell_key', $cell)
                ->get();

            $finalArr = array();

            foreach ($macList as $val) {

                $total = "SELECT * from almstoppages Where M='$val->machine_serial' and ($shifts) and ($time)";
                $alarms = $database->query($total)->getPoints();

                for ($i = 0; $i < sizeof($alarms); $i++) {
                    $sig_end = 0;
                    if ($alarms[$i]['V'] == '0') {
                        $machine_not_in_alarm_duration = $machine_not_in_alarm_duration + $alarms[$i]['d'];
                        $machine_not_in_alarm_count = $machine_not_in_alarm_count + $alarms[$i]['pc'];
                    }

                    if ($alarms[$i]['V'] == '1') {
                        $machine_in_alarm_duration = $machine_in_alarm_duration + $alarms[$i]['d'];
                        $machine_in_alarm_count = $machine_in_alarm_count + $alarms[$i]['pc'];
                    }

                    if ($alarms[$i]['V'] == '-2') {
                        $poweroff_duration = $poweroff_duration + $alarms[$i]['d'];
                        $poweroff_count = $poweroff_count + $alarms[$i]['pc'];
                    }

                    if ($alarms[$i]['V'] == '-3') {
                        $undetermined_duration = $undetermined_duration + $alarms[$i]['d'];
                        $undetermined_count = $undetermined_count + $alarms[$i]['pc'];
                    }
                }

                $finalArr = array(
                    'machine_not_in_alarm_duration' => $machine_not_in_alarm_duration,
                    'machine_in_alarm_duration' => $machine_in_alarm_duration,
                    'poweroff_duration' => $poweroff_duration,
                    'undetermined_duration' => $undetermined_duration,
                );
            }

            if (sizeof($finalArr) > 0) {
                $data['success'] = 1;
                $data['data'] = array($finalArr);
                $data['msg'] = "Alarm Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No Alarm For This Cell";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = $e->getMessage();
            $data['data'] = "Fetching Failed";
        }
        return $data;
    }

    public function cellAcs($cell, $date)
    {
        try {

            $from = date('Y-m-d', strtotime($date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();

            $macList = DB::table('machines')
                ->select('machines.machine_serial')
                ->where('machines.is_subscribed', 1)
                ->where('machines.cell_key', $cell)
                ->get();

            $finalArr = array();
            $non_auto_idle_duration = 0;
            $non_auto_idle_count = 0;
            $non_auto_cycle_duration = 0;
            $non_auto_cycle_count = 0;
            $auto_idle_duration = 0;
            $auto_idle_count = 0;
            $auto_cycle_duration = 0;
            $auto_cycle_count = 0;
            $poweroff_duration = 0;
            $poweroff_count = 0;
            $undetermined_duration = 0;
            $undetermined_count = 0;

            foreach ($macList as $val) {
                $i = "SELECT * from acs Where M='$val->machine_serial' and ($shifts) and ($time)";
                $acs = $database->query($i)->getPoints();

                for ($i = 0; $i < sizeof($acs); $i++) {
                    $sig_end = 0;
                    if ($acs[$i]['V'] == '0') {
                        $non_auto_idle_duration = $non_auto_idle_duration + $acs[$i]['d'];
                        $non_auto_idle_count = $non_auto_idle_count + $acs[$i]['pc'];
                    }

                    if ($acs[$i]['V'] == '1') {
                        $non_auto_cycle_duration = $non_auto_cycle_duration + $acs[$i]['d'];
                        $non_auto_cycle_count = $non_auto_cycle_count + $acs[$i]['pc'];
                    }

                    if ($acs[$i]['V'] == '2') {
                        $auto_idle_duration = $auto_idle_duration + $acs[$i]['d'];
                        $auto_idle_count = $auto_idle_count + $acs[$i]['pc'];
                    }

                    if ($acs[$i]['V'] == '3') {
                        $auto_cycle_duration = $auto_cycle_duration + $acs[$i]['d'];
                        $auto_cycle_count = $auto_cycle_count + $acs[$i]['pc'];
                    }

                    if ($acs[$i]['V'] == '-2') {
                        $poweroff_duration = $poweroff_duration + $acs[$i]['d'];
                        $poweroff_count = $poweroff_count + $acs[$i]['pc'];
                    }

                    if ($acs[$i]['V'] == '-3') {
                        $undetermined_duration = $undetermined_duration + $acs[$i]['d'];
                        $undetermined_count = $undetermined_count + $acs[$i]['pc'];
                    }
                }

                $finalArr = array(
                    'non_auto_idle_duration' => $non_auto_idle_duration,
                    'non_auto_cycle_duration' => $non_auto_cycle_duration,
                    'auto_idle_duration' => $auto_idle_duration,
                    'auto_cycle_duration' => $auto_cycle_duration,
                    'poweroff_duration' => $poweroff_duration,
                    'undetermined_duration' => $undetermined_duration,
                );
            }
            if (sizeof($finalArr) > 0) {
                $data['success'] = 1;
                $data['data'] = array($finalArr);
                $data['msg'] = "Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No ACS For this cell";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function cellOvr($cell, $date)
    {
        try {

            $from = date('Y-m-d', strtotime($date));
            $in_criteria = $this->shiftCriteria($from, $from, "all");
            $shifts = $in_criteria['shift_cri'];
            $time = $in_criteria['time_cri'];
            $database = $this->ConfigServices->influx_iris_v2c();

            $macList = DB::table('machines')
                ->select('machines.machine_serial')
                ->where('machines.is_subscribed', 1)
                ->where('machines.cell_key', $cell)
                ->get();

            $finalArr = array();

            $feedlhundred_duration = 0;
            $feedlhundred_count = 0;
            $undetermined_duration = 0;
            $undetermined_count = 0;
            $feedehundred_duration = 0;
            $feedehundred_count = 0;
            $feedghundred_duration = 0;
            $feedghundred_count = 0;
            $poweroff_duration = 0;
            $poweroff_count = 0;

            foreach ($macList as $val) {

                $i = "SELECT * from ovrs Where M='$val->machine_serial' and ($shifts) and ($time)";
                $ovr = $database->query($i)->getPoints();

                for ($i = 0; $i < sizeof($ovr); $i++) {
                    $sig_end = 0;
                    if ($ovr[$i]['V'] == '-1') {
                        $feedlhundred_duration = $feedlhundred_duration + $ovr[$i]['d'];
                        $feedlhundred_count = $feedlhundred_count + 1;
                    }

                    if ($ovr[$i]['V'] == '-3') {
                        $undetermined_duration = $undetermined_duration + $ovr[$i]['d'];
                        $undetermined_count = $undetermined_count + 1;
                    }

                    if ($ovr[$i]['V'] == 0) {
                        $feedehundred_duration = $feedehundred_duration + $ovr[$i]['d'];
                        $feedehundred_count = $feedehundred_count + 1;
                    }

                    if ($ovr[$i]['V'] == 1) {
                        $feedghundred_duration = $feedghundred_duration + $ovr[$i]['d'];
                        $feedghundred_count = $feedghundred_count + 1;
                    }

                    if ($ovr[$i]['V'] == -2) {
                        $poweroff_duration = $poweroff_duration + $ovr[$i]['d'];
                        $poweroff_count = $poweroff_count + 1;
                    }
                }

                $finalArr = array(
                    'feedlhundred_duration' => $feedlhundred_duration,
                    'feedehundred_duration' => $feedehundred_duration,
                    'feedghundred_duration' => $feedghundred_duration,
                    'poweroff_duration' => $poweroff_duration,
                    'undetermined_duration' => $undetermined_duration,
                );
            }

            if (sizeof($finalArr) > 0) {
                $data['success'] = 1;
                $data['data'] = array($finalArr);
                $data['msg'] = "Ovr Data Fetched Successfully";
            } else {
                $data['success'] = 0;
                $data['msg'] = "No OVR For This Cell";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "retrived Failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function cellOEE($cell, $date)
    {
        try {
            $end = date('d-m-y', strtotime($date)) . '-S';

            $macList = DB::table('machines')
                ->select('machines.machine_serial', 'machines.customer_key')
                ->where('machines.is_subscribed', 1)
                ->where('machines.cell_key', $cell)
                ->get();

            $finalArr = array();
            if ($macList->count() > 0) {
                $customer_key = $macList[0]->customer_key;
            } else {
                $customer_key = "";
            }

            $date = date('y-m-d', strtotime('now'));
            $year = substr($date, 0, 2);

            $tot_oee = 0;
            $tot_performance = 0;
            $tot_availability = 0;
            $tot_qualityPer = 0;
            $table = "yn_oee_" . "20" . "$year" . "_" . "$customer_key";

            if (DB::schema()->hasTable($table)) {
                foreach ($macList as $val) {

                    $shift_count = DB::table($table)
                        ->leftJoin("machines", "machines.machine_key", '=', "$table.machine_key")
                        ->where("machines.machine_serial", $val->machine_serial)
                        ->where("$table.shift_name", 'LIKE', '%' . $end . '%')
                        ->count();

                    $total_data = DB::table($table)
                        ->select(
                            DB::raw('SUM(oee) as totaloee'),
                            DB::raw('SUM(performance) as totalperformance'),
                            DB::raw('SUM(availability) as totalavailability'),
                            DB::raw('SUM(quality_percentage) as totalqualitypercentage')
                        )
                        ->leftJoin("machines", "machines.machine_key", '=', "$table.machine_key")
                        ->where("machines.machine_serial", $val->machine_serial)
                        ->where("$table.shift_name", 'LIKE', '%' . $end . '%')
                        ->get();

                    if ($shift_count < 1) {
                        $shift_count = 1;
                    }

                    if (isset($total_data[0])) {

                        $tot_oee += round($total_data[0]->totaloee, 2);
                        $tot_performance += round($total_data[0]->totalperformance, 2);
                        $tot_availability += round($total_data[0]->totalavailability, 2);
                        $tot_qualityPer += round($total_data[0]->totalqualitypercentage, 2);

                        $final_array = array(
                            'oee' => round(($tot_oee / $shift_count == null) ? 0 : ($tot_oee / $shift_count), 2),
                            'performance' => round(($tot_performance / $shift_count == null) ? 0 : ($tot_performance / $shift_count), 2),
                            'availability' => round(($tot_availability / $shift_count == null) ? 0 : ($tot_availability / $shift_count), 2),
                            'quality_percentage' => round(($tot_qualityPer / $shift_count == null) ? 0 : ($tot_qualityPer / $shift_count), 2),
                        );
                    }
                }

                $macCount = sizeof($macList);
                $finalArr = [
                    'oee' => $final_array['oee'] / $macCount,
                    'performance' => $final_array['performance'] / $macCount,
                    'availability' => $final_array['availability'] / $macCount,
                    'quality_percentage' => $final_array['quality_percentage'] / $macCount,
                ];

                if (sizeof($finalArr) > 0) {
                    $data['success'] = 1;
                    $data['msg'] = 'Data fetched successfully';
                    $data['data'] = array($finalArr);
                } else {
                    $data['success'] = 0;
                    $data['msg'] = 'No OEE For This Cell';
                }
            } else {
                $data['data'] = [];
                $data['success'] = 0;
                $data['msg'] = "no Data";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "Retrieve Fail";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }
}
