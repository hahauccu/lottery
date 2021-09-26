<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CrudService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ParticipantRequest;
use Illuminate\Support\Str;

class ParticipantController extends Controller
{
    // public function list_management()
    // {
    // 	//$crudService = new 
    // }
    private $indexTableInfo =array();

    public function __construct()
    {
    	$this->indexTableInfo = array('columnsTitle'=>
    								array('名單名稱'=>'name',
    								),
								'isNew' => 1,
								'isEdit' => array('is_visible'=>1,'columns'=>'code'),
								'isDelete' => array('is_visible'=>1,'columns'=>'code'),
								'path' => "/adminer/participant_lists",
							);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //dd(file_get_contents("https://innews.com.tw/?feed=tvbs"));
    	$crudService = new CrudService(\App\Models\ParticipantLists::class);
    	$lotteryLists = $crudService->get( array("user_id" => Auth::id()) );
    	$this->indexTableInfo['data'] = (!empty($lotteryLists)) ? $lotteryLists->toArray() : array();
    	return view("participant_list.index",
    		[
    			'lotteryLists'=>$lotteryLists,
    			'title' =>'名單管理',
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
        return view("participant_list.edit",
        	[
        		'indexTableInfo' =>$this->indexTableInfo,
        		'title' =>'名單管理',
        	]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ParticipantRequest $request)
    {
        $crudService = new CrudService(\App\Models\ParticipantLists::class);
        $toCreateData = $request->validated();
        $toCreateData["user_id"] =Auth::id();
        $toCreateData['code'] = Str::random(10);
        if( !empty( $crudService->getOne(array('code'=>$toCreateData['code'] ) ) ) )
        {
        	$toCreateData['code'] = Str::random(10);
        }
        $crudService->create($toCreateData);
        
        return redirect()->route('participant_lists.index');
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
        $subMenu = array(
            array('title'=>'獎項管理','columns'=>
                array(
                    'input'=>'award_name',
                    'input'=>'award_number',
                    'drop_down_list'=>'to_participant_list',
                ),
            ),

        );
        $crudService = new CrudService(\App\Models\ParticipantLists::class);
        $data = $crudService->getOne(array('code'=>$code));
        
        
        return view("participant_list.edit",
        	[
        		'indexTableInfo' =>$this->indexTableInfo,
        		'title' =>'名單管理',
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
    public function update(ParticipantRequest $request, $code)
    {
        $crudService = new CrudService(\App\Models\ParticipantLists::class);
        $crudService->update($request->validated(),array('code' => $code));
        return redirect()->route('participant_lists.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($code)
    {
        $crudService = new CrudService(\App\Models\ParticipantLists::class);
        $crudService->delete(array('code' => $code));
        return redirect()->route('participant_lists.index');
    }

}
