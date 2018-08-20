<?php

namespace App\Repositories;

use Illuminate\Database\QueryException;

class GlobalCrudRepo{

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function all($columns = array('*')){
        try {
            if($columns == array('*')) return $this->model->get();
            else return $this->model->select($columns)->get();
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

    public function find($column, $value){
        try {
            return $this->model->where($column, $value)->first();
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

    public function delete($column, $value){
        try {
            return $this->model->where($column, $value)->delete();
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