@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Dashboard</div>

                    <div class="card-body">
                        @if (session('updated'))
                            <div class="alert alert-success">
                                Updated
                            </div>
                        @endif
                        <form method="POST" action="{{ asset('reminders/' . $reminder->id) }}">
                            @csrf
                            <div class="form-group">
                                <label for="title">Reminder Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="{{ $reminder->title }}"/>
                            </div>
                            <div class="form-group">
                                <label for="name">Reminder For</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ $reminder->name }}"/>
                            </div>
                            <div class="form-group">
                                <label for="frequency">Reminder Frequency</label>
<!--                                //value="{{ $reminder->frequency }}"-->
                                <select class="form-control" id="frequency" name="frequency" >
                                    <option value="DAILY" @if($reminder->frequency == "DAILY") selected @endif>Daily</option>
                                    <option value="WEEKLY" @if($reminder->frequency == "WEEKLY") selected @endif>Weekly</option>
                                    <option value="MONTHLY" @if($reminder->frequency == "MONTHLY") selected @endif>Monthly</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="time">Time</label>
                                <input type="text" class="form-control" id="time" name="time" value="{{ $reminder->time }}"/>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="{{ asset('reminders') }}" class="btn btn-danger">back</a>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button type="submit" class="btn btn-primary">Update</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
