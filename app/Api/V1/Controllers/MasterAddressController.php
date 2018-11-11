<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MongoMasterAddress;
use App\Models\MongoLogsSync;
use Maatwebsite\Excel\Facades\Excel;
use Auth;

class MasterAddressController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MongoMasterAddress());
    }

    public function store(Request $request)
    {
        try{
            $fileName = $request->fileName;
            ini_set("memory_limit",-1);
            ini_set('max_execution_time', 0);
            $new = [];
            $path  = base_path().'/public/'.$fileName;
            $datas = Excel::load( $path , function($reader) {})->get();
            foreach($datas as $data){
                $checkData = MongoMasterAddress::where('longlat', $data->longlat)->first();
                if (!empty($checkData)) {
                    continue;
                }
                $dataSave = [
                    'latitude'			=> $data->latitude,
                    'longitude'			=> $data->longitude,
                    'address'	        => $data->address,
                    'longlat'			=> $data->longlat
                ];
                $new = $this->globalCrudRepo->create($dataSave);
            }
            return $this->makeResponse(200, 1, null, $new);
        }catch(\Exception $e){
            $saveLogs = [
				'status' => 'ERROR',
				'file_function' => 'MasterAddressController',
				'execution_time' => '0',
				'Message' => $e->getMessage()
			];
			$logs = MongoLogsSync::create($saveLogs);
        }
    }

    public function show(Request $request)
    {
        $this->validate($request, [
            'longitude' => 'required',
            'latitude' => 'required',
        ]);
        $longlat = $request->longitude.$request->latitude;
        $data = MongoMasterAddress::where('longlat', $longlat)->first();
        if(empty($data)){
             //Send request and receive json data by address
             $geocodeFromLatLong = file_get_contents('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='.$request->latitude.'&lon='. $request->longitude.'&limit=1&email=badai.samoedra@gmail.com'); 
             $output = json_decode($geocodeFromLatLong);
             //Get lat, long and address from json data
             $address   = !empty($output) ? $output->display_name:'';
             //store to master address
             MongoMasterAddress::create([
                                     'latitude'  => $request->latitude,
                                     'longitude' => $request->longitude,
                                     'address'   => $address,
                                     'longlat'   => $longlat 
                                 ]);
            $data = MongoMasterAddress::where('longlat', $longlat)->first();
        }
        return $this->makeResponse(200, 1, null, $data);
    }

    public function count(Request $request)
    {
        $data = MongoMasterAddress::get()->count();
        return $this->makeResponse(200, 1, null, ['total' => $data]);
    }

}