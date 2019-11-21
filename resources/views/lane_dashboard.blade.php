@extends('parts.template')

@section('title')
Home
@endsection


@section('content')
	<div class="page-loader">
		<div class="page-loader__spinner">
			<svg viewBox="25 25 50 50">
				<circle cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
			</svg>
		</div>
	</div>

	<header class="content__title">
        <h1>Lane Dashboard</h1>
        <small id="small-sub-title"></small>
        <div class="actions">
        </div>
    </header>

	<div id="div-exit-buttons" class="toolbar">
		<div class="toolbar__nav">
			@foreach ($lane_list as $lane)
			<a href="javascript:ld_on_lane_button_clicked('{{ $lane->id }}','{{ $lane->name }}')">{{ $lane->name }}</a>
			@endforeach
        </div>
	</div>
	<div data-columns>
		<div id="div-last-exit" class="card">
		</div>
		<div id="div-matched-entry" class="card">
		</div>
	</div>
    <div class="modal fade" id="modal-search" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title pull-left">Search Similar Plate NO</h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-10">
                            <input id="input-search" type="text" class="form-control" placeholder="Enter the car plate number">
                        </div>
                        <div class="col-sm-2">
                            <button id="button-search" onclick="ld_on_button_clicked('search','0')" class="btn btn-primary btn-sm ">Search</button>
                        </div>
                    </div>
                    <br>
                    <div id="div-search-result">
                    </div>
                </div>
                <div class="modal-footer ">
                    <button type="button" class="btn btn-link" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script src="js/lane_data.js "></script>
    <script src="js/lane_dashboard.js?20190720"></script>
@endsection

