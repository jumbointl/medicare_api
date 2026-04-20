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
                        <div class="col-12">
                            @if (session()->has('error'))
                                <div class="alert alert-danger" role="alert">
                                    {{ session('error') }}
                                </div>
                            @endif
                           
                         
                    </div>   <hr>
                </div>
            </div>


            <div class="card-main">
                <div class="d-flex group-main">
                    <div class="group-out">
                        
                        <p class="step-name">STEP 5. Admin Account Settings</p>
                        <p class="step-subhead">Provide your information.
                        </p>
                        <div class="group">
                            <div class="grp-form">
                                <form class="form-section" method="POST" action="{{ route('system_settings') }}">
                                    @csrf
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group custom-form-group">
                                                <label class="label-section" for="system_name">Business Name</label>
                                                <input type="text" class="form-control input-section"
                                                    name="business_name" required>
                                            </div>
                                        </div>

                                        <div class="col-6">
                                            <div class="form-group custom-form-group">
                                                <label class="label-section" for="admin_name">Name</label>
                                                <input type="text" class="form-control input-section" name="f_name"
                                                    required>
                                            </div>
                                        </div>

                                        <!-- <div class="col-6">
                                            <div class="form-group">
                                                <label for="admin_name">Last Name</label>
                                                <input type="text" class="form-control" name="l_name" required>
                                            </div>
                                        </div> -->

                                        <div class="col-12">
                                            <div class="form-group custom-form-group">
                                                <label class="label-section" for="admin_phone">Phone Number (Ex :
                                                    124567890)</label>
                                                <input type="text" class="form-control input-section" name="phone"
                                                    required>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-group custom-form-group">
                                                <label class="label-section" for="admin_email">Business Email</label>
                                                <input type="email" class="form-control input-section" id="admin_email"
                                                    name="email" required>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-group custom-form-group">
                                                <label class="label-section" for="admin_password">Admin Password (At
                                                    least 8
                                                    characters)</label>
                                                <input type="text" class="form-control input-section"
                                                    id="admin_password" name="password" required>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="text-center">
                                                <button type="submit" class="btn btn-custom">Continue</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>

            </div>


        </div>
    </div>
    <link rel="stylesheet" href="{{ asset('css/css.css') }}">
</div>
@endsection