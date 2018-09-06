<?php

namespace App\Repositories;

use Illuminate\Database\QueryException;

class GlobalCrudRepo{

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function all($relation = []){
        try {
            if(!empty($relation)) return $this->model->with($relation)->paginate(MAX_DATA);
            else return $this->model->paginate(MAX_DATA);
        }catch(QueryException $e){
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function create(array $data){
        try {
            return $this->model->create($data);
        }catch(QueryException $e){
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function insert(array $data){
        try {
            return $this->model->insert($data);
        }catch(QueryException $e){
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function find($column, $value, $relation = []){
        try {
            if(!empty($relation)) return $this->model->where($column, $value)->with($relation)->first();
            else return $this->model->where($column, $value)->first();
        }catch(QueryException $e){
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function findObject($value){
        try {
            return $this->model->find($value);
        }catch(QueryException $e){
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function update($column, $value, array $data){
        try {
            return $this->model->where($column, $value)->update($data);
        }catch(QueryException $e){
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function updateObject($value, array $data){
        try {
            $Obj = $this->model->find($value);
            foreach($data as $key=>$val){
                $Obj->{$key} = $val;
            }
            $Obj->save();
            return $Obj;
        }catch(QueryException $e){
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function delete($column, $value){
        try {
            return $this->model->where($column, $value)->delete();
        }catch(QueryException $e){
            throw new \Exception($e->getMessage(), 500);
        }

    }

    public function deleteObject($value){
        try {
            $Obj = $this->model->find($value);
            $Obj->delete();
            return $Obj;
        }catch(QueryException $e){
            throw new \Exception($e->getMessage(), 500);
        }

    }

    public function last(){
        try{
            return $this->model->orderBy('id', 'desc')->first();
        }catch(QueryException $e){
            throw new \Exception($e->getMessage(), 500);
        }
    }

}