
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
	<table>
		<tr>
			<th>prize name </th>
			<th>prize number </th>
			<th>link</th>

		</tr>
		@foreach($browseData['get_lottery_steps'] as $steps)
		<tr>
			<td>{{$steps['step_name']}}</td>
			<td>{{$steps['prize_number']}}</td>
			<td><a href='/lottery/{{$steps["lottery_code"]}}/{{$steps["id"]}}'>go lottery</a></td>
		</tr>
		@endforeach
	</table>
</body>
</html>