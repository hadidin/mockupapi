<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>KiplePark Local System</title>
		<!-- <link rel="stylesheet" href="{{asset('css/app.css')}}"> -->
		<!-- Scripts -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge">

		<meta name="viewport" content="width=device-width, initial-scale=1">

		<!-- Vendor styles -->

		<!-- App styles -->
		<link rel="stylesheet" href="css/app.min.css">

		<link href="{{ asset('vendors/bower_components/jquery.scrollbar/jquery.scrollbar.css')}}" rel="stylesheet">
		<link href="{{ asset('vendors/bower_components/material-design-iconic-font/dist/css/material-design-iconic-font.min.css') }}" rel="stylesheet">
		<link href="{{ asset('vendors/bower_components/animate.css/animate.min.css') }}" rel="stylesheet">
		<link href="{{ asset('vendors/bower_components/select2/dist/css/select2.min.css') }}" rel="stylesheet">
		<link href="{{ asset('vendors/bower_components/nouislider/distribute/nouislider.min.css') }}" rel="stylesheet">
		<link href="{{ asset('vendors/bower_components/dropzone/dist/dropzone.css') }}" rel="stylesheet">

		<link href="{{ asset('vendors/bower_components/trumbowyg/dist/ui/trumbowyg.min.css') }}" rel="stylesheet" >
		<link href="{{ asset('vendors/flatpickr/flatpickr.min.css') }}" rel="stylesheet" />

		<link rel="stylesheet" href="vendors/sweetalert2/sweetalert2.min.css">


		<!-- Styles -->
		<link href="{{ asset('css/app.css') }}?20190528" rel="stylesheet">
		<script type="text/javascript" src="{{asset('js/app2.min.js')}}"></script>
		<style>
			ul {
				list-style-type: none;
			}
			.active{
				background-color:#e8e9ea;
			}
		</style>
		@yield('style')
	</head>
	<body data-ma-theme="blue">
		<main class="main">
			<!-- Header -->
			<header class="header">
				<div class="navigation-trigger hidden-xl-up" data-ma-action="aside-open" data-ma-target=".sidebar">
					<div class="navigation-trigger__inner">
						<i class="navigation-trigger__line"></i>
						<i class="navigation-trigger__line"></i>
						<i class="navigation-trigger__line"></i>
					</div>
				</div>

				<div class="header__logo hidden-sm-down">
				<img src="{{asset('img/kiple-logo-white.png')}}" alt="kiplepark-logo" width="150" >
				<!-- <h1><a href="index.html">KIPLEPARK LOCAL-PSM</a></h1> -->
				</div>


				<ul class="top-nav">

					<li class="dropdown">
						<a href="" data-toggle="dropdown"><div><i class="zmdi zmdi-account-circle zmdi-hc-3x"></i></div></a>

						<div class="dropdown-menu dropdown-menu-right">
 								<div class="user">
									<div class="user__info">
										<img class="user__img" src="{{asset('img/profile-pics/4.jpg')}}" alt="">
										<div>
										
										</div>
									</div>

									<div class="dropdown-menu">

									</div>
								</div>
  							<!-- <a class="dropdown-item" href="">Profile</a> -->
							
						</div>
					</li>
				</ul>
			</header>


			<!-- Sidebar -->
			<aside class="sidebar" style="border-style: solid; border-width: 1px;border-color:#e0e0e0;">
				<div class="scrollbar-inner">

					<ul class="navigation">
						@auth()

						<?php

						$a_date=date('Y-m-d');
						$lastday_month=date("Y-m-t", strtotime($a_date));
						$firstday_month=date("Y-m-01");
						?>
						<li class="nav-item {{ Request::is('home') ? 'active' : '' }}"><a href="{{ url('/home') }}"><i class="zmdi zmdi-view-dashboard"></i> Home</a></li>

						{{--<li class="navigation__sub">--}}
							{{--@can('view_entry_log_db')--}}
							{{--<a href=""><i class="zmdi zmdi-chart"></i> Analytic</a>--}}

							{{--<ul style="{{ Request::is('entry_log_db')|Request::is('entry_log_reviewed') ? 'display: block;' : '' }}">--}}

								{{--@can('view_entry_log_db')--}}
									{{--<li class="nav-item {{ Request::is('entry_log') ? 'active' : '' }}"><a href="{{ url('/entry_log') }}"><i class="zmdi zmdi-file-text"></i> Entry log</a></li>--}}
								{{--@endcan--}}


								{{--<!-- @can('view_entry_log_reviewed')--}}
									{{--<li class="nav-item {{ Request::is('entry_log_reviewed') ? 'active' : '' }}"><a href="--}}{{-- url('/entry_log_reviewed') }}?start_date={{$firstday_month}}T00:00&end_date={{$lastday_month}}T23:59--}}{{--"><i class="zmdi zmdi-assignment-check"></i> Entry log Reviewed</a></li>--}}
								{{--@endcan  -->--}}

							{{--</ul>--}}
							{{--@endcan--}}
						{{--</li>--}}





					@endauth
					</ul>
				</div>
			</aside>


			<!-- Contents -->
			<section class="content">
				<div class="content__inner">

					<div class="container">
						@yield('content')
					</div>
					<!-- Footer -->
					<footer class="footer hidden-xs-down">
						<p>© v1.0.3 2019 | Copyright by KiplePark Malaysia</p>
					</footer>
				</div>
			</section>

			<script type="text/javascript" src="{{asset('vendors/bower_components/jquery/dist/jquery.min.js')}}"></script>
			<script type="text/javascript" src="{{asset('vendors/bower_components/popper.js/dist/umd/popper.min.js')}}"></script>
			<script type="text/javascript" src="{{asset('vendors/bower_components/bootstrap/dist/js/bootstrap.min.js')}}"></script>
			<script type="text/javascript" src="{{asset('vendors/bower_components/select2/dist/js/select2.full.min.js')}}"></script>
			<script type="text/javascript" src="{{asset('js/app.min.js')}}"></script>


			<script type="text/javascript" src="{{asset('vendors/bower_components/bootstrap/dist/js/bootstrap.min.js')}}"></script>


			<script type="text/javascript" src="{{asset('vendors/bower_components/jquery.scrollbar/jquery.scrollbar.min.js')}}"></script>
			<script type="text/javascript" src="{{asset('vendors/bower_components/jquery-scrollLock/jquery-scrollLock.min.js')}}"></script>

			<script src="{{asset('vendors/bower_components/flot/jquery.flot.js')}}"></script>
			<script src="{{asset('vendors/bower_components/flot/jquery.flot.pie.js')}}"></script>
			<script src="{{asset('vendors/bower_components/flot/jquery.flot.resize.js')}}"></script>
			<script src="{{asset('vendors/bower_components/flot.curvedlines/curvedLines.js')}}"></script>
			<script src="{{asset('vendors/bower_components/flot.orderbars/js/jquery.flot.orderBars.js')}}"></script>
			<script src="{{asset('vendors/bower_components/nouislider/distribute/nouislider.min.js')}}"></script>
			<script src="{{asset('vendors/bower_components/dropzone/dist/min/dropzone.min.js')}}"></script>

			<script src="{{asset('vendors/bower_components/datatables.net/js/jquery.dataTables.min.js')}}"></script>
			<script src="{{asset('vendors/bower_components/datatables.net-buttons/js/dataTables.buttons.min.js')}}"></script>
			<script src="{{asset('vendors/bower_components/datatables.net-buttons/js/buttons.print.min.js')}}"></script>
			<script src="{{asset('vendors/bower_components/jszip/dist/jszip.min.js')}}"></script>
			<script src="{{asset('vendors/bower_components/datatables.net-buttons/js/buttons.html5.min.js')}}"></script>
			<script src="{{asset('vendors/bower_components/autosize/dist/autosize.min.js')}}"></script>


			<script src="{{asset('vendors/bower_components/jqvmap/dist/jquery.vmap.min.js')}}"></script>
			<script src="{{asset('vendors/bower_components/jqvmap/dist/maps/jquery.vmap.world.js')}}"></script>
			<script src="{{asset('vendors/bower_components/jquery.easy-pie-chart/dist/jquery.easypiechart.min.js')}}"></script>
			<script src="{{asset('vendors/bower_components/salvattore/dist/salvattore.min.js')}}"></script>
			<script src="{{asset('vendors/jquery.sparkline/jquery.sparkline.min.js')}}"></script>
			<script src="{{asset('vendors/bower_components/moment/min/moment.min.js')}}"></script>


			<script src="{{asset('vendors/bower_components/trumbowyg/dist/trumbowyg.min.js')}}"></script>
			<script src="{{asset('vendors/flatpickr/flatpickr.min.js')}}"></script>
			<script src="vendors/bootstrap-notify/bootstrap-notify.min.js"></script>
			<script src="vendors/sweetalert2/sweetalert2.min.js"></script>
			<script type="text/javascript" src="{{asset('js/base64.js')}}"></script>
			@yield('script')
		</main>
	</body>
</html>
