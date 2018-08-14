<?php

namespace App\Repositories;

use App\Models\MsRole as RoleDB;
use Illuminate\Database\QueryException;

class RoleRepo{
	
	public function all($columns = array('*')){
		try {
			if($columns == array('*')) return RoleDB::all();
			else return RoleDB::select($columns)->get();
		}catch(QueryException $e){
			throw new \Exception($e->getMessage(), 500);
		}
	}

	public function create(array $data){
		try {
			return RoleDB::create($data);
		}catch(QueryException $e){
			throw new \Exception($e->getMessage(), 500);
		}
	}

	public function insert(array $data){
		try {
			return RoleDB::insert($data);
		}catch(QueryException $e){
			throw new \Exception($e->getMessage(), 500);
		}
	}

	public function find($column, $value){
		try {
			return RoleDB::where($column, $value)->first();
		}catch(QueryException $e){
			throw new \Exception($e->getMessage(), 500);
		}
	}

	public function update($id, array $data){
		try { 
			return RoleDB::where('Id',$id)->update($data);
		}catch(QueryException $e){
			throw new \Exception($e->getMessage(), 500);
		}	
	} 

	public function delete($id){
		try { 
			return RoleDB::where('Id',$id)->delete();
		}catch(QueryException $e){
			throw new \Exception($e->getMessage(), 500);
		}
		
	}

	public function deleteByParam($column, $value){
		try { 
			return RoleDB::where($column, $value)->delete();
		}catch(QueryException $e){
			throw new \Exception($e->getMessage(), 500);
		}
	}
		

	public function last(){
		try{
			return RoleDB::orderBy('Id', 'desc')->first();
		}catch(QueryException $e){
			throw new \Exception($e->getMessage(), 500);
		}
	}


}