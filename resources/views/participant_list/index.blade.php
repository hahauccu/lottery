@extends('component.template')

@section("content")

<div class="wrapper" style="height: 90vh; ">
	<div class="container" style="width: 90vw; margin-left:15vw;">
		<div class="row">
			@include("component.sidebar")
			<div class="span30" style="width: 70%">
				<h1>{{$title}}</h1>
				<div style="width:100%; height: 3px; background-color: black"></div>
				{{Functions::indexTableMaker($indexTableInfo)}}

			</div>
		</div>
	</div>
	@stop