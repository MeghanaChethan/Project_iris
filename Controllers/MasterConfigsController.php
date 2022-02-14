<?php
    namespace App\Controllers;
    use App\Controllers\Controller;
    use App\Services\AppServices\ExceptionServices ;
    use App\Services\AppServices\Validator ;
    use App\Services\MasterConfigsServices;

    class MasterConfigsController extends Controller
    {   
        protected $ExceptionServices;
        protected $validator;

        public function __construct($container){
            parent::__construct($container);
            $this->ExceptionServices = new ExceptionServices();
            $this->validator = new Validator();
    
        }

        public function mastersConfigs($request,$response){
            try {
                $customerKey = $request->getAttribute('customerKey');
                $obj = new MasterConfigsServices();
                $data = $obj->mastersConfigs($customerKey);
                if ($data['success'] == 0) {
                    $data = $this->ExceptionServices->service_level_exception($data);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $data = $this->ExceptionServices->controller_level_exception($e);
                $data['msg'] = "Something went wrong";
            }
        }
        
        public function mastersPlantConfigs($request,$response){
            try {
                $plant_key = $request->getParam('plant_key');
 
                $obj = new MasterConfigsServices();
                $data = $obj->mastersPlantConfigs($plant_key);
                if ($data['success'] == 0) {
                    $data = $this->ExceptionServices->service_level_exception($data);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $data = $this->ExceptionServices->controller_level_exception($e);
                $data['msg'] = "Something went wrong";
            }
        }

    }
?>