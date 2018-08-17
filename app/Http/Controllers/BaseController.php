<?php

namespace App\Http\Controllers;

use DB;
use Response;
use App\Http\Controllers\Controller;

class BaseController extends Controller
{

    protected function makeResponseWithPagination($code, $value){
        $paginator = [
            'paginator' => [
                'total_rows' => $value->total(),
                'last_page' => $value->lastPage(),
                'current_page' => $value->currentPage(),
                'per_page' => $value->perPage()
            ],
            'data' => $value->items()
        ];
        $result = $this->makeResponse(200, null, $paginator);
        return $result; 
    }

    protected function makeResponse($code, $status, $message, $data = null){
        $result = [
            'Status' => $status,
            'resp_code' => $code,
            'resp_status' => ($code == 200) ? 'success' : 'error',
        ];
        $http_code = 200;
        
        if($code == 401)
            $http_code = $code;
        
        if(!empty($message)) $result['ErrorMessage'] = $message;
        if(!empty($data)) $result['Data'] = $data;
        $result = response()->json($result,$http_code);
        return $result;
    }

    protected function isEmptyID($id, $field = 'ID'){
        if(empty($id)) return $this->makeResponse(400, $field.' can\'t be empty or zero');
        if(!is_numeric($id)) return $this->makeResponse(400, $field.' must be in numeric format');
        return false;
    }

    protected function isArrayEmptyID($ids, $field = 'ID'){
        foreach ($ids as $key => $value) {
            if(empty($value)) return $this->makeResponse(400, 'Any of '.$field.' can\'t be empty or zero');
            if(!is_numeric($value)) return $this->makeResponse(400, 'Any of '.$field.' must be in numeric format');
        }
        return false;
    }

    protected function embedSelectedFields($select, $value, $prefix = null, $exceptions = null){
        $select = explode(',', $select);
        if(!empty($prefix)){
            $temp = array();
            foreach ($select as $key => $val) {
                $val = trim($val);
                if(!empty($exceptions)){
                    if(in_array($val, $exceptions)){
                        $key = array_search($val, $exceptions);
                        $temp[] = $key.' as '.$exceptions[$key];
                    }else{
                        $temp[] = $prefix.'.'.$val;
                    } 
                }else{
                    $temp[] = $prefix.'.'.$val;
                }
            }
            $select = $temp;
        }
        $value = $value->select($select);
        return $value;
    }

    protected function embedFilters($request, $value, $filters){
        foreach ($filters as $key) {
            $inputVal = $request->input($key);
            if ($inputVal) {
                if(is_array($inputVal)){
                    $value = $value->whereIn($key, $inputVal);
                }else{
                    $value = $value->where($key, $inputVal);
                }
            }
        }
        return $value;
    }

    protected function embedSort($value, $sortBy, $sortType){
        if($sortType =='desc') $value = $value->orderBy($sortBy, 'desc');
        else $value = $value->orderBy($sortBy);
        return $value;
    }

    protected function embedWith($value, $withs){
        foreach ($withs as $val) {
            $value = $value->with($val);
        }
        return $value;
    }
}
