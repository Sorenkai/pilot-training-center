@extends('layouts.app')

@section('title', 'Training Activities')
@section('title-flex')

@endsection

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Training Activities
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-cookie="true"
                        data-cookie-id-table="activities"
                        data-cookie-expire="90d"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true">
                        <thead class="table-light">
                            <tr>
                                <th data-field="id" data-sortable="false" data-filter-control="input">Training</th>
                                <th data-field="who" data-sortable="false" data-filter-control="input">Who</th>
                                <th data-field="instructor" data-sortable="false" data-filter-control="input">Activity</th>
                                <th data-field="level" data-sortable="false">When</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                            @foreach($entries as $activity)
                                <tr>
                                    <td>
                                        <i class="{{ $statuses[$activity->pilotTraining->status]["icon"] }} text-{{  $statuses[$activity->pilotTraining->status]["color"] }}"></i>
                                        <a href="{{ route('pilot.training.show', $activity->pilotTraining) }}">{{ $activity->pilotTraining->user->name }}'s {{ $activity->pilotTraining->getInlineRatings() }}</a>
                                    </td>
                                    <td>
                                        @if(is_a($activity, 'App\Models\PilotTrainingActivity'))
                                            @isset($activity->user)
                                                {{ $activity->user->name }}
                                            @else
                                                System
                                            @endisset
                                        @elseif(is_a($activity, 'App\Models\PilotTrainingReport'))
                                            {{ $activity->author->name }}
                                        @endif
                                    </td>
                                    <td>

                                        @if(is_a($activity, 'App\Models\PilotTrainingActivity'))

                                            @if($activity->type == "STATUS" || $activity->type == "TYPE")
                                                <i class="fas fa-right-left"></i>
                                            @elseif($activity->type == "INSTRUCTOR")
                                                @if($activity->new_data)
                                                    <i class="fas fa-user-plus"></i>
                                                @elseif($activity->old_data)
                                                    <i class="fas fa-user-minus"></i>
                                                @endif
                                            @elseif($activity->type == "PAUSE")
                                                <i class="fas fa-circle-pause"></i>
                                            @elseif($activity->type == "ENDORSEMENT")
                                                <i class="fas fa-check-square"></i>
                                            @elseif($activity->type == "COMMENT")
                                                <i class="fas fa-comment"></i>
                                            @elseif($activity->type == 'PRETRAINING')
                                                <i class="fas fa-graduation-cap"></i>
                                            @endif

                                            @if($activity->type == "STATUS")
                                                @if(($activity->new_data == -2 || $activity->new_data == -4) && isset($activity->comment))
                                                    Status changed from <span class="badge text-bg-light text-primary">{{ \App\Http\Controllers\PilotTrainingController::$statuses[$activity->old_data]["text"] }}</span>
                                                to <span class="badge text-bg-light">{{ \App\Http\Controllers\PilotTrainingController::$statuses[$activity->new_data]["text"] }}</span>
                                                with reason <span class="badge text-bg-light">{{ $activity->comment }}</span>
                                                @else
                                                    Status changed from <span class="badge text-bg-light">{{ \App\Http\Controllers\PilotTrainingController::$statuses[$activity->old_data]["text"] }}</span>
                                                to <span class="badge text-bg-light">{{ \App\Http\Controllers\PilotTrainingController::$statuses[$activity->new_data]["text"] }}</span>
                                                @endif
                                            @elseif($activity->type == "TYPE")
                                                Training type changed from <span class="badge text-bg-light">{{ \App\Http\Controllers\PilotTrainingController::$types[$activity->old_data]["text"] }}</span>
                                                to <span class="badge text-bg-light">{{ \App\Http\Controllers\PilotTrainingController::$types[$activity->new_data]["text"] }}</span>
                                            @elseif($activity->type == "INSTRUCTOR")
                                                @if($activity->new_data)
                                                    <span class="badge text-bg-light">{{ \App\Models\User::find($activity->new_data)->name }}</span> assigned as instructor
                                                @elseif($activity->old_data)
                                                <span class="badge text-bg-light">{{ \App\Models\User::find($activity->old_data)->name }}</span> removed as instructor
                                                @endif
                                            @elseif($activity->type == "PAUSE")
                                                @if($activity->new_data)
                                                    Training paused
                                                @else
                                                    Training unpaused
                                                @endif
                                            @elseif($activity->type == "ENDORSEMENT")
                                                @if( $activity->endorsement !== null)
                                                    @empty($activity->comment)
                                                        <span class="badge text-bg-light">
                                                            {{ str($activity->endorsement->type)->lower()->ucfirst() }} endorsement
                                                        </span> granted, valid to 
                                                        <span class="badge text-bg-light">
                                                            @isset($activity->endorsement->valid_to)
                                                                {{ $activity->endorsement->valid_to->toEuropeanDateTime() }}
                                                            @else
                                                                Forever
                                                            @endisset
                                                        </span>
                                                    @else
                                                        <span class="badge text-bg-light">
                                                            {{ str($activity->endorsement->type)->lower()->ucfirst() }} endorsement
                                                        </span> granted, valid to 
                                                        <span class="badge text-bg-light">
                                                            @isset($activity->endorsement->valid_to)
                                                                {{ $activity->endorsement->valid_to->toEuropeanDateTime() }}
                                                            @else
                                                                Forever
                                                            @endisset
                                                        </span>
                                                        for positions: 
                                                        @foreach(explode(',', $activity->comment) as $p)
                                                            <span class="badge text-bg-light">{{ $p }}</span>
                                                        @endforeach
                                                    @endempty
                                                @endif
                                            @elseif($activity->type == "COMMENT")
                                                {!! nl2br($activity->comment) !!}

                                                @if($activity->created_at != $activity->updated_at)
                                                    <span class="text-muted">(edited)</span>
                                                @endif
                                            @elseif($activity->type == "PRETRAINING")
                                                Pre-training marked as
                                                <span class="badge text-bg-light">
                                                    @if($activity->new_data)
                                                        <i class="fas fa-check"></i>
                                                        Completed
                                                    @else
                                                        <i class="fas fa-xmark"></i>
                                                        Not completed
                                                    @endif
                                                </span>
                                            @elseif ($activity->type == "EXAM")
                                                <i class="fas fa-graduation-cap"></i>
                                                {{ $activity->comment }}
                                            @endif

                                        @elseif(is_a($activity, 'App\Models\PilotTrainingReport'))
                                            <i class="fas fa-file"></i>
                                            Training report published
                                        @endif

                                    </td>
                                    <td>
                                        <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $activity->created_at->toEuropeanDateTime() }}">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </span>
                                    </td>
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

@section('js')
    @include('scripts.tooltips')
@endsection