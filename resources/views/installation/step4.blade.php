@extends('layouts.blank')
@section('content')
    <div class="container center-content mainSection">
        <div class="card mt-6">
            <div class="card-body">
                <div class="card-header">
                    <div class="row" style="width: 100%">
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
                           
                            <hr>

                        </div>
                    </div>
                </div>

                <div class="card-main">
                    <div class="d-flex group-main">
                        <div class="group-out">

                            <p class="step-name">STEP 4. Import Software Database</p>

                            </p>
                            <div class="group">

                                <div class="col-md-12">

                                    <p class="text-muted font-13 text-center db-imp-text">
                                        Your database connection is successful. Click <strong> 'Install Database'</strong>
                                        to proceed. This automated process will configure your database settings.

                                    </p>
                                    @if (session()->has('error'))
                                        <div class="text-center mar-top pad-top">
                                            <a href="{{ route('force-import-sql') }}" class="btn btn-danger"
                                                onclick="showLoder()">Force
                                                Install Database</a>
                                        </div>
                                    @else
                                        <div class="text-center mar-top pad-top">
                                            <a href="{{ route('import_sql') }}" class="btn btn-custom"
                                                onclick="showLoder()">Install
                                                Database</a>
                                        </div>
                                    @endif

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

@section('scripts')
    <script type="text/javascript">
        function showLoder() {
            $('#loading').fadeIn();
        }
    </script>
@endsection
