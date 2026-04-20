@extends('layouts.blank')
@section('content')
    <div class="container center-content mainSection ">
        <div class="card mt-6">
            <div class="card-body">
                <div class="card-header">
                    <div class="row" style="width: 100%">
                        <div class="text-center main-header">
                            <h1>Medicare Software Installation</h1>
                            <h4>Please proceed step by step with proper data according to instructions</h4>
                        </div>
                        <hr>

                        <div class="card-main">
                            <div class="d-flex group-main">
                                <div class="group-out">
                                    <div class="where">
                                        <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code"
                                            class="text-info">Where to get purchase code ?</a>
                                        </p>
                                    </div>
                                    <p class="step-name">STEP 2. Update Purchase Information</p>
                                    <p class="step-subhead">Please provide your <strong>Codecanyon username</strong> and
                                        <strong>purchase code</strong> below.
                                    </p>
                                    <div class="group">
                                        <div class="grp-form">
                                            <form class="form-section" method="POST" action="{{ route('purchase.code') }}">
                                                @csrf
                                                <div class="form-group custom-form-group d-flex">
                                                    <div class="col-md-6">
                                                        <label class="label-section" for="purchase_code">Codecanyon
                                                            Username</label>
                                                        <input type="text" value="{{ env('BUYER_USERNAME') }}"
                                                            class="form-control input-section" id="username"
                                                            name="username" required placeholder="Ex: - Jhon">
                                                    </div>

                                                    <div class="col-md-6 code">
                                                        <label class="label-section" for="purchase_code">Purchase
                                                            Code</label>
                                                        <input type="text" value="{{ env('PURCHASE_CODE') }}"
                                                            class="form-control input-section" id="purchase_key"
                                                            name="purchase_key" required placeholder="Ex: x0sxxxxsxx-7a07-4e61-xxxx-xxxxeb4750a6 ">
                                                    </div>
                                                </div>

                                                <div class="text-center">
                                                    <button type="submit" class="btn btn-custom">Proceed to
                                                        Install</button>
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
