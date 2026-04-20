@extends('layouts.blank')
@section('content')
<div class="container center-content mainSection">
    <div class="card mt-6">
        <div class="card-body">
            <div class="row pt-5">
                <div class="col-md-12">
                    <div class="text-center main-header">
                        <h1>Medicare Software Installation</h1>
                        <h4>Software Activated</h4>
                    </div>
                    <hr>

                    <div class="card-success">
                        <div class="circle">
                            <i class="checkmark">✓</i>
                        </div>
                        <h1>All Done, Great Job.</h1>
                        <p>Your Software is ready to run.</p>
                    </div>
                </div>

                <div class="text-center pt-3">
                    <a href="https://medicaredocumention.techashna.com/" target="_blank" class="btn btn-success">
                        Continue Installation</a>
                </div>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="{{ asset('css/css.css') }}">
</div>
@endsection