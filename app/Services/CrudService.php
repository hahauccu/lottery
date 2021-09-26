<?php 

namespace App\Services;

class CrudService
{
	private $modleName;
	

	public function __construct($modleName)
	{
		$this->modleName = new $modleName;

	}

	public function create( $request )
	{
		return $this->modleName->create($request);
	}

	public function getOne($where = array())
	{
		return $this->modleName->when(!empty($where),function($query) use ($where){
					foreach ($where as $column => $value) 
					{
						$query->where($column,$value);
					}
				})->first();
	}

	public function get($where = array())
	{
		return $this->modleName->when(!empty($where),function($query) use ($where){
					foreach ($where as $column => $value) 
					{
						$query->where($column,$value);
					}
				})->get();
	}

	public function update( $request ,$where)
	{
		$toUpdateData = $this->getOne($where);
		foreach($request as $column => $value)
		{
			$toUpdateData->$column = $value;
		}
		$toUpdateData->save();
	}

	public function delete($where)
	{
		$toUpdateData = $this->getOne($where)->delete();
	}
	
}