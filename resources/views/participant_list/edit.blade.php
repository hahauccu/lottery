@extends("component.template")

@section("content")
@section("css")
<style type="text/css">
	.sub_menu_button
	{
		float: left;
		margin: 0 2px 0 0 ;
		border: 2px solid;
		padding: 10px 50px 10px 50px ;
	}

	.notify
	{
		float: right;
		margin: 0 5vw 0 0 ;
		border: 10px solid green;
		padding: 10px 50px 10px 50px ;
		background: #eaf4e2;
	    border-color: #c1dea9;
	    color: #61a06f;
	    font-size: large;
	    display: none;
	}
	.sub_menu_button span
	{
		font-size: large;
	}
</style>
@stop
<div class="wrapper" style="height: 90vh; ">
	<div class="container" style="width: 90vw; margin-left:15vw;">
		<div class="row">
			@include("component.sidebar")
			<div class="span30" style="width: 70%">
				<h1>{{$title}} {{!empty($data) ? ' 編輯' : ' 新增'}}</h1>
				<div style="width:100%; height: 3px; background-color: black"></div>
				<div  style="display:block height:7vh;">
					<div class="sub_menu_button" div-name='index'>
						<span>{{$title}}</span>
					</div>
					{{-- @foreach ($subTable as $tableTitle) --}}
					<div class="sub_menu_button" div-name='participant_detail'>
						<span>抽獎者管理</span>
					</div>
					{{-- @endforeach --}}
					{{-- <div class="sub_menu_button">
						<span>管理</span>
					</div>
					<div class="sub_menu_button">
						<span>管理</span>
					</div> --}}
					<span class='notify'></span>
				</div>
				<br>
				<br>
				<br>
				<div class="content index" style="background-color: white;">
					<div class='module'>
						<div class="module-body">
							<form class="form-horizontal row-fluid" method="POST" action="{{$indexTableInfo['path']}}{{!empty($data) ? '/'.$data['code'] : ''}}">
								{{!empty($data) ? method_field('PATCH') : ''}}
								<input type='hidden' name="_token" value="{{csrf_token()}}">
								{!!Functions::tableEditInputMaker([
										'type' => 'text',
										'label' => '名單名稱',
										'name' =>'name',
										'value' =>!empty($data['name']) ? $data['name'] : "",
									])!!}
								<div class="control-group">
									<br>
									<div class="controls">
										<button type="submit" class="btn">送出</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>

				<div class="content participant_detail" style="background-color: white; display:none;">
					<input type="button" value="新增" class='new_table_data' style="display: none">
				<table class='hidden_header' style='display:none;'>
				
					
					<td>
						<input type='text' column-name='name'>
						<input type='hidden' column-name='id'>
					</td>
					<td><input type='text' column-name='phone'></td>
					<td><input type='text' column-name='email'></td>
					<td>
						<input type="checkbox" column-name="is_visible" value='1'>
					</td>
					<td>
						<div>
							<input type="text" class='order_input' column-name='order'>
						</div>
					</td>
					<td class='save'>save</td>
					<td class='delete'>delete</td>
				</table>
					<br>
					<table class="table">
						<thead>
						  <tr>
							<th>姓名</th>
							<th>電話</th>
							<th>email</th>
							<th>能否抽獎</th>
							<th>排序</th>
							<th>儲存</th>
							<th>刪除</th>
						  </tr>
						</thead>
						<tbody>

						 
						</tbody>
					  </table>
				</div>

				<div class="content" style="background-color: white; display:none">
					222
				</div>
			</div>
		</div>
	</div>
	
	@stop

	@section('script')
	<script>

		function showmessage(text,second)
		{
			$('.notify').text(text);
			$('.notify').show();
			$('.notify').fadeOut(second*1000);
		}


		$(".sub_menu_button").click(function(){
			var _this = $(this);
			$(".content").hide();
			$(".content_active").removeClass('content_active');
			_this.addClass('content_active');
			$("."+_this.attr('div-name')+'.content').addClass('content_active');
			$(".content_active").show();
			$(".content_active").find('.table tbody tr').remove();
			
			$.ajax({
				url: '/adminer/sub_menu/{"where":{"participant_lists_code":"'+$("form").attr('action').split("/").pop()+'"},"target":"'+_this.attr('div-name')+'"}', 
				method:'get',
				success: function(data)
				{
					var data = JSON.parse(data);
					$.each(data,function(data_key,data_value)
					{
						$(".new_table_data").click();
						$.each(data_value,function(column,value)
						{
							$('.save_yet').find('input,select').each(function(){
								$(this).attr('column-name')
								if($(this).attr('column-name') == column)
								{
									if($(this).attr('type')=='checkbox')
									{
										if(value == 1)
											$(this).prop('checked',true);
									}
									else
									{
										$(this).val(value);	
									}
								}
							})
						})
						$('.save_yet').removeClass('save_yet');
					})
					$(".new_table_data").show();
					// $(".order_down").show()
					// $(".order_up").show()
					//$(".content_active").find('.table tbody tr').first().find('.order_up').hide();
					//$(".content_active").find('.table tbody tr').last().find('.order_down').hide();
					
			    }
			});
		});

		$(".new_table_data").click(function(){
			if($(".save_yet").length > 0 )
			{
				alert('請先儲存');
				return 0;
			}

			var parent = $(this).parent();
			parent.find('.table tbody').append(parent.find('.hidden_header tbody').html());
			$(".table tr").last().find('.order_input').val($(".table tr").length-1);
			$(".table tr").last().find('.order_up,.order_down').hide();
			$(".table tr").last().addClass('save_yet')
		});

		$("body").on('click','.save',function(){
			var _this = $(this);
			var data = {};
			_this.parent().find('input,select').each(function(){
				data[$(this).attr('column-name')] = $(this).val();
				if($(this).attr('type')=='checkbox')
				{
					if($(this).prop('checked') !==true)
					{
						data[$(this).attr('column-name')] = 0;
					}

				}
			})
			data['target'] = 'participant_detail';
			data['participant_lists_code'] = $("form").attr('action').split("/").pop();
			data['_token'] = $('input[name="_token"]').val();
			var url ='';
			var method ='POST';
			//edit
			if(data["id"]+"" !=="")
			{
				url='/adminer/sub_menu/'+data["id"];
				method = "PATCH";
			}
			//create
			else
			{
				url='/adminer/sub_menu';
				delete data['id'];
			}

			$.ajax({
				method:method,
				data:data,
				url:url,
				success: function(result)
				{
					$('.save_yet').append('<input type="hidden" value="'+result+'" column-name="id" >');
					$('.save_yet').removeClass('save_yet');
					showmessage('儲存成功',7);
			    },
			    error(response)
			    {
			    	if(response.status ==422)
			    	{
			    		alert("please fill in all the input");
			    	}
			    }
			});
		})

		$("body").on('click','.delete',function(){
			
			var yes = confirm('確定刪除嗎？');
			var _this = $(this);

			if (yes) 
			{
				$.ajax({
					method:"DELETE",

					url:'/adminer/sub_menu/'+'{"where":{"id":"'+_this.parent().find("input[column-name='id']").val()+'"},"target":"participant_detail"}',
					data:
					{
						_token:$('input[name="_token"]').val()
					},
					success: function(result)
					{
						$(".content_active").click();
						showmessage('刪除成功',7);
				    },
				});    
			} 
			
		})

		// $(".order_up,.order_down").click(function(){

		// })


	</script>
		
	@endsection