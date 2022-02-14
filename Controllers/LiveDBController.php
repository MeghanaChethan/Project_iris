<?php
    namespace App\Controllers;
    use App\Controllers\Controller;
    use App\Services\AppServices\ExceptionServices ;
    use App\Services\AppServices\Validator ;
    use App\Services\LiveDBServices;

    class LiveDBController extends Controller
    {   
        protected $ExceptionServices;
        protected $validator;

        public function __construct($container){
            parent::__construct($container);
            $this->ExceptionServices = new ExceptionServices();
            $this->validator = new Validator();
            $this->obj = new LiveDBServices();
    
        }

        public function machineData($request,$response){
            try {
                $arr['machine_serial']  = $request->getParam('machine_serial');
                $arr['cur_date']        = $request->getParam('cur_date');
                $arr['shift']           = $request->getParam('shift');
                
                $data = $this->obj->machineData($arr);
                if ($data['success'] == 0) {
                    $data = $this->ExceptionServices->service_level_exception($data);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $data['success'] = 0;
                $data = $this->ExceptionServices->controller_level_exception($e);
                $data['msg'] = "Something went wrong";
            }
            echo json_encode($data);
        }

        public function macAccData($request,$response){
            try {
                $arr['machine_serial']  = $request->getParam('machine_serial');
                $arr['cur_date']        = $request->getParam('cur_date');
                $arr['shift']           = $request->getParam('shift');
                
                $data = $this->obj->macAccData($arr);
                if ($data['success'] == 0) {
                    $data = $this->ExceptionServices->service_level_exception($data);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $data['success'] = 0;
                $data = $this->ExceptionServices->controller_level_exception($e);
                $data['msg'] = "Something went wrong";
            }
            echo json_encode($data);
        }
        public function prodData($request,$response){
            try {
                $arr['machine_serial']  = $request->getParam('machine_serial');
                $arr['cur_date']        = $request->getParam('cur_date');
                $arr['shift']           = $request->getParam('shift');
                
                $data = $this->obj->prodData($arr);
                if ($data['success'] == 0) {
                    $data = $this->ExceptionServices->service_level_exception($data);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $data['success'] = 0;
                $data = $this->ExceptionServices->controller_level_exception($e);
                $data['msg'] = "Something went wrong";
            }
            echo json_encode($data);
        }

        public function proStatus($request,$response){
            try {
                $arr['machine_serial']  = $request->getParam('machine_serial');
                $arr['cur_date']        = $request->getParam('cur_date');
                $arr['shift']           = $request->getParam('shift');
                
                $data = $this->obj->proStatus($arr);
                if ($data['success'] == 0) {
                    $data = $this->ExceptionServices->service_level_exception($data);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $data['success'] = 0;
                $data = $this->ExceptionServices->controller_level_exception($e);
                $data['msg'] = "Something went wrong";
            }
            echo json_encode($data);
        }

        public function alarmDetails($request,$response){
            try {
                $arr['machine_serial']  = $request->getParam('machine_serial');
                $arr['cur_date']        = $request->getParam('cur_date');
                $arr['shift']           = $request->getParam('shift');
                
                $data = $this->obj->alarmDetails($arr);
                if ($data['success'] == 0) {
                    $data = $this->ExceptionServices->service_level_exception($data);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $data['success'] = 0;
                $data = $this->ExceptionServices->controller_level_exception($e);
                $data['msg'] = "Something went wrong";
            }
            echo json_encode($data);
        }

        public function productivity_count($request, $response)
        {
            try {
                $arr['machine_serial']  = $request->getParam('machine_serial');
                $arr['cur_date']        = $request->getParam('cur_date');
                $arr['shift']           = $request->getParam('shift');
                
                $data = $this->obj->productivity_count($arr);
                if ($data['success'] == 0) {
                    $data = $this->ExceptionServices->service_level_exception($data);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $data['success'] = 0;
                $data = $this->ExceptionServices->controller_level_exception($e);
                $data['msg'] = "Something went wrong";
            }
            echo json_encode($data);
        }
        
        public function cy_idle_status($request, $response)
        {
            try {
                $arr['machine_serial']  = $request->getParam('machine_serial');
                $arr['cur_date']        = $request->getParam('cur_date');
                $arr['shift']           = $request->getParam('shift');
                
                $data = $this->obj->cy_idle_status($arr);
                if ($data['success'] == 0) {
                    $data = $this->ExceptionServices->service_level_exception($data);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $data['success'] = 0;
                $data = $this->ExceptionServices->controller_level_exception($e);
                $data['msg'] = "Something went wrong";
            }
            echo json_encode($data);
           
        }

        public function consumable_details($request, $response)
        {

            try {
                $arr['machine_serial']  = $request->getParam('machine_serial');
                $arr['cur_date']        = $request->getParam('cur_date');
                $arr['shift']           = $request->getParam('shift');
                
                $data = $this->obj->consumable_details($arr);
                if ($data['success'] == 0) {
                    $data = $this->ExceptionServices->service_level_exception($data);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $data['success'] = 0;
                $data = $this->ExceptionServices->controller_level_exception($e);
                $data['msg'] = "Something went wrong";
            }
            echo json_encode($data);
            
        }

        public function ovr_details($request, $response)
        {
          
            try {
                $arr['machine_serial']  = $request->getParam('machine_serial');
                $arr['cur_date']        = $request->getParam('cur_date');
                $arr['shift']           = $request->getParam('shift');
                
                $data = $this->obj->ovr_details($arr);
                if ($data['success'] == 0) {
                    $data = $this->ExceptionServices->service_level_exception($data);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $data['success'] = 0;
                $data = $this->ExceptionServices->controller_level_exception($e);
                $data['msg'] = "Something went wrong";
            }
            echo json_encode($data);
        }

        public function operator_details($request, $response)
        {
            try {
                $arr['machine_serial']  = $request->getParam('machine_serial');
               
                
                $data = $this->obj->operator_details($arr);
                if ($data['success'] == 0) {
                    $data = $this->ExceptionServices->service_level_exception($data);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $data['success'] = 0;
                $data = $this->ExceptionServices->controller_level_exception($e);
                $data['msg'] = "Something went wrong";
            }
            echo json_encode($data);
        }
    
    }
