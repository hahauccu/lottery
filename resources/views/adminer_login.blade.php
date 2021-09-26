@extends("component.template")
@section("head")
	<title>user login</title>
@stop
@section("content")
	<div class="wrapper" style="height:80vh">
		<div class="container">
			<div class="row">
				<div class="module module-login span4 offset4">
					<form id='login_form' action="{{URL::to("adminer/do_login")}}" method="post">
						<input type="hidden" name="_token" value="{{csrf_token()}}">
						<div class="module-head">
							<h3>Sign In</h3>
						</div>
						<div class="module-body">
							<div class="control-group">
								<div class="controls row-fluid">
									<input class="span12" type="text" id="email" name="email" placeholder="email">
								</div>
							</div>
							<div class="control-group">
								<div class="controls row-fluid">
									<input class="span12" type="password" name="password" id="password" placeholder="Password">
								</div>
							</div>
						</div>
						<div class="module-foot">
							<div class="control-group">
								<div class="controls clearfix">
									<input type="button" name=""  class="btn btn-primary pull-right" id="do_login" value="Login">
									{{-- <label class="checkbox">
										<input type="checkbox"> Remember me
									</label> --}}
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>


	<div class="footer">
		<div class="container">
		</div>
	</div>
@stop
	@section("script")
		<script type="text/javascript">
		@if(!empty(Session::get("message")))
		alert('{{Session::get("message")}}');
		@endif
		
		$("#do_login").click(function(){
			if($("#username").val()=="")
			{
				alert("fill in username");
			}
			else if($("#password").val()=="")
			{
				alert("fill in password");
			}
			else
			{
				$("#login_form").submit();
			}
		});
	</script>
	@stop