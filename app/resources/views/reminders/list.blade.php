@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Dashboard</div>

                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th scope="col">Id</th>
                                <th scope="col">Reminder Title</th>
                                <th scope="col">For</th>
                                <th scope="col">Frequency</th>
                                <th scope="col">Time</th>
                                <th scope="col">Last Reminded</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                            @foreach($reminders as $reminder)
                                <tr>
                                    <th scope="row">{{ $reminder->id }}</th>
                                    <td>{{ $reminder->title }}</td>
                                    <td>{{ $reminder->name }}</td>
                                    <td>{{ $reminder->frequency }}</td>
                                    <td>{{ $reminder->time }}</td>
									@if($reminder->confirmations()->count() > 0)
										@php($confirmation = $reminder->getLatestConfirmation())
										<td>{{ $confirmation->reminder_time }}</td>
										<td class="text-{{ $confirmation->confirmed ? "success" : "danger" }}">{{ $confirmation->confirmed ? 'Confirmed' : 'Not Confirmed'}}</td>
									@else
										<td>Never</td>
										<td>N/A</td>										
									@endif
                                    <td>
                                        <a href="{{ asset('reminders/' . $reminder->id) }}" class="btn btn-primary">Edit</a>
                                        <a href="{{ asset('reminders/' . $reminder->id. '/delete') }}" class="btn btn-danger">Delete</a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
