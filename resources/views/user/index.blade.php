@extends('layouts.app')

@section('title', 'Member Overview')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">{{ Config::get('app.owner_name_short') }} Members</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-page-size="15"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true">
                        <thead class="table-light">
                            <tr>
                                <th data-field="id" data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-field="firstname" data-sortable="true" data-filter-control="input">First Name</th>
                                <th data-field="lastname" data-sortable="true" data-filter-control="input">Last Name</th>
                                <th data-field="rating" data-sortable="true" data-filter-control="select" data-filter-strict-search="true">Pilot Rating</th>
                                <th>Last Training</th>
                                <th>Joined VATSIM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                        <td><a href="{{ route('user.show', $user['id']) }}">{{ $user['id'] }}</a></td>
                                        <td>{{ $user->first_name }}</td>
                                        <td>{{ $user->last_name }}</td>
                                        <td>{{ $user->pilotrating_long}}</td>
                                        <td>
                                            @php
                                                $pilotTraining = $user->pilotTrainings()->first();
                                            @endphp
                                            {{ $pilotTraining ? $pilotTraining->updated_at->diffForHumans() : 'N/A' }}
                                        </td>
                                        <td>{{ Carbon\Carbon::create($user['reg_date'])->toEuropeanDate() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
