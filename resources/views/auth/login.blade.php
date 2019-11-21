<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
         <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>KIPLEPARK</title>

        <!-- Styles -->
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">

        <link href="{{ asset('vendors/bower_components/material-design-iconic-font/dist/css/material-design-iconic-font.min.css') }}" rel="stylesheet">
        <link href="{{ asset('vendors/bower_components/animate.css/animate.min.css') }}" rel="stylesheet">

    </head>
    <body data-ma-theme="blue">

        <div class="login">

            <!-- Login -->
            <div class="login__block active" id="l-login">
                <div class="login__block__header" style="height:130px;">
<!--                    <i class="zmdi zmdi-account-circle"></i>-->
					<br>
                    <img src="{{asset('img/kiple-logo-white.png')}}" alt="Smiley face">
                    <!-- <br><br>
                    <font size='4'><b>KIPLEPARK PSM</b></font> -->

                </div>

                <div class="login__block__body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group form-group--float form-group--centered">
                            <input id="email" type="text"
                                   class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                                   name="email" value="{{ old('email') }}">
                            <label>Email</label>
                            <i class="form-group__bar"></i>
                            @if ($errors->has('email'))
                            <span class="invalid-feedback">
                                <strong>{{ $errors->first('email') }}</strong>
                            </span>
                            @endif
                        </div>

                        <div class="form-group form-group--float form-group--centered">
                            <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" autocomplete="off">
                            <i class="form-group__bar"></i> 
                            <label>Password</label>
                            <i class="form-group__bar"></i>
                            @if ($errors->has('password'))
                            <span class="invalid-feedback">
                                <strong>{{ $errors->first('password') }}</strong>
                            </span>
                            @endif
                        </div>

                        <button href="index.html" class="btn btn--icon login__block__btn"><i class="zmdi zmdi-long-arrow-right"></i></button>
                    </form>
                    <br>
                    © 2019 kiplePark. All Rights Reserved.
                </div>
            </div>
 
        </div>

        <script src="{{asset('vendors/bower_components/jquery/dist/jquery.min.js')}}"></script>
        <script src="{{asset('vendors/bower_components/popper.js/dist/umd/popper.min.js')}}"></script>
        <script src="{{asset('vendors/bower_components/bootstrap/dist/js/bootstrap.min.js')}}"></script>

        <!-- Scripts -->
        <script src="{{ asset('js/app.min.js') }}"></script>
    </body>
</html>

<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
