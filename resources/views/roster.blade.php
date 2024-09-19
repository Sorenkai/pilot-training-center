@extends('layouts.app')

@section('title', 'Instructor Roster')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Instructors</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true"
                        data-sort-select-options="true"
                        >
                        <thead class="table-light">
                            <tr>
                                <th data-field="member" class="w-50" data-sortable="true" data-filter-control="input">Member</th>
                                @foreach($ratings as $r)
                                    <th data-field="{{ $r->id }}" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">{{ $r->description }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                                <tr>
                                    <td>
                                        @can('view', $u)
                                            <a href="{{ route('user.show', $u->id) }}">{{ $u->name }} ({{ $u->id }})</a>
                                        @else
                                            {{ $u->name }} ({{ $u->id }})
                                        @endcan
                                    </td>

                                    @foreach($ratings as $r)
                                        @if ($u->pilotrating >= $r->vatsim_rating)
                                            <td class="text-center bg-success text-white">
                                                <i class="fas fa-check-circle"></i><span class="d-none">Approved</span>
                                            </td>
                                        @endif
                                    @endforeach
                                    
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