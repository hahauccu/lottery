<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CrudService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateLotteryListRequest;
use Illuminate\Support\Str;
use App\Models\ParticipantLists;

class LotteryController extends Controller
{
    // public function list_management()
    // {
    // 	//$crudService = new 
    // }
    private $indexTableInfo =array();

    public function __construct()
    {
    	$this->indexTableInfo = array('columnsTitle'=>
    								array('抽獎名稱'=>'lottery_name',
    								  '抽獎網址' =>array(
    								  	'type' => 'linkStringFontAdd',
    								  	'columns' => 'code',
    								  	'toAddString' => config('app.font_url')."lottery/",
    								  	'displayString' => '連結'
    								  )
    								),
								'isNew' => 1,
								'isEdit' => array('is_visible'=>1,'columns'=>'code'),
								'isDelete' => array('is_visible'=>1,'columns'=>'code'),
								'path' => "/adminer/lottery_lists",
							);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    	
    	$crudService = new CrudService(\App\Models\LotteryLists::class);
    	$lotteryLists = $crudService->get( array("user_id" => Auth::id()) );
    	$this->indexTableInfo['data'] = (!empty($lotteryLists)) ? $lotteryLists->toArray() : array();
    	return view("lottery_list.index",
    		[
    			'lotteryLists'=>$lotteryLists,
    			'title' =>'抽獎管理',
    			'indexTableInfo' =>$this->indexTableInfo,
    			
    		]
    	);
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view("lottery_list.edit",
        	[
        		'indexTableInfo' =>$this->indexTableInfo,
        		'title' =>'抽獎管理',
        	]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateLotteryListRequest $request)
    {
        $crudService = new CrudService(\App\Models\LotteryLists::class);
        $toCreateData = $request->validated();
        $toCreateData["user_id"] =Auth::id();
        $toCreateData['code'] = Str::random(10);
        if( !empty( $crudService->getOne(array('code'=>$toCreateData['code'] ) ) ) )
        {
        	$toCreateData['code'] = Str::random(10);
        }
        $crudService->create($toCreateData);
        
        return redirect()->route('lottery_lists.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($code)
    {

        $participantList =  ParticipantLists::where('user_id',Auth::id())->get()->toArray();
        
        $subMenu = array(
            array('title'=>'獎項管理','columns'=>
                array(
                    'input'=>'award_name',
                    'input'=>'award_number',
                    'drop_down_list'=>'to_lottery_list',
                ),
            ),

        );
        $crudService = new CrudService(\App\Models\LotteryLists::class);
        $data = $crudService->getOne(array('code'=>$code));
        
        return view("lottery_list.edit",
        	[
        		'indexTableInfo' =>$this->indexTableInfo,
        		'title' =>'抽獎管理',
                'participantList'=>$participantList,
        		'data' =>$data->toArray(),
        	]
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CreateLotteryListRequest $request, $code)
    {
        $crudService = new CrudService(\App\Models\LotteryLists::class);
        $crudService->update($request->validated(),array('code' => $code));
        return redirect()->route('lottery_lists.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($code)
    {
        $crudService = new CrudService(\App\Models\LotteryLists::class);
        $crudService->delete(array('code' => $code));
        return redirect()->route('lottery_lists.index');
    }

}
