<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LotteryStepsRequest;
use App\Services\CrudService;
use Illuminate\Support\Facades\Auth;
class SubMenuController extends Controller
{
    
    public $targetModelArray=array(
    	"lotterysteps" => \App\Models\LotterySteps::class,
    	'participant_detail'=> \App\Models\ParticipantDetails::class
    );

    public $targetRequestArray=array(
    	"lotterysteps" => \App\Http\Requests\LotteryStepsRequest::class,
        "participant_detail" => \App\Http\Requests\ParticipantDetailRequest::class
    );

    public function store(Request $request)
    {
        $requestRule =new $this->targetRequestArray[$request['target']];
    	$crudService = new CrudService($this->targetModelArray[$request['target']]);
        if($requestRule->authorize() == true)
            $toMotifyData = $request->validate($requestRule->rules());
        else
            $toMotifyData=(array)$request->request;
        
    	return $crudService->create($toMotifyData)->id;
    	
    }

    public function update(Request $request)
    {
        $requestRule =new $this->targetRequestArray[$request['target']];
    	$crudService = new CrudService($this->targetModelArray[$request['target']]);
        $where = array('id'=>$request['id']);
    	if($requestRule->authorize() == true)
            $toMotifyData = $request->validate($requestRule->rules());
        else
            $toMotifyData=$request;
    	$crudService->update($toMotifyData,$where);
    }

    public function show($jsonParamaters)
    {

    	$jsonParamatersArray= json_decode($jsonParamaters,1);
    	$crudService = new CrudService($this->targetModelArray[$jsonParamatersArray['target']]);
    	$where=$jsonParamatersArray['where'];
    	return  json_encode($crudService->get($where));
    }

    public function destroy($jsonParamaters)
    {
    	$jsonParamatersArray= json_decode($jsonParamaters,1);
        $crudService = new CrudService($this->targetModelArray[$jsonParamatersArray['target']]);
        $where = array_merge($jsonParamatersArray['where'],array('user_id'=>Auth::id()));
        $toDeleteData = $crudService->getOne($where);
        $this->dealWithOrder($jsonParamatersArray['target'],$toDeleteData);
        $crudService->delete($where);
    }

    public function dealWithOrder($targetModel,$toDeleteData)
    {
    	$targetModel =new $this->targetModelArray[$targetModel];
        $toUpdateOrderData = $targetModel->where('user_id',Auth::id())
        								 ->where("order",">",$toDeleteData->order)
        								 ->get();

        foreach ($toUpdateOrderData as $key => $value) 
        {
        	$value->order = $value->order-1;	
        	$value->save();
        }
    }
}
