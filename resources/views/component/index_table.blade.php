<div class="module-body table">
	<div  class="dataTables_wrapper" >
		@if(!empty($indexTableInfo['isNew']) && $indexTableInfo['isNew'] ==1 )
			<input type="button" value='新增'onclick="location.href='{{$indexTableInfo['path'].'/create'}}'">
		@endif
		<form id='delete_post_form' method="POST" >
			{{ csrf_field() }}
			{{ method_field('DELETE') }} 
		</form>
		<table class="datatable-1 table table-bordered table-striped  display dataTable" style="width: 100%;">
			
			<thead>
				<tr role="row">
					@foreach($indexTableInfo['columnsTitle'] as $title => $titleKey)
					<th class="sorting_asc">
						{{$title}}
					</th>
					@endforeach

					@if($indexTableInfo['isEdit']['is_visible'])
					<th  class="sorting_asc">
						編輯
					</th>
					@endif

					@if($indexTableInfo['isDelete']['is_visible'])
					<th  class="sorting_asc">
						刪除
					</th>
					@endif
				</tr>
			</thead>
			<tbody>
				@foreach($indexTableInfo['data'] as $dataKey => $data)
					<tr class="gradeA odd">
						@foreach($indexTableInfo['columnsTitle'] as $titleKey)
							<td>{!!(!is_array($titleKey))? $data[$titleKey] : Functions::indexTableContanMaker($titleKey,$data[$titleKey["columns"]]) !!}</td>
						@endforeach	
						
						@if($indexTableInfo['isEdit'])
							<td>
								<a href='{{$indexTableInfo["path"]."/".$data[$indexTableInfo['isEdit']['columns']].'/edit'}}'>編輯</a>
							</td>
						@endif

						@if($indexTableInfo['isDelete'])
							<td>
								<a class='delete_data' href='javascript:void(0);' url='{{$indexTableInfo["path"]."/".$data[$indexTableInfo['isEdit']['columns']],'/delete'}}' url1='{{route("lottery_lists.destroy",'aa')}}'>刪除</a>
							</td>
						@endif
					</tr>
					
				@endforeach
			</tbody>
		</table>
	</div>
</div>
</div>
@section("script")
<script>

	$('.delete_data').click(function(){
		var _this = $(this);
		if(confirm('Are you sure?')) {
			$("#delete_post_form").attr('actioln',_this.attr('url'));
			//$("#delete_post_form").submit();
		}
	})
	
</script>
@stop