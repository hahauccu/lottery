<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CrudService;
use Mockery;
use App\Models\User;
class CrudTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_create()
    {	
    	$mockTestModel = Mockery::mock(TestModel::class);
    	$mockTestModel->shouldReceive('create')->once()->with(['id'=>1,'data'=>"test"]);
    				//->with(15)->andReturn((object) ['project' => (object) ['id' => 3]]);

    	$CrudService = new CrudService($mockTestModel);
    	$CrudService->create();
    	//app()->instance(Website::class, $websitemock)
    	app()->instance(TestModel::class, $mockTestModel);

    	//$CrudService = new CrudService(new $mockTestModel);

    	//$CrudService = (new CrudService())->handle($mockTestModel);
     	
    }

}