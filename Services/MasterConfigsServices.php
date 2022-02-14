<?php

namespace App\Services;

use Illuminate\Database\Capsule\Manager as DB;
use App\Services\AppServices\DBServices;
use App\Services\AppServices\AppConfigs;

class MasterConfigsServices
{
    protected $dbServ;
    private  $appConfigs;
    public function __construct()
    {
        $this->dbServ = new DBServices();
        $this->appConfigs = new AppConfigs();
    }

    public function mastersConfigs($customerKey)
    {

        $res    =  DB::table('machines')
            ->select(
                "machines.machine_key",
                "machines.asset_no",
                "machines.asset_name",
                "machines.machine_serial",
                "cells.cell_key",
                "cells.cell_name",
                "plants.plant_key",
                "plants.plant_name",
                "plants.timezone_key",
                "time_zones.timezone_name",
                "time_zones.gmt_offset",
                "shifts.shift_name",
                "shifts.start_time",
                "customers.customer_key",
                "customers.customer"
            )
            ->leftjoin("cells", "cells.cell_key", "=", "machines.cell_key")
            ->leftjoin("plants", "plants.plant_key", "=", "cells.plant_key")
            ->leftjoin("shifts", "shifts.plant_key", "=", "plants.plant_key")
            ->leftjoin("customers", "customers.customer_key", "=", "plants.customer_key")
            ->leftjoin("time_zones", "time_zones.timezone_key", "=", "plants.timezone_key")
            ->where("shifts.shift_name", "shift-1")
            ->where("machines.is_subscribed", 1)
            ->where(['customers.customer_key' => $customerKey])
            ->where(['customers.status' => 1, 'plants.status' => 1, 'cells.status' => 1, 'machines.status' => 1])
            ->get()->toArray();
        if (sizeof($res)) {
            $cells = array();
            $plants = array();
            $cusMacs = array();
            $cellParentPlant = array();
            $cellMacs = array();
            $plantMacs = array();
            $cellName = array();
            $plantCells = array();
            $plantShfts = array();
            $plantTimeZone = array();
            $plantTimeZoneKey = array();
            $plantTimeZoneOffset = array();
            $plantName = array();
            $customerName = array();

            foreach ($res as $m) {

                if ($m->machine_serial != "") {
                    $customerName = $m->customer;
                    array_push($cusMacs, $m->machine_serial);

                    if (!in_array($m->cell_key, $cells)) {
                        array_push($cells, $m->cell_key);
                    }

                    if (!in_array($m->plant_key, $plants)) {
                        array_push($plants, $m->plant_key);
                    }

                    if (isset($cellMacs[$m->cell_key])) {
                        if (!in_array($m->machine_serial, $cellMacs[$m->cell_key])) {
                            array_push($cellMacs[$m->cell_key], $m->machine_serial);
                        }
                    } else {
                        $cellMacs[$m->cell_key] = [$m->machine_serial];
                    }

                    if (isset($cellName[$m->cell_key])) {
                        if (!in_array($m->cell_name, $cellName[$m->cell_key])) {
                            array_push($cellName[$m->cell_key], $m->cell_name);
                        }
                    } else {
                        $cellName[$m->cell_key] = [$m->cell_name];
                    }

                    $cellParentPlant[$m->cell_key] = $m->plant_key;

                    if (isset($plantMacs[$m->plant_key])) {
                        if (!in_array($m->machine_serial, $plantMacs[$m->plant_key])) {
                            array_push($plantMacs[$m->plant_key], $m->machine_serial);
                        }
                    } else {
                        $plantMacs[$m->plant_key] = [$m->machine_serial];
                    }

                    if (isset($plantCells[$m->plant_key])) {
                        if (!in_array($m->cell_key, $plantCells[$m->plant_key])) {
                            array_push($plantCells[$m->plant_key], $m->cell_key);
                        }
                    } else {
                        $plantCells[$m->plant_key] = [$m->cell_key];
                    }

                    if (isset($plantShfts[$m->plant_key])) {
                        if (!in_array($m->start_time, $plantShfts[$m->plant_key])) {
                            array_push($plantShfts[$m->plant_key], $m->start_time);
                        }
                    } else {
                        $plantShfts[$m->plant_key] = [$m->start_time];
                    }

                    if (isset($plantTimeZone[$m->plant_key])) {
                        if (!in_array($m->timezone_name, $plantShfts[$m->plant_key])) {
                            array_push($plantTimeZone[$m->plant_key], $m->timezone_name);
                        }
                    } else {
                        $plantTimeZone[$m->plant_key] = [$m->timezone_name];
                    }

                    if (isset($plantTimeZoneKey[$m->plant_key])) {
                        if (!in_array($m->timezone_key, $plantShfts[$m->plant_key])) {
                            array_push($plantTimeZoneKey[$m->plant_key], $m->timezone_key);
                        }
                    } else {
                        $plantTimeZoneKey[$m->plant_key] = [$m->timezone_key];
                    }

                    if (isset($plantTimeZoneOffset[$m->plant_key])) {
                        if (!in_array($m->gmt_offset, $plantShfts[$m->plant_key])) {
                            array_push($plantTimeZoneOffset[$m->plant_key], $m->gmt_offset);
                        }
                    } else {
                        $plantTimeZoneOffset[$m->plant_key] = [$m->gmt_offset];
                    }



                    if (isset($plantName[$m->plant_key])) {
                        if (!in_array($m->plant_name, $plantShfts[$m->plant_key])) {
                            array_push($plantName[$m->plant_key], $m->plant_name);
                        }
                    } else {
                        $plantName[$m->plant_key] = [$m->plant_name];
                    }

                    $this->writeMacConfig($customerKey, $customerName, $m->plant_key, $m->cell_key, $m->machine_serial, $m->machine_key, $m->asset_no, $m->asset_name);
                }
            }
            if (sizeof($cellMacs)) {
                foreach ($cellMacs as $k => $cl) {
                    $this->writeCellConfig($customerKey, $cellParentPlant, $k, $cl, $cellName[$k]);
                }
            }

            if (sizeof($plantMacs)) {
                foreach ($plantMacs as $k => $macs) {
                    $this->writePlantConfig($customerKey, $k, $plantCells[$k], $plantShfts[$k], $macs, $plantTimeZone[$k], $plantName[$k], $plantTimeZoneKey[$k], $plantTimeZoneOffset[$k]);
                }
            }
            $this->writeCusConfig($customerKey, $customerName, $plants, $cells, $cusMacs);

            $data['success'] = 1;
            $data['msg'] = "Data written to config files successfully";
        } else {
            $data['success'] = 0;
            $data['msg'] = "No data";
        }
        echo json_encode($data);
    }

    public function writeMacConfig($customerKey, $customerName, $plant_key, $cell_key, $machine_serial, $machine_key, $asset_no, $asset_name)
    {
        $filePath = $this->appConfigs->config['fileWrite'];

        $d = "<?php \n";
        $d .= "  \$val['customer_key']      = '$customerKey'; \n";
        $d .= "  \$val['customerName']      = '$customerName'; \n";
        $d .= "  \$val['plant_key']         = '$plant_key'; \n";
        $d .= "  \$val['cell_key']          = '$cell_key'; \n";
        $d .= "  \$val['machine_serial']    = '$machine_serial'; \n";
        $d .= "  \$val['machine_key']       = '$machine_key'; \n";
        $d .= "  \$val['asset_no']          = '$asset_no'; \n";
        $d .= "  \$val['asset_name']        = '$asset_name'; \n";
        $d .= "?>";

        $path =  $filePath . "/machines/$machine_serial-details.php";
        $myfile = fopen($path, "w") or die("Unable to open file!");
        fwrite($myfile, $d);
        fclose($myfile);
    }

    public function writeCellConfig($customerKey, $cellParentPlant, $cell_key, $cl, $cellName)
    {
        $filePath = $this->appConfigs->config['fileWrite'];

        $d = "<?php \n";
        $d .= "  \$val['customer']  = '$customerKey';  \n";
        $d .= "  \$val['plants']    = '" . $cellParentPlant[$cell_key] . "';\n";
        $d .= "  \$val['cells']     = '$cell_key';  \n";
        $d .= "  \$val['cell_name'] = '$cellName[0]';  \n";
        $d .= "  \$val['macSer']    = " . json_encode($cl) . ";\n";
        $d .= "?>";

        $path =  $filePath . "/cells/$cell_key-details.php";
        $myfile = fopen($path, "w") or die("Unable to open file!");
        fwrite($myfile, $d);
        fclose($myfile);
    }

    public function writePlantConfig($customerKey, $plant_key, $plantCells, $plantShfts, $macs, $timeZone, $plantName, $plantTimeZoneKey, $plantTimeZoneOffset)
    {
        $filePath = $this->appConfigs->config['fileWrite'];

        $d = "<?php\n";
        $d .= "  \$val['customer']      = '$customerKey';  \n";
        $d .= "  \$val['plants']        = '$plant_key';  \n";
        $d .= "  \$val['plant_name']    = '$plantName[0]';  \n";
        $d .= "  \$val['shift_start']   = '$plantShfts[0]';  \n";
        $d .= "  \$val['time_zone']     = '$timeZone[0]';  \n";
        $d .= "  \$val['time_zone_key'] = '$plantTimeZoneKey[0]';  \n";
        $d .= "  \$val['time_zone_offset'] = '$plantTimeZoneOffset[0]';  \n";
        $d .= "  \$val['cells']         = " . json_encode($plantCells) . ";\n";
        $d .= "  \$val['macSer']        = " . json_encode($macs) . ";\n";
        $d .= "?>";

        $path =  $filePath . "/plants/$plant_key-details.php";
        $myfile = fopen($path, "w") or die("Unable to open file!");
        fwrite($myfile, $d);
        fclose($myfile);
    }

    public function writeCusConfig($customerKey, $customerName, $plants, $cells, $macs)
    {
        $filePath = $this->appConfigs->config['fileWrite'];

        $d = "<?php\n";
        $d .= "  \$val['customer']      = '$customerKey';  \n";
        $d .= "  \$val['customerName']  = '$customerName';  \n";
        $d .= "  \$val['plants']        = " . json_encode($plants) . ";\n";
        $d .= "  \$val['cells']         = " . json_encode($cells) . ";\n";
        $d .= "  \$val['macSer']        = " . json_encode($macs) . ";\n";
        $d .= "?>";

        $path =  $filePath . "/customers/$customerKey-details.php";
        $myfile = fopen($path, "w") or die("Unable to open file!");
        fwrite($myfile, $d);
        fclose($myfile);
    }


    public function mastersPlantConfigs($plantArr)
    {
        foreach ($plantArr as $plant_key) {

            $res    =  DB::table('machines')
                ->select(
                    "machines.machine_key",
                    "machines.asset_no",
                    "machines.asset_name",
                    "machines.machine_serial",
                    "cells.cell_key",
                    "cells.cell_name",
                    "plants.plant_key",
                    "plants.plant_name",
                    "plants.timezone_key",
                    "time_zones.timezone_name",
                    "time_zones.gmt_offset",
                    "shifts.shift_name",
                    "shifts.start_time",
                    "customers.customer_key",
                    "customers.customer"
                )
                ->leftjoin("cells", "cells.cell_key", "=", "machines.cell_key")
                ->leftjoin("plants", "plants.plant_key", "=", "cells.plant_key")
                ->leftjoin("shifts", "shifts.plant_key", "=", "plants.plant_key")
                ->leftjoin("customers", "customers.customer_key", "=", "plants.customer_key")
                ->leftjoin("time_zones", "time_zones.timezone_key", "=", "plants.timezone_key")
                ->where("shifts.shift_name", "shift-1")
                ->where("machines.is_subscribed", 1)
                ->where(['plants.plant_key' => $plant_key])
                ->where(['customers.status' => 1, 'plants.status' => 1, 'cells.status' => 1, 'machines.status' => 1])
                ->get()->toArray();
            if (sizeof($res)) {
                $cells = array();
                $plants = array();
                $cusMacs = array();
                $cellParentPlant = array();
                $cellMacs = array();
                $plantMacs = array();
                $cellName = array();
                $plantCells = array();
                $plantShfts = array();
                $plantTimeZone = array();
                $plantTimeZoneKey = array();
                $plantTimeZoneOffset = array();
                $plantName = array();
                $customerName = array();
                $customer_key = array();

                foreach ($res as $m) {

                    if ($m->machine_serial != "") {
                        $customerName = $m->customer;
                        $customer_key = $m->customer_key;
                        array_push($cusMacs, $m->machine_serial);

                        if (!in_array($m->cell_key, $cells)) {
                            array_push($cells, $m->cell_key);
                        }

                        if (!in_array($m->plant_key, $plants)) {
                            array_push($plants, $m->plant_key);
                        }

                        if (isset($cellMacs[$m->cell_key])) {
                            if (!in_array($m->machine_serial, $cellMacs[$m->cell_key])) {
                                array_push($cellMacs[$m->cell_key], $m->machine_serial);
                            }
                        } else {
                            $cellMacs[$m->cell_key] = [$m->machine_serial];
                        }

                        if (isset($cellName[$m->cell_key])) {
                            if (!in_array($m->cell_name, $cellName[$m->cell_key])) {
                                array_push($cellName[$m->cell_key], $m->cell_name);
                            }
                        } else {
                            $cellName[$m->cell_key] = [$m->cell_name];
                        }

                        $cellParentPlant[$m->cell_key] = $m->plant_key;

                        if (isset($plantMacs[$m->plant_key])) {
                            if (!in_array($m->machine_serial, $plantMacs[$m->plant_key])) {
                                array_push($plantMacs[$m->plant_key], $m->machine_serial);
                            }
                        } else {
                            $plantMacs[$m->plant_key] = [$m->machine_serial];
                        }

                        if (isset($plantCells[$m->plant_key])) {
                            if (!in_array($m->cell_key, $plantCells[$m->plant_key])) {
                                array_push($plantCells[$m->plant_key], $m->cell_key);
                            }
                        } else {
                            $plantCells[$m->plant_key] = [$m->cell_key];
                        }

                        if (isset($plantShfts[$m->plant_key])) {
                            if (!in_array($m->start_time, $plantShfts[$m->plant_key])) {
                                array_push($plantShfts[$m->plant_key], $m->start_time);
                            }
                        } else {
                            $plantShfts[$m->plant_key] = [$m->start_time];
                        }

                        if (isset($plantTimeZone[$m->plant_key])) {
                            if (!in_array($m->timezone_name, $plantShfts[$m->plant_key])) {
                                array_push($plantTimeZone[$m->plant_key], $m->timezone_name);
                            }
                        } else {
                            $plantTimeZone[$m->plant_key] = [$m->timezone_name];
                        }

                        if (isset($plantTimeZoneKey[$m->plant_key])) {
                            if (!in_array($m->timezone_key, $plantShfts[$m->plant_key])) {
                                array_push($plantTimeZoneKey[$m->plant_key], $m->timezone_key);
                            }
                        } else {
                            $plantTimeZoneKey[$m->plant_key] = [$m->timezone_key];
                        }

                        if (isset($plantTimeZoneOffset[$m->plant_key])) {
                            if (!in_array($m->gmt_offset, $plantShfts[$m->plant_key])) {
                                array_push($plantTimeZoneOffset[$m->plant_key], $m->gmt_offset);
                            }
                        } else {
                            $plantTimeZoneOffset[$m->plant_key] = [$m->gmt_offset];
                        }



                        if (isset($plantName[$m->plant_key])) {
                            if (!in_array($m->plant_name, $plantShfts[$m->plant_key])) {
                                array_push($plantName[$m->plant_key], $m->plant_name);
                            }
                        } else {
                            $plantName[$m->plant_key] = [$m->plant_name];
                        }

                        $this->writeMacConfig($customer_key, $customerName, $m->plant_key, $m->cell_key, $m->machine_serial, $m->machine_key, $m->asset_no, $m->asset_name);
                    }
                }
                if (sizeof($cellMacs)) {
                    foreach ($cellMacs as $k => $cl) {
                        $this->writeCellConfig($customer_key, $cellParentPlant, $k, $cl, $cellName[$k]);
                    }
                }

                if (sizeof($plantMacs)) {
                    foreach ($plantMacs as $k => $macs) {
                        $this->writePlantConfig($customer_key, $k, $plantCells[$k], $plantShfts[$k], $macs, $plantTimeZone[$k], $plantName[$k], $plantTimeZoneKey[$k], $plantTimeZoneOffset[$k]);

                        $data['data'][] = $plantMacs;
                    }
                    
                }

                $data['success'] = 1;
                $data['msg'] = "Data written to config files successfully";
                
            } else {

                $res    =  DB::table('machines')
                    ->select(
                        "machines.machine_key",
                        "machines.asset_no",
                        "machines.asset_name",
                        "machines.machine_serial",
                        "cells.cell_key",
                        "cells.cell_name",
                        "plants.plant_key",
                        "plants.plant_name",
                        "plants.timezone_key",
                        "time_zones.timezone_name",
                        "time_zones.gmt_offset",
                        "shifts.shift_name",
                        "shifts.start_time",
                        "customers.customer_key",
                        "customers.customer"
                    )
                    ->leftjoin("cells", "cells.cell_key", "=", "machines.cell_key")
                    ->leftjoin("plants", "plants.plant_key", "=", "cells.plant_key")
                    ->leftjoin("shifts", "shifts.plant_key", "=", "plants.plant_key")
                    ->leftjoin("customers", "customers.customer_key", "=", "plants.customer_key")
                    ->leftjoin("time_zones", "time_zones.timezone_key", "=", "plants.timezone_key")
                    ->where("shifts.shift_name", "shift-1")
                    ->where(['plants.plant_key' => $plant_key])
                    ->where(['customers.status' => 1, 'plants.status' => 1, 'cells.status' => 1, 'machines.status' => 1])
                    ->limit(1)->get();

                    if(sizeof($res)){
                        $filePath = $this->appConfigs->config['fileWrite'];

                        $customerKey = $res[0]->customer_key;
                        $plantName   = $res[0]->plant_name;
                        $plantShfts   = $res[0]->start_time;
                        $timeZone   = $res[0]->timezone_name;
                        $plantTimeZoneKey   = $res[0]->timezone_key;
                        $plantTimeZoneOffset   = $res[0]->gmt_offset;
                        $plantCells   = $res[0]->cell_key;
        
                        $d = "<?php\n";
                        $d .= "  \$val['customer']      = '$customerKey';  \n";
                        $d .= "  \$val['plants']        = '$plant_key';  \n";
                        $d .= "  \$val['plant_name']    = '$plantName';  \n";
                        $d .= "  \$val['shift_start']   = '$plantShfts';  \n";
                        $d .= "  \$val['time_zone']     = '$timeZone';  \n";
                        $d .= "  \$val['time_zone_key'] = '$plantTimeZoneKey';  \n";
                        $d .= "  \$val['time_zone_offset'] = '$plantTimeZoneOffset';  \n";
                        $d .= "  \$val['cells']         = [" . json_encode($plantCells) . "];\n";
                        $d .= "  \$val['macSer']        = [] ;\n";
                        $d .= "?>";
        
                        $path =  $filePath . "/plants/$plant_key-details.php";
                        $myfile = fopen($path, "w");
                        fwrite($myfile, $d);
                        fclose($myfile);
        
                    }else{
                        $pArr[] = $plant_key;
                        $data['NoData'] = "No Proper Data for this Plant Key - ".json_encode($pArr);
                    }

                $data['success'] = 1;
                $data['msg'] = "Reset the Plant's machine data to empty";
            }
        }
        echo json_encode($data);
    }
}
