<!DOCTYPE html>
<html>
	<head>
		@yield('head')
		@include("component.head")
		@yield('css')
	</head>
	<body>
		@include('component.header')
		@yield('content')

		<script src="{{ URL::to('resource/js/jquery-1.9.1.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::to('resource/js/jquery-ui-1.10.1.custom.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::to('resource/js/bootstrap.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::to('resource/js/jquery.flot.js') }}" type="text/javascript"></script>
		<script type="text/javascript" src="{{ URL::to('resource/js/top-bar.js') }}""></script>
		@yield('script')
	</body>
</html>