@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Login') }}</div>

                    <div class="card-body">
                        <h1>Click confirm below to continue linking your account</h1>
                        <p class="text-center"><a href="{{ asset('/link/confirm') }}" class="btn btn-primary">Confirm</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
