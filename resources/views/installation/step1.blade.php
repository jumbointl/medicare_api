@extends('layouts.blank')

@section('content')
<div class="container center-content mainSection">
    <div class="card mt-6">
        <div class="card-body">
            <div class="card-header">
                <div class="row" style="width: 100%">
                    <div class="col-12">
                        <div class="text-center main-header">
                            <h1>Medicare Software Installation</h1>
                            <h4>Please proceed step by step with proper data according to instructions</h4>
                        </div>
                    </div>
                </div>
                <hr>
            </div>
            <div class="row pt-5">
                <div class="card-main">
                    <p class="step-name-1">STEP 1 - Check & Verify File Permissions</p>
                    <div class="d-flex group-main">
                        <div class="col-md-12 group grp-step-2">
                            <ul class="list-group">
                                <li class="list-group-item text-semibold">
                                    PHP version 7.4 +

                                    @php
                                    $phpVersion = number_format((float)phpversion(), 2, '.', '');
                                    @endphp
                                    @if ($phpVersion >= 7.4)
                                    <i class="fas fa-check-circle text-success pull-right"></i>
                                    @else
                                    <i class="fas fa-circle-xmark text-danger pull-right"></i>
                                    @endif
                                </li>
                                <li class="list-group-item text-semibold">
                                    Curl Enabled

                                    @if ($permission['curl_enabled'])
                                    <i class="fas fa-check-circle text-success pull-right"></i>
                                    @else
                                    <i class="fas fa-circle-xmark text-danger pull-right"></i>
                                    @endif
                                </li>
                                <li class="list-group-item text-semibold">
                                    <b>.env</b> File Permission

                                    @if ($permission['db_file_write_perm'])
                                    <i class="fas fa-check-circle text-success pull-right"></i>
                                    @else
                                    <i class="fas fa-circle-xmark text-danger pull-right"></i>
                                    @endif
                                </li>
                                <li class="list-group-item text-semibold">
                                    <b>RouteServiceProvider.php</b> File Permission

                                    @if ($permission['routes_file_write_perm'])
                                    <i class="fas fa-check-circle text-success pull-right"></i>
                                    @else
                                    <i class="fas fa-circle-xmark text-danger pull-right"></i>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>

                    <br />

                    <p class="text-center pt-3">
                        @if ($permission['curl_enabled'] == 1 && $permission['db_file_write_perm'] == 1 && $permission['routes_file_write_perm'] == 1 && $phpVersion >= 7.4)
                        <a href="{{ route('step2') }}" class="btn btn-custom">Next <i class="fa fa-forward"></i></a>
                        @endif
                    </p>
                </div>
                <div class="col-3"></div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="{{ url('css/css.css') }}">
    <link rel="stylesheet" href="{{ url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css') }}">

</div>
@endsection