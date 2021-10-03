
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
	<h2>{{$lotteryData['step_name'] x $lotteryData['step_name']}}</h2>
	<table>
		@foreach($lotteryData['get_lottery_steps'] as $key =>$participants)
		@if($key %4 == 0)
		<tr a="{{$key}}">
		@endif
			<td>
				{{$participants['name']}} {{}}
			</td>
		@if($key+1 %4 == 0)
		</tr>
		@endif
		@endforeach
	</table>
</body>
</html>