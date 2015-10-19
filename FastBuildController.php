<?php
/*
 * This file is part of Enchanting Travels Service Rates project
 */
namespace App\Controllers;

use BaseController;
use Input;
use Validator;
use Response;
use App\Models\FastbuildCounter;
use App\Models\Region;
use DB;

/**
 * It helps to save itinerary from 
 * session data which comprises of cities, hotels, arrival details, internal services,
 * car type, tour manager etc..
 * 
 * @package Controllers
 * @author VIPIN PS <vipinps13@gmail.com>
 * @version 1.0
 */

class FastBuildController extends BaseController
{
    protected $responseObject = array('data'=>'','errorMessage'=>'');
    
    
    public function __construct() {
//        $fastbuildCounter$this->FastbuildCounter
    }
    
    
    public function createServices()
    {
          $jsonData  = Input::all('data');         
          $serviceData =  json_decode($jsonData['data'],true);   

          if($serviceData['fast_build_type']=='city'){
              $this->createRegionDetails($serviceData);
          }else{
              $this->createServiceDetails($serviceData);
          }
    }
    
    public  function createRegionDetails($createServiceData){        
           $options = array();
           $extras = array();
//           $fastBuild = FastbuildCounter::create(array('status'=>1));           
//           $regionData = array( 'ts_id'=>$fastBuild->id,
//                                'name'=>$createServiceData['service_name'],
//                                'parent_id'=>$createServiceData['parent_region_id'],
//                                'status'=>1,
//                           );
            DB::beginTransaction();
            try {            
                $fastBuild = FastbuildCounter::create(array('status'=>1)); 
                $regionData = array( 'ts_id'=>$fastBuild->id,
                                'name'=>$createServiceData['service_name'],
                                'parent_id'=>$createServiceData['parent_region_id'],
                                'status'=>1,
                         );
                $regionObject = Region::create($regionData); 
                DB::commit();            
            } catch (\Exception $e) {
                $this->responseObject = array('data'=>null ,'errorMessage'=>'Region Data alredy exist');
                DB::rollback();                
            }
            if(isset($regionObject)){                
                $this->responseObject = array('data'=>$regionData ,'errorMessage'>'');
            }
          echo json_encode($this->responseObject);
    }
    public  function createServiceDetails($serviceDetails) {
        
           $options = array();
           $extras = array();
           $service_type_id = 0;           
           if($serviceDetails['fast_build_type'] == 'hotel'){
               $service_type_id= 2;
           }else if($serviceDetails['fast_build_type'] == 'travel'){
               $service_type_id= 6; 
           }else if($serviceDetails['fast_build_type'] == 'activity'){
               $service_type_id= 3;
           }else if($serviceDetails['fast_build_type'] == 'guide'){
               $service_type_id= 5;
           }
           $currency_id = 33;
           $supplier_id = 2;
                   $serviceDetails = array( 'ts_id'=>$fastBuild->id,
                                'name'=>$serviceDetails['service_name'],
                                'description'=>$serviceDetails['description'],
                                'service_type_id'=>$service_type_id,
                                'region_id'=>$serviceDetails['region_id'],
                                'supplier_id'=>$supplier_id,
                                'currency_id'=>$currency_id,
                                'status'=>1,
                           );
                   
                   $serviceDetails->id;
          
        
    }
    
}

