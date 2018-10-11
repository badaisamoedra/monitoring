<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MwMappingHistory;
use Carbon\Carbon;
use Auth;

class ReportController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        // $this->globalCrudRepo = $globalCrudRepo;
        // $this->globalCrudRepo->setModel(new MwMappingHistory());
    }

    public function index()
    {
        $data = MwMappingHistory::select([
                                        'device_time',
                                        'license_plate',
                                        'longitude',
                                        'latitude',
                                        'speed',
                                        'total_odometer',
                                        'satellite',
                                        'last_location'
                                       ])->get();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportDriverScore(){
        $data = MwMappingHistory::all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportFleetUtilisation(){
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportGpsNotUpdate(){
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportHistorical(){
        $data = MwMappingHistory::raw(function($collection)
        {
            return $collection->aggregate([
                [
                    '$match' => [
                            'created_at' => [
                                '$gte' => Carbon::parse('2018-10-01 00:00:00'),
                                '$lte' => Carbon::parse('2018-10-05 00:00:00')
                            ]
                        ]
                ],
                [
                    '$limit' => 20
                ],
                [
                    '$project' => array(
                        'date_time'      => '$device_time',
                        'license_plate'  => '$license_plate',
                        'engine_status'  => [
                            '$cond' => [
                                'if'   => [ '$eq' => [ '$ignition', 0 ]],
                                'then' => 'Engine Off',
                                'else' => 'Engine On'
                            ]
                        ],
                        'longitude'      => '$longitude',
                        'latitude'       => '$latitude',
                        'speed'          => '$speed',
                        'mileage'        => '$total_odometer',
                        'alert'          => '$alert_status',
                        'out_of_zone'    => [
                            '$cond' => [
                                'if'   => [ '$eq' => [ '$is_out_zone', true ]],
                                'then' => 'Out Zone',
                                'else' => 'In Zone'
                            ]
                        ],
                        'heading'      => '$direction',
                        // sleepmode (deep sleed)
                        // immo 
                        'satellite'      => '$satellite',
                        'accu'           => '$internal_battery_voltage',
                        'gsm_signal'     => '$gsm_signal_level',
                        'address'        => '$last_location',
                    )
                ]
            ]);
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportKMDriven(){
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportNotification(){
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportOutOfGeofence(){
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportOverSpeed(){
        $data = MwMappingHistory::raw(function($collection)
        {
            return $collection->aggregate([
                [
                    '$project' => array(
                        'license_plate'  => '$license_plate',
                        'date_time'      => '$device_time',
                        // No. Rangka
                        // No. Mesin
                        // Duration
                        // KategoriOverspeed
                        'speed'          => '$speed',
                        'address'        => '$last_location',
                    )
                ]
            ]);
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportUnPlugged(){
        $data = MwMappingHistory::raw(function($collection)
        {
            return $collection->aggregate([
                [
                    '$project' => array(
                        'date_time'      => '$device_time',
                        'license_plate'  => '$license_plate',
                        'vehicle_number' => '$vehicle_number',
                        'longitude'      => '$longitude',
                        'latitude'       => '$latitude',
                        'speed'          => '$speed',
                        'alert'          => '$alert_status',
                        'address'        => '$last_location',
                    )
                ]
            ]);
        });
        return $this->makeResponse(200, 1, null, $data);
    }
 }