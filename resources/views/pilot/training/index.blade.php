@extends('layouts.app')

@section('title', 'Pilot Training Requests')
@section('title-flex')
    <div>
        @if (\Auth::user()->isInstructorOrAbove())
            
            <a href="{{ route('pilot.training.create') }}" class="btn btn-outline-success"><i class="fas fa-plus"></i> Add new request</a>
            
        @endif
    </div>
@endSection

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Open pilot training requests</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-cookie="true"
                        data-cookie-id-table="pilot_trainings"
                        data-cookie-expire="90d"
                        data-page-size="100"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true">
                        <thead class="table-light">
                            <tr>
                                <th data-field="state" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">State</th>
                                <th data-field="id" data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-field="name" data-sortable="true" data-filter-control="input">Name</th>
                                <th data-field="level" data-sortable="true" data-filter-control="select" data-filter-strict-search="false">Level</th>                                
                                <th data-fiels="callsign" data-sortable="true" data-filter-control="input">Callsign</th>
                                <th data-field="period" data-sortable="true" data-filter-control="input">Period</th>                                
                                <th data-field="applied" data-sortable="true" data-sorter="tableSortDates" data-filter-control="input">Applied</th>
                                <th data-field="instructor" data-sortable="true" data-filter-control="input">Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($openTrainings as $training)
                                <tr>
                                    <td>
                                        <i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;
                                            <a 
                                                href="/pilot/training/{{ $training->id }}"
                                                class="link-tooltip" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-html="true" 
                                                data-bs-placement="right" 
                                                {{-- data-bs-title="{{ str_replace(["\r\n", "\r", "\n"], '&#013;', $notes) }}" --}}
                                                >
                                                {{ $statuses[$training->status]["text"] }}
                                            </a>
                                        {{ isset($training->paused_at) ? ' (PAUSED)' : ''}}
                                    </td>
                                    <td><a href="{{ route('user.show', $training->user->id)}}">{{ $training->user->id}} </a></td>
                                    <td><a href="{{ route('user.show', $training->user->id)}}">{{ $training->user->name}} </a></td>
                                    <td>{{ $training->pilotRatings[0]->name}}</td>
                                    <td> {{$training->callsign->callsign}} </td>
                                    <td>
                                        @if ($training->started_at == null & $training->closed_at == null)
                                            Training not started
                                        @elseif ($training->closed_at == null)
                                            {{ $training->started_at->toEuropeanDate()}} -
                                        @elseif ($training->started_at != null)
                                            {{ $training->started_at->toEuropeanDate()}} - {{ $trainnig->closed_at->toEuropeanDate()}}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $training->created_at->toEuropeanDate()}}</td>
                                    <td>{{ $training->getInlineInstructors()}}</td>
                                </tr>
                            @endforeach
                        </tbody>

</div>
@endsection