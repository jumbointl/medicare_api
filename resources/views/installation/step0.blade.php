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
                    <hr>
                </div>
            </div>
            <div class="row mt-4 card-main">
                <div class="col-3">
                    <p>Before starting the installation process, please collect this information. Without this
                        information, you won't be able to complete the installation process.</p>
                </div>
                <div class="d-flex group-main">
                    <div class="col-md-12 group">
                        <p>REQUIRED DATABASE DETAILS -</p>
                        <ol class="list-group">

                            <li class="list-group-item text-semibold"><i class="fas fa-database"></i> Database Name</li>
                            <li class="list-group-item text-semibold"><i class="fas fa-user"></i> Database Username</li>
                            <li class="list-group-item text-semibold"><i class="fas fa-key"></i> Database Password</li>
                            <li class="list-group-item text-semibold"><i class="fas fa-server"></i> Database Hostname</li>
                        </ol>


                        <p class="pt-5 final-last-text">
                            We will check permission to write several files,proceed..
                        </p>
                        <br>

                    </div>
                </div>

         <div class="btn-main">
         <a href="{{ route('step1') }}" class="btn btn-custom">
            Ready? Then start <i class="fas fa-arrow-right"></i>
        </a>
         </div>



            </div>
        </div>
    </div>
 

    <link rel="stylesheet" href="{{ url('css/css.css') }}">
    <link rel="stylesheet" href="{{ url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css') }}">

</div>
@endsection