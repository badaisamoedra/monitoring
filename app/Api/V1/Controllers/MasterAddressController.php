<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MongoMasterAddress;
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
        $new = [];
        $path  = base_path().'/public/longlat2.xlsx';
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
    }

}