@extends('component.template')

@section("content")
<div class="wrapper" style="height: 90vh;">
	<div class="container" style="width: 90vw;">
		
		<div class="wrapper" style="height: 90vh; ">
			<div class="container" style="width: 90vw; margin-left:15vw;">
				<div class="row">
					@include("component.sidebar")
					
				</div>
			</div>
		</div>
	</div>
</div>
@stop