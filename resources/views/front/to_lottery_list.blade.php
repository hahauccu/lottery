
<!DOCTYPE html>
<html>
<head>
	<title>looter browse</title>
	<style>
		table {
		  font-family: Arial, Helvetica, sans-serif;
		  border-collapse: collapse;
		  width: 100%;
		}

		table td, table th {
		  border: 1px solid #ddd;
		  padding: 8px;
		}

		table tr:nth-child(even){background-color: #f2f2f2;}

		table tr:hover {background-color: #ddd;}

		table th {
		  padding-top: 12px;
		  padding-bottom: 12px;
		  text-align: left;
		  background-color: #04AA6D;
		  color: white;
		}
	</style>
</head>
<body>
	<h1>抽獎名單</h1>
	<h2>{{$lotteryData['step_name'] ." -". $lotteryData['prize_number']." winners"}}</h2>
	
	<table>
		@foreach($lotteryData['participants'] as $key =>$participants)
		@if($key %4 == 0)
		<tr a="{{$key}}">
		@endif
			<td @if(array_key_exists($participants['id'], $winners)) style="background-color: lightblue"  @endif>
				{{$participants['name']}} 
				@if(array_key_exists($participants['id'], $winners))
				
				@endif
			</td>
		@if($key+1 %4 == 0)
		</tr>
		@endif
		@endforeach
	</table>
	@if(empty($lotteryData['winners']))
		<input type="button" value="go!" id='do_lottery'>
	@endif
	<script src="{{ URL::to('resource/js/jquery-1.9.1.min.js') }}" type="text/javascript"></script>
	<script type="text/javascript">
		$("#do_lottery").click(function(){
			$(this).unbind();
			$.ajax({
				url: '/do_lottery/{{$lotteryData['id']}}', 
				method:'post',
				data:{
					'_token':'{{csrf_token()}}'
				},
				success: function(data)
				{
					if(data == 1)
					{
						location.reload();
					}
			    }
			});

		})
		
	</script>
</body>
</html>