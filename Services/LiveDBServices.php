<?php

namespace App\Services;

use Illuminate\Database\Capsule\Manager as DB;
use App\Services\AppServices\DBServices;
use App\Services\AppServices\ConfigServices;

class LiveDBServices
{
    protected $dbServ;
    protected $database;

    public function __construct()
    {
        $this->dbServ = new DBServices();
        $this->configServices = new ConfigServices();
        $this->database = $this->configServices->influx_iris_v2c();
    }

    public function shiftCriteria($from_date, $to_date, $shift)
    {
        $from   = date('Y-m-d', strtotime("-2 days", strtotime($from_date)));
        $to     = date('Y-m-d', strtotime("+2 days", strtotime($to_date)));

        $res['time_cri']    = " time >= " . strtotime($from) . "000000000 AND time <= " .  strtotime($to) . "000000000";
        $res['shift_cri']   = '';

        while (strtotime($from_date) <= strtotime($to_date)) {
            if ($res["shift_cri"] != "") {
                $res["shift_cri"] .= " OR ";
            }
            switch ($shift) {
                case "All":
                    $res["shift_cri"] .= " shift=~/" . date("d-m-y", strtotime($from_date)) . "*/  ";
                    break;
                case "shift-1":
                    $res["shift_cri"] .= " shift= '" . date("d-m-y", strtotime($from_date)) . "-S1'  ";
                    break;
                case "shift-2":
                    $res["shift_cri"] .= " shift= '" . date("d-m-y", strtotime($from_date)) . "-S2'  ";
                    break;
                case "shift-3":
                    $res["shift_cri"] .= " shift= '" . date("d-m-y", strtotime($from_date)) . "-S3'  ";
                    break;
                default:
                    $res["shift_cri"] .= " shift= '" . date("d-m-y", strtotime($from_date)) . "-" . $shift . "' ";
            }
            $from_date = date("Y-m-d", strtotime("+1 days", strtotime($from_date)));
        }
        return $res;
    }

    public function machineData($arr)
    {
        try {
            $finalArr = [];
            $non_auto_idle  = 0;
            $non_auto_cycle = 0;
            $auto_idle      = 0;
            $auto_cycle     = 0;
            $p_off          = 0;
            $undetermined   = 0;

            $in_criteria =  $this->shiftCriteria($arr['cur_date'], $arr['cur_date'], $arr['shift']);
            $shifts     = $in_criteria['shift_cri'];
            $time       = $in_criteria['time_cri'];
            $mac        = $arr['machine_serial'];

            $macStatus = $this->database->query("SELECT * from acs where M='$mac' and ($time) and ($shifts)")->getPoints();

            $count = sizeof($macStatus);
            if ($count > 0) {
                foreach ($macStatus as $val) {
                    if ($val['V'] == 0) {
                        $non_auto_idle += $val["d"];
                    } elseif ($val['V'] == 1) {
                        $non_auto_cycle += $val["d"];
                    } elseif ($val['V'] == 2) {
                        $auto_idle += $val["d"];
                    } elseif ($val['V'] == 3) {
                        $auto_cycle += $val["d"];
                    } elseif ($val['V'] == '-2') {
                        $p_off += $val["d"];
                    } else {
                        $undetermined += $val["d"];
                    }
                }
                $finalArr = [
                    'non_auto_idle'  => $non_auto_idle,
                    'non_auto_cycle' => $non_auto_cycle,
                    'auto_idle'      => $auto_idle,
                    'auto_cycle'     => $auto_cycle,
                    'p_off'          => $p_off,
                    'undetermined'   => $undetermined
                ];
            }

            if (sizeof($finalArr) > 0) {
                $data['success'] = 1;
                $data['msg']     = "Data reterived successfully";
                $data['data']    =  array($finalArr);
            } else {
                $data['success'] = 0;
                $data['msg']     = "No Data";
                $data['data']    = [];
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success']    = 0;
            $data['msg']        = "Something went wrong";
            $data['data']       = $e->getMessage();
        }
        return $data;
    }

    public function macAccData($arr)
    {
        $data['success'] = 1;
        $data['msg'] = "success";
        $in_criteria =  $this->shiftCriteria($arr['cur_date'], $arr['cur_date'], $arr['shift']);
        $shifts      = $in_criteria['shift_cri'];
        $time        = $in_criteria['time_cri'];
        $mac         = $arr['machine_serial'];

        try {
            $macStatus = $this->database->query("SELECT count(V) from acs where M='$mac' and ($time) and ($shifts)")->getPoints();

            if (sizeof($macStatus) != 0) {
                $count = $macStatus[0]['count'];
                if ($count > 1000) {
                    $offset = "offset " . ($count - 1000);
                } else {
                    $offset = "";
                }
                $data['data'] = $this->database->query("SELECT * from acs where M='$mac' and ($time) and ($shifts) limit 1000 $offset")->getPoints();
            } else {
                $data['data'] = [];
            }
            if ($data['data'] == []) {
                $data['success'] = 0;
                $data['msg'] = "no Signals found";
            }
        } catch (\Exception $e) {
            $data['success']    = 0;
            $data['msg']        = "Something went wrong";
            $data['data']       = $e->getMessage();
        }
        return $data;
    }

    public function prodData($arr)
    {
        $data['success'] = 1;
        $data['msg'] = "success";
        $in_criteria =  $this->shiftCriteria($arr['cur_date'], $arr['cur_date'], $arr['shift']);
        $shifts      = $in_criteria['shift_cri'];
        $time        = $in_criteria['time_cri'];
        $mac         = $arr['machine_serial'];

        $cycling        = 0;
        $breakdown      = 0;
        $setup          = 0;
        $idle           = 0;
        $undetermined   = 0;
        $cycling_pause  = 0;
        try {
            $finalArr = array();
            $proData = $this->database->query("SELECT * from productions where M='$mac' and ($time) and ($shifts)")->getPoints();
            if (sizeof($proData) > 0) {
                foreach ($proData as $p) {
                    if ($p['V'] == '1') {
                        $cycling = $cycling + $p['d'];
                    } elseif ($p['V'] == '12') {
                        $breakdown += $p['d'];
                    } elseif ($p['V'] == '13') {
                        $setup       += $p['d'];
                    } elseif ($p['V'] == '-3') {
                        $undetermined += $p['d'];
                    } elseif ($p['V'] == '3') {
                        $cycling_pause += $p['d'];
                    } else {
                        $idle += $p['d'];
                    }
                }

                $finalArr = [
                    'cycling'       => $cycling,
                    'breakdown'     => $breakdown,
                    'setup'         => $setup,
                    'undetermined'  => $undetermined,
                    'cycling_pause' => $cycling_pause,
                    'idle'          => $idle
                ];

                $data['success'] = 1;
                $data['msg']     = "Data reterived successfully";
                $data['data']    =  array($finalArr);
            } else {
                $data['success'] = 0;
                $data['msg']     = "No Data";
                $data['data']    = [];
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success']    = 0;
            $data['msg']        = "Something went wrong";
            $data['data']       = $e->getMessage();
        }
        return $data;
    }

    public function proStatus($arr)
    {
        $data['success'] = 1;
        $data['msg'] = "success";
        $in_criteria =  $this->shiftCriteria($arr['cur_date'], $arr['cur_date'], $arr['shift']);
        $shifts      = $in_criteria['shift_cri'];
        $time        = $in_criteria['time_cri'];
        $mac         = $arr['machine_serial'];

        try {
            $macStatus = $this->database->query("SELECT count(V) from productions where M='$mac' and ($time) and ($shifts)")->getPoints();
            if (sizeof($macStatus) != 0) {
                $count = $macStatus[0]['count'];
                if ($count > 1000) {
                    $offset = "offset " . ($count - 1000);
                } else {
                    $offset = "";
                }
                $data['data'] = $this->database->query("SELECT time,V,M,d,s,e,shift,pides from productions where M='$mac' and ($time) and ($shifts) limit 1000 $offset")->getPoints();
            } else {
                $data['data'] = [];
            }
            if ($data['data'] == []) {
                $data['success'] = 0;
                $data['msg'] = "no Signals found";
            }
        } catch (\Exception $e) {
            $data['success']    = 0;
            $data['msg']        = "Something went wrong";
            $data['data']       = $e->getMessage();
        }
        return $data;
    }

    public function alarmDetails($arr)
    {
        $data['success'] = 1;
        $data['msg'] = "success";
        $in_criteria =  $this->shiftCriteria($arr['cur_date'], $arr['cur_date'], $arr['shift']);
        $shifts      = $in_criteria['shift_cri'];
        $time        = $in_criteria['time_cri'];
        $mac         = $arr['machine_serial'];
        try {

            $alarmStoppages = $this->database->query("SELECT sum(d) as alarmStoppages from almstoppages where M = '$mac' and V=1 and ($time) and ($shifts) ")->getPoints();
            if (isset($alarmStoppages[0])) {
                $data["alarmStoppages"] = $alarmStoppages[0]['alarmStoppages'];
            } else {
                $data["alarmStoppages"] = 0;
            }
            $topName = $this->database->query("SELECT * FROM alarms where M = '$mac' and ($time) and ($shifts)")->getPoints();
            $dupId = array();
            if (sizeof($topName) > 0) {
                $result = [];
                foreach ($topName as $key) {
                    array_push($dupId, $key['AC']);

                    if (array_key_exists($key['AC'], $result)) {
                        $result[$key['AC']]['alarmTopDur'] += ($key['d']);
                        $result[$key['AC']]['alarmTopDurCount'] += round($key['pc'], 2);
                    } else {
                        $result[$key['AC']] = [
                            'alarmTopDur' => $key['d'],
                            'AC' => $key['AC'],
                            'Des' => $key['Des'],
                            'alarmTopDurCount' => round($key['pc'], 2),
                        ];
                    }
                }
                $result1 = array();
                $result2 = array();
                foreach ($result as $value) {
                    if (isset($result1[$value["AC"]])) {
                        $result1[$value["AC"]]["alarmTopDur"] += $value["alarmTopDur"];
                        $result2[$value["alarmTopDurCount"]]["alarmTopDurCount"] += $value["alarmTopDurCount"];
                    } else {
                        $result1[$value["AC"]] = $value;
                        $result2[$value["alarmTopDurCount"]] = $value;
                    }
                }
                foreach ($result1 as $key => $row) {
                    $dates[$key]  = $row['alarmTopDur'];
                }
                foreach ($result2 as $key => $row) {
                    $dates1[$key]  = $row['alarmTopDurCount'];
                }
                array_multisort($dates, SORT_DESC, $result1);
                array_multisort($dates1, SORT_DESC, $result2);
                $data['alarmDur'] = array_values(array_slice($result1, 0, 1));
                $data['alarmCount'] = array_values(array_slice($result2, 0, 1));
            } else {
                $data['alarmDur'] = [];
                $data['alarmCount'] = [];
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] =  "Data retrieval failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function productivity_count($arr)
    {
        $data['success'] = 1;
        $data['msg'] = "success";
        $in_criteria =  $this->shiftCriteria($arr['cur_date'], $arr['cur_date'], $arr['shift']);
        $shifts      = $in_criteria['shift_cri'];
        $time        = $in_criteria['time_cri'];
        $mac         = $arr['machine_serial'];
        try {


            $qry = $this->database->query("SELECT * from productions where M = '$mac' order by time desc limit 1")->getPoints();
            if (sizeof($qry) > 0) {
                foreach ($qry as $key => $row) {
                    $dates[$key]  = $row['s'];
                }

                array_multisort($dates, SORT_DESC, $qry);
                array_slice($qry, 0, 1);

                $data['plan_id'] = $qry[0]['pid'];


                if (strcmp($data['plan_id'], 'U') == 0) {

                    $data['work_order_no'] = 'U';
                } else {

                    $work_order_no = DB::table('production_plan')
                        ->select('production_plan.work_order_no')
                        ->where('production_plan.production_planning_key', $data['plan_id'])
                        ->first();
                    if ($work_order_no) {
                        $data['work_order_no'] = $work_order_no->work_order_no;
                    } else {
                        $data['work_order_no'] = "";
                    }
                }

                $customer_key = DB::table('machines')->select('customer_key')->where('machine_serial',$arr['machine_serial'])->first();
                $customer_dacc_table = "yn_dacc_".date("Y")."_".$customer_key->customer_key;
                if(DB::schema()->hasTable($customer_dacc_table)){
                    $data['part_count'] = DB::table($customer_dacc_table)
                    ->select('part_count', 'notok as not_ok')
                    ->where('machine_serial', $mac)
                    ->where('plan_key', $data['plan_id'])
                    ->orderBy('modified_at', 'desc')
                    ->limit(1)
                    ->get();
                }else{
                    $data['part_count'] = [];
                }

                $data['part_no'] = DB::table('production_plan')
                    ->select('production_plan.part_key', 'parts_master.part_number')
                    ->leftJoin('parts_master', 'parts_master.part_key', 'production_plan.part_key')
                    ->where('production_plan.production_planning_key', $data['plan_id'])
                    ->get();
            } else {
                $data['success'] = 0;
                $data['data'] = [];
                $data['msg'] = "NoData Found";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "Table is not Exists";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function cy_idle_status($arr)
    {

        $data['success'] = 1;
        $data['msg'] = "success";
        $in_criteria =  $this->shiftCriteria($arr['cur_date'], $arr['cur_date'], $arr['shift']);
        $shifts      = $in_criteria['shift_cri'];
        $time        = $in_criteria['time_cri'];
        $mac         = $arr['machine_serial'];

        try {

            $acn = $this->database->query("SELECT V as V  from acn  where M='$mac' order by time desc limit 1")->getPoints();
            $V = $acn[0]["V"];

            if ($V == 0) {
                $data['status'] = "Idle";
                $data['mode'] = "Non Auto";
            } else if ($V == 1) {
                $data['status'] = "Cycling";
                $data['mode'] = "Non Auto ";
            } else if ($V == 2) {
                $data['status'] = "Idle";
                $data['mode'] = "Auto";
            } else if ($V == 3) {
                $data['status'] = "Cycling";
                $data['mode'] = "Auto";
            } else if ($V == '-2') {
                $data['status'] = "P Off";
                $data['mode'] = "P Off";
            } else {
                $data["status"] = null;
                $data["mode"] = null;
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "Data retrieval failed";
            $data['data'] = $e->getMessage();
        }

        return $data;
    }

    public function consumable_details($arr)
    {
        $data['success'] = 1;
        $data['msg'] = "success";
        $in_criteria =  $this->shiftCriteria($arr['cur_date'], $arr['cur_date'], $arr['shift']);
        $shifts      = $in_criteria['shift_cri'];
        $time        = $in_criteria['time_cri'];
        $mac         = $arr['machine_serial'];

        try {

            $conslists = $this->database->query("SELECT sum(d) as totCons from conslists where M = '$mac' and V=1 and ($time) and ($shifts) ")->getPoints();
            if (isset($conslists[0])) {
                $data["conslists"] = $conslists[0]['totCons'];
            } else {
                $data["conslists"] = 0;
            }

            $topName = $this->database->query("SELECT * FROM conss where M = '$mac' and ($time) and ($shifts)")->getPoints();
            $dupId = array();

            if (sizeof($topName) > 0) {

                $result = [];
                foreach ($topName as $key) {
                    array_push($dupId, $key['CC']);

                    if (array_key_exists($key['CC'], $result)) {
                        $result[$key['CC']]['consTopDur'] += ($key['d']);
                        $result[$key['CC']]['consTopDurCount'] += round($key['pc'], 2);
                    } else {
                        $result[$key['CC']] = [
                            'consTopDur' => $key['d'],
                            'CC' => $key['CC'],
                            'Des' => $key['Des'],
                            'consTopDurCount' =>  round($key['pc'], 2)
                        ];
                    }
                }

                $result1 = array();
                $result2 = array();
                foreach ($result as $value) {
                    if (isset($result1[$value["CC"]])) {
                        $result1[$value["CC"]]["consTopDur"] += $value["consTopDur"];
                        $result2[$value["consTopDurCount"]]["consTopDurCount"] += $value["consTopDurCount"];
                    } else {
                        $result1[$value["CC"]] = $value;
                        $result2[$value["consTopDurCount"]] = $value;
                    }
                }

                foreach ($result1 as $key => $row) {
                    $dates[$key]  = $row['consTopDur'];
                }

                foreach ($result2 as $key => $row) {
                    $dates1[$key]  = $row['consTopDurCount'];
                }

                array_multisort($dates, SORT_DESC, $result1);
                array_multisort($dates1, SORT_DESC, $result2);

                $data['consDur'] = array_values(array_slice($result1, 0, 1));
                $data['consCount'] = array_values(array_slice($result2, 0, 1));
            } else {
                $data['consDur'] = [];
                $data['consCount'] = [];
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "Data retrieval failed";
            $data['data'] = $e->getMessage();
        }
        return $data;
    }

    public function ovr_details($arr)
    {

        $data['success'] = 1;
        $data['msg'] = "success";
        $in_criteria =  $this->shiftCriteria($arr['cur_date'], $arr['cur_date'], $arr['shift']);
        $shifts      = $in_criteria['shift_cri'];
        $time        = $in_criteria['time_cri'];
        $mac         = $arr['machine_serial'];

        $ovr_low    = 0;
        $ovr_high   = 0;
        $ovr_medium = 0;
        $poff       = 0;
        $low_percentage  = 0;
        $med_percentage  = 0;
        $high_percentage = 0;
        $poffper         = 0;
        $other  = 0;

        try {

            $ovrs_data = $this->database->query("SELECT * from ovrs where  M = '$mac' and ($time) and ($shifts)")->getPoints();
            $count = sizeof($ovrs_data);
            if ($count > 0) {
                foreach ($ovrs_data as $ov) {
                    if ($ov['V'] == -1) {
                        $ovr_low++;
                    } elseif ($ov['V'] == 0) {
                        $ovr_medium++;
                    } elseif ($ov['V'] == 1) {
                        $ovr_high++;
                    } elseif ($ov['V'] == -2) {
                        $poff++;
                    } else {
                        $other++;
                    }
                }
            }

            $total = $ovr_high + $ovr_low + $ovr_medium + $poff;

            if ($ovr_low > 0) {
                $low_percentage =  round(($ovr_low / $total) * 100);
            } else {
                $low_percentage = 0;
            }

            if ($ovr_medium > 0) {
                $med_percentage =  round(($ovr_medium / $total) * 100);
            } else {
                $med_percentage = 0;
            }

            if ($ovr_high > 0) {
                $high_percentage = round(($ovr_high / $total) * 100);
            } else {
                $high_percentage = 0;
            }

            if ($poff > 0) {
                $poffper = round(($poff / $total) * 100);
            } else {
                $poffper = 0;
            }

            $data['data'] = [
                "ovr_low"           => $ovr_low,
                "ovr_medium"        => $ovr_medium,
                "ovr_high"          => $ovr_high,
                "poff"              => $poff,
                "low_percentage"    => $low_percentage,
                "med_percentage"    => $med_percentage,
                "high_percentage"   => $high_percentage,
                "poffper"           => $poffper
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = "Data retrieval failed";
            $data['data'] = $e->getMessage();
        }

        return $data;
    }

    public function operator_details($arr)
    {
        $data['success'] = 1;
        $data['msg'] = "success";
        $mac         = $arr['machine_serial'];

        try {

            $data['data'] = DB::table('operator_logs')
                ->select('users.username', 'users.first_name', 'operator_logs.login_time', 'operator_logs.flag')
                ->leftJoin('users', 'users.user_key', '=', 'operator_logs.user_key')
                ->where('operator_logs.machine_serial', $mac)
                ->orderby('operator_logs.login_time', 'desc')
                ->limit(1)
                ->distinct()
                ->get();

            if ($data['data']->count() > 0) {
                if ($data['data'][0]->username == null) {
                    $data['data'][0]->username = 'Guest';
                    $data['login_time'] = $data['data'][0]->login_time;
                } else {
                    $data['data'][0]->username = $data['data'][0]->first_name . '(' . $data['data'][0]->username . ')';
                    $data['login_time'] = $data['data'][0]->login_time;
                }
                if ($data['data'][0]->flag == 1) {
                    $data['data'][0]->username = 'No Operator';
                    $data['data'][0]->login_time = 0;
                }
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $data['success'] = 0;
            $data['msg'] = $e->getMessage();
            $data = "Data Retrival Failed";
        }
        return $data;
    }
}
