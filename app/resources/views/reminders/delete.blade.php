@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Dashboard</div>

                    <div class="card-body">
                        <h1>Are you sure you want to delete your reminder?</h1>
                        <div class="row">
                            <div class="col-md-6">
                                <a href="{{ asset('/reminders/') }}" class="btn btn-primary">Back</a>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="{{ asset('/reminders/' . $reminder->id ."/delete/confirm") }}" class="btn btn-danger">Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
