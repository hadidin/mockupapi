<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">Local Parking Server</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                {{--<li><a href="#">Dashboard</a></li>--}}
                <li><a href="{{ url('/card_list') }}">Season card list</a></li>
                <li><a href="{{ url('/car_in_site') }}">Car in-site</a></li>
                <li><a href="{{ url('/entry_log_db') }}">Entry log</a></li>
                <li><a href="{{ url('/entry_history') }}">Season Parking</a></li>
                <li><a href="">Profile</a></li>
            </ul>
            {{--<form class="navbar-form navbar-right">--}}
                {{--<input type="text" class="form-control" placeholder="Search...">--}}
            {{--</form>--}}
        </div>
    </div>
</nav>