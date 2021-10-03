<html lang="en"><head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edmin</title>
	<link type="text/css" href="{{ URL::to('resource/css/bootstrap.min.css') }}" rel="stylesheet">
	<link type="text/css" href="{{ URL::to('resource/css/bootstrap-responsive.min.css') }}" rel="stylesheet">
	<link type="text/css" href="{{ URL::to('resource/css/theme.css') }}" rel="stylesheet">
	<link type="text/css" href="{{ URL::to('resource/css/font-awesome.css') }}" rel="stylesheet">
	<link type="text/css" href="https://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet">
	</head>
<body data-post="http://www.egrappler.com/responsive-bootstrap-admin-template-edmin/">

<link type="text/css" rel="stylesheet" href="https://www.egrappler.com/wp-content/themes/piha/css/top-bar.css">


<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container" style="margin-top:50px;">
			<a class="btn btn-navbar" data-toggle="collapse" data-target=".navbar-inverse-collapse">
				<i class="icon-reorder shaded"></i>
			</a>

			<a class="brand" href="index.html">
				Edmin
			</a>

			<div class="nav-collapse collapse navbar-inverse-collapse">
				<ul class="nav nav-icons">
					<li class="active"><a href="#">
						<i class="icon-envelope"></i>
					</a></li>
					<li><a href="#">
						<i class="icon-eye-open"></i>
					</a></li>
					<li><a href="#">
						<i class="icon-bar-chart"></i>
					</a></li>
				</ul>

				<form class="navbar-search pull-left input-append" action="#">
					<input type="text" class="span3">
					<button class="btn" type="button">
						<i class="icon-search"></i>
					</button>
				</form>
				
				<ul class="nav pull-right">
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">Dropdown <b class="caret"></b></a>
						<ul class="dropdown-menu">
							<li><a href="#">Item No. 1</a></li>

							<li><a href="#">Don't Click</a></li>
							<li class="divider"></li>
							<li class="nav-header">Example Header</li>
							<li><a href="#">A Separated link</a></li>
						</ul>
					</li>

					<li><a href="#">
						Support
					</a></li>
					<li class="nav-user dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">
							<img src="images/user.png" class="nav-avatar">
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu">
							<li><a href="#">Your Profile</a></li>
							<li><a href="#">Edit Profile</a></li>
							<li><a href="#">Account Settings</a></li>
							<li class="divider"></li>
							<li><a href="#">Logout</a></li>
						</ul>
					</li>
				</ul>
			</div><!-- /.nav-collapse -->
		</div>
	</div><!-- /navbar-inner -->
</div><!-- /navbar -->



<div class="wrapper">
	<div class="container">
		<div class="row">
			<div class="span3">
				<div class="sidebar">

					<ul class="widget widget-menu unstyled">
						<li class="active">
							<a href="index.html">
								<i class="menu-icon icon-dashboard"></i>
								Dashboard
							</a>
						</li>
						<li>
							<a href="activity.html">
								<i class="menu-icon icon-bullhorn"></i>
								News Feed
							</a>
						</li>
						<li>
							<a href="message.html">
								<i class="menu-icon icon-inbox"></i>
								Inbox
								<b class="label green pull-right">11</b>
							</a>
						</li>

						<li>
							<a href="task.html">
								<i class="menu-icon icon-tasks"></i>
								Tasks
								<b class="label orange pull-right">19</b>
							</a>
						</li>
					</ul><!--/.widget-nav-->

					<ul class="widget widget-menu unstyled">
						<li><a href="ui-button-icon.html"><i class="menu-icon icon-bold"></i> Buttons </a></li>
						<li><a href="ui-typography.html"><i class="menu-icon icon-book"></i>Typography </a></li>
						<li><a href="form.html"><i class="menu-icon icon-paste"></i>Forms </a></li>
						<li><a href="table.html"><i class="menu-icon icon-table"></i>Tables </a></li>
						<li><a href="charts.html"><i class="menu-icon icon-bar-chart"></i>Charts </a></li>
					</ul><!--/.widget-nav-->

					<ul class="widget widget-menu unstyled">
						<li>
							<a class="collapsed" data-toggle="collapse" href="#togglePages">
								<i class="menu-icon icon-cog"></i>
								<i class="icon-chevron-down pull-right"></i><i class="icon-chevron-up pull-right"></i>
								More Pages
							</a>
							<ul id="togglePages" class="collapse unstyled">
								<li>
									<a href="other-login.html">
										<i class="icon-inbox"></i>
										Login
									</a>
								</li>
								<li>
									<a href="other-user-profile.html">
										<i class="icon-inbox"></i>
										Profile
									</a>
								</li>
								<li>
									<a href="other-user-listing.html">
										<i class="icon-inbox"></i>
										All Users
									</a>
								</li>
							</ul>
						</li>

						<li>
							<a href="#">
								<i class="menu-icon icon-signout"></i>
								Logout
							</a>
						</li>
					</ul>

				</div><!--/.sidebar-->
			</div><!--/.span3-->


			<div class="span9">
				<div class="content">

					<div class="module">
						<div class="module-head">
							<h3>Forms</h3>
						</div>
						<div class="module-body">

							<div class="alert">
								<button type="button" class="close" data-dismiss="alert">×</button>
								<strong>Warning!</strong> Something fishy here!
							</div>
							<div class="alert alert-error">
								<button type="button" class="close" data-dismiss="alert">×</button>
								<strong>Oh snap!</strong> Whats wrong with you? 
							</div>
							<div class="alert alert-success">
								<button type="button" class="close" data-dismiss="alert">×</button>
								<strong>Well done!</strong> Now you are listening me :) 
							</div>

							<br>

							<form class="form-horizontal row-fluid">
								<div class="control-group">
									<label class="control-label" for="basicinput">Basic Input</label>
									<div class="controls">
										<input type="text" id="basicinput" placeholder="Type something here..." class="span8">
										<span class="help-inline">Minimum 5 Characters</span>
									</div>
								</div>

								<div class="control-group">
									<label class="control-label" for="basicinput">Disabled Input</label>
									<div class="controls">
										<input type="text" id="basicinput" placeholder="You can't type something here..." class="span8" disabled="">
									</div>
								</div>

								<div class="control-group">
									<label class="control-label" for="basicinput">Tooltip Input</label>
									<div class="controls">
										<input data-title="A tooltip for the input" type="text" placeholder="Hover to view the tooltip…" data-original-title="" class="span8 tip">
									</div>
								</div>

								<div class="control-group">
									<label class="control-label" for="basicinput">Prepended Input</label>
									<div class="controls">
										<div class="input-prepend">
											<span class="add-on">#</span><input class="span8" type="text" placeholder="prepend">       
										</div>
									</div>
								</div>

								<div class="control-group">
									<label class="control-label" for="basicinput">Appended Input</label>
									<div class="controls">
										<div class="input-append">
											<input type="text" placeholder="5.000" class="span8"><span class="add-on">$</span>
										</div>
									</div>
								</div>

								<div class="control-group">
									<label class="control-label" for="basicinput">Dropdown Button</label>
									<div class="controls">
										<div class="dropdown">
											<a class="dropdown-toggle btn" data-toggle="dropdown" href="#">Dropdown Button <i class="icon-caret-down"></i></a>
											<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
												<li><a href="#">First Row</a></li>
												<li><a href="#">Second Row</a></li>
												<li><a href="#">Third Row</a></li>
												<li><a href="#">Fourth Row</a></li>
											</ul>
										</div>
									</div>
								</div>

								<div class="control-group">
									<label class="control-label" for="basicinput">Dropdown</label>
									<div class="controls">
										<select tabindex="1" data-placeholder="Select here.." class="span8">
											<option value="">Select here..</option>
											<option value="Category 1">First Row</option>
											<option value="Category 2">Second Row</option>
											<option value="Category 3">Third Row</option>
											<option value="Category 4">Fourth Row</option>
										</select>
									</div>
								</div>

								<div class="control-group">
									<label class="control-label">Radiobuttons</label>
									<div class="controls">
										<label class="radio">
											<input type="radio" name="optionsRadios" id="optionsRadios1" value="option1" checked="">
											Option one
										</label> 
										<label class="radio">
											<input type="radio" name="optionsRadios" id="optionsRadios2" value="option2">
											Option two
										</label> 
										<label class="radio">
											<input type="radio" name="optionsRadios" id="optionsRadios3" value="option3">
											Option three
										</label>
									</div>
								</div>

								<div class="control-group">
									<label class="control-label">Inline Radiobuttons</label>
									<div class="controls">
										<label class="radio inline">
											<input type="radio" name="optionsRadios" id="optionsRadios1" value="option1" checked="">
											Option one
										</label> 
										<label class="radio inline">
											<input type="radio" name="optionsRadios" id="optionsRadios2" value="option2">
											Option two
										</label> 
										<label class="radio inline">
											<input type="radio" name="optionsRadios" id="optionsRadios3" value="option3">
											Option three
										</label>
									</div>
								</div>

								<div class="control-group">
									<label class="control-label">Checkboxes</label>
									<div class="controls">
										<label class="checkbox">
											<input type="checkbox" value="">
											Option one
										</label>
										<label class="checkbox">
											<input type="checkbox" value="">
											Option two
										</label>
										<label class="checkbox">
											<input type="checkbox" value="">
											Option three
										</label>
									</div>
								</div>

								<div class="control-group">
									<label class="control-label">Inline Checkboxes</label>
									<div class="controls">
										<label class="checkbox inline">
											<input type="checkbox" value="">
											Option one
										</label>
										<label class="checkbox inline">
											<input type="checkbox" value="">
											Option two
										</label>
										<label class="checkbox inline">
											<input type="checkbox" value="">
											Option three
										</label>
									</div>
								</div>

								<div class="control-group">
									<label class="control-label" for="basicinput">Textarea</label>
									<div class="controls">
										<textarea class="span8" rows="5"></textarea>
									</div>
								</div>

								<div class="control-group">
									<div class="controls">
										<button type="submit" class="btn">Submit Form</button>
									</div>
								</div>
							</form>
						</div>
					</div>



				</div><!--/.content-->
			</div><!--/.span9-->
		</div>
	</div><!--/.container-->
</div><!--/.wrapper-->

<div class="footer">
	<div class="container">


		<b class="copyright">© 2014 Edmin - EGrappler.com </b> All rights reserved.
	</div>
</div>

<script src="{{ URL::to('resource/js/jquery-1.9.1.min.js') }}" type="text/javascript"></script>
<script src="{{ URL::to('resource/js/jquery-ui-1.10.1.custom.min.js') }}" type="text/javascript"></script>
<script src="{{ URL::to('resource/js/bootstrap.min.js') }}" type="text/javascript"></script>
<script src="{{ URL::to('resource/js/jquery.flot.js') }}" type="text/javascript"></script>
<script type="text/javascript" src="{{ URL::to('resource/js/top-bar.js') }}""></script>

<!--Dynamically creates analytics markup-->
<!--?php include("http://www.egrappler.com/analytics.php");?-->
<iframe scrolling="no" frameborder="0" allowtransparency="true" src="https://platform.twitter.com/widgets/widget_iframe.06c6ee58c3810956b7509218508c7b56.html?origin=https%3A%2F%2Fwww.egrappler.com" title="Twitter settings iframe" style="display: none;"></iframe><iframe id="rufous-sandbox" scrolling="no" frameborder="0" allowtransparency="true" allowfullscreen="true" style="position: absolute; visibility: hidden; display: none; width: 0px; height: 0px; padding: 0px; border: none;" title="Twitter analytics iframe"></iframe></body></html>