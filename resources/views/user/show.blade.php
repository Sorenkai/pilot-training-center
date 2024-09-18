@extends('layouts.app')

@section('title', 'User Details')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-3 col-md-4 col-sm-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    <i class="fas fa-user"></i>&nbsp;{{ $user->first_name.' '.$user->last_name }}
                </h6>
            </div>
            <div class="card-body">

                <dl class="copyable">
                    <dt>VATSIM ID</dt>
                    <dd>
                        {{ $user->id }}
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $user->id }}')"><i class="fas fa-copy"></i></button>
                        <a href="https://stats.vatsim.net/stats/{{ $user->id }}" target="_blank" title="VATSIM Stats" class="link-btn me-1"><i class="fas fa-chart-simple"></i></button></a>
                        @if($user->division == 'EUD' && Auth::user()->isModeratorOrAbove())
                            <a href="https://core.vateud.net/manage/controller/{{ $user->id }}/view" target="_blank" title="VATEUD Core Profile" class="link-btn"><i class="fa-solid fa-earth-europe"></i></button></a>
                        @endif
                    </dd>

                    <dt>Name</dt>
                    <dd>{{ $user->first_name.' '.$user->last_name }}<button type="button" onclick="navigator.clipboard.writeText('{{ $user->first_name.' '.$user->last_name }}')"><i class="fas fa-copy"></i></button></dd>

                    <dt>Email</dt>
                    <dd class="separator pb-3">{{ $user->notificationEmail }}<button type="button" onclick="navigator.clipboard.writeText('{{ $user->notificationEmail }}')"><i class="fas fa-copy"></i></button></dd>

                    <dt class="pt-2">ATC Rating</dt>
                    <dd>{{ $user->rating_short }}</dd>

                    <dt>Sub/Division</dt>
                    <dd class="separator pb-3">{{ $user->subdivision }} / {{ $user->division }}</dd>

                    <dt class="pt-2">ATC Active</dt>
                    <dd>
                        @if($user->isVisiting())
                            <i class="far fa-circle-check text-success"></i>
                            Visiting
                        @else
                            <i class="fas fa-circle-{{ $user->isAtcActive() ? 'check' : 'xmark' }} text-{{ $user->isAtcActive() ? 'success' : 'danger' }}"></i> {{ round($totalHours) }} hours
                        @endif
                    </dd>

                    <dt>ATC Hours</dt>

                    <div id="vatsim-data">
                        <dt class="pt-2">VATSIM Stats&nbsp;<a href="https://stats.vatsim.net/stats/{{ $user->id }}" target="_blank"><i class="fas fa-link"></i></a></dt>
                    </div>

                    <dd class="separator pb-3"></dd>

                    <dt class="pt-2">Last login</dt>
                    <dd>{{ $user->last_login->toEuropeanDateTime() }}</dd>

                    @if(\Auth::user()->isModeratorOrAbove())
                        <dt class="pt-2">Last activity</dt>
                        <dd>{{ isset($user->last_activity) ? $user->last_activity->toEuropeanDateTime() : 'N/A' }}</dd>
                    @endif

                </dl>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Activity
                </h6>
            </div>
            <div class="card-body">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Instructing
                </h6>
                <a href="{{ route('user.reports', $user->id) }}" class="btn btn-icon btn-light"><i class="fas fa-file"></i> See reports</a>
            </div>
            <div class="card-body {{ $user->instructs->count() == 0 ? '' : 'p-0' }}">

                @if($user->instructs->count() == 0)
                    <p class="mb-0">No registered students</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                            <thead class="table-light">
                                <tr>
                                    <th data-sortable="true" data-filter-control="select">Instructs</th>
                                    <th data-sortable="true" data-filter-control="input">Expires</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->instructs as $training)
                                <tr>
                                    <td><a href="{{ route('user.show', $training->user->id) }}">{{ $training->user->name }}</a></td>
                                    <td>{{ Carbon\Carbon::parse($user->instructs->find($training->id)->pivot->expire_at)->toEuropeanDate() }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <div class="col-xl-9 col-md-8 col-sm-12 mb-12">
        <div class="row">
            <div class="col-xl-8 col-lg-12 col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-white">
                            Trainings
                        </h6>
                        @can('create', \App\Models\Training::class)
                            <a href="{{ route('training.create.id', $user->id) }}" class="btn btn-icon btn-light"><i class="fas fa-plus"></i> Add new training</a>
                        @endcan
                    </div>
                    <div class="card-body {{ $trainings->count() == 0 ? '' : 'p-0' }}">
        
                        @if($trainings->count() == 0)
                            <p class="mb-0">No registered trainings</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>State</th>
                                            <th>Level</th>
                                            <th>Callsign</th>
                                            <th>Applied</th>
                                            <th>Ended</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($trainings as $training)
                                        <tr>
                                            <td>
                                                <i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;<a href="/training/{{ $training->id }}">{{ $statuses[$training->status]["text"] }}</a>{{ isset($training->paused_at) ? ' (PAUSED)' : '' }}
                                            </td>
                                            <td>
                                                @if ( is_iterable($ratings = $training->pilotRatings->toArray()) )
                                                    @for( $i = 0; $i < sizeof($ratings); $i++ )
                                                        @if ( $i == (sizeof($ratings) - 1) )
                                                            {{ $ratings[$i]["name"] }}
                                                        @else
                                                            {{ $ratings[$i]["name"] . " + " }}
                                                        @endif
                                                    @endfor
                                                @else
                                                    {{ $ratings["name"] }}
                                                @endif
                                            </td>
                                            <td>
                                                {{ $training->callsign->callsign }}
                                            </td>
                                            <td>
                                                {{ $training->created_at->toEuropeanDate() }}
                                            </td>
                                            <td>
                                                @if ($training->closed_at != null)
                                                    {{ $training->closed_at->toEuropeanDate() }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        
                    </div>
                </div>
            </div>
        
            <div class="col-xl-4 col-lg-12 col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-white">
                            Division Exams
                        </h6>
                    </div>
                    <div class="card-body {{ $divisionExams->count() == 0 ? '' : 'p-0' }}">
        
                        @if($divisionExams->count() == 0)
                            <p class="mb-0">No division exam history</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Exam</th>
                                            <th>Created</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($divisionExams as $exam)
                                            <tr>
                                                <td>
                                                    {{ $exam['rating'] }}
                                                    @if($exam['category'] == 'reassignments')
                                                        (Retake)
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $exam['created_at'] }}
                                                </td>
                                                <td>
                                                    @if($exam['category'] == 'results')
                                                        @if($exam['passed'])
                                                            <i class="fas fa-circle-check text-success"></i>
                                                            Pass {{ $exam['score'] }}%
                                                        @else
                                                            <i class="fas fa-circle-xmark text-danger"></i>
                                                            Fail {{ $exam['score'] }}%
                                                        @endif
                                                    @else
                                                        <i class="fas fa-circle-half-stroke text-warning"></i>
                                                        Pending
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
        
                    </div>
                </div>
            </div>
        </div>

        @if (\Illuminate\Support\Facades\Gate::inspect('viewAccess', $user)->allowed())
            <div class="col-xl-12 col-lg-12 col-md-12 mb-12 p-0">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-white">
                            Access
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('user.update', $user->id) }}" method="POST">
                            @method('PATCH')
                            @csrf

                            <p>Select none, one or multiple permissions for the user.</p>

                            <table class="table table-bordered table-hover table-responsive w-100 d-block d-md-table">
                                <thead>
                                    <tr>
                                        <th>Area</th>
                                        @foreach($groups as $group)
                                            <th class="text-center">{{ $group->name }} <i class="fas fa-question-circle text-gray-400" title="{{ $group->description }}"></i></th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach($areas as $area)
                                        <tr>
                                            <td>{{ $area->name }}</td>

                                            @foreach($groups as $group)

                                                @php
                                                    $shouldRender = !($group->id == 4 && $area->id != 2);
                                                    $hasPermission = \Illuminate\Support\Facades\Gate::inspect('updateGroup', [$user, $group, $area])->allowed();
                                                    $isChecked = $user->groups()->where('group_id', $group->id)->where('area_id', $area->id)->count() > 0;
                                                @endphp

                                                @if ($shouldRender)
                                                    <td class="text-center">
                                                        <input type="checkbox" name="{{ $area->id }}_{{ $group->name }}" {{ $isChecked ? 'checked' : '' }} {{ !$hasPermission || $group->id == 1 ? 'disabled' : '' }}>
                                                    </td>
                                                @else
                                                    <td class="text-center">
                                                        <input type="checkbox" disabled>
                                                    </td>
                                                @endif
                                                
                                            @endforeach

                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>

                            @if (\Illuminate\Support\Facades\Gate::inspect('update', $user)->allowed())
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">Save access</button>
                                </div>
                            @endif

                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

</div>

@endsection

@section('js')

    <!-- Flatpickr -->
    @include('scripts.tooltips')
    @vite(['resources/js/flatpickr.js', 'resources/sass/flatpickr.scss', 'resources/js/chart.js'])
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll('.flatpickr').flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d') !!}", dateFormat: "Y-m-d", locale: {firstDayOfWeek: 1 }, wrap: true, altInputClass: "hide",
                onChange: function(selectedDates, dateStr, instance) {
                    if(confirm('Are you sure you want to shorten this endorsement expire date to '+dateStr+'? Student will be notified by e-mail.')){
                        window.location.replace("/endorsements/shorten/"+instance.input.dataset.endorsementId+"/"+dateStr);
                    }
                },
                onReady: function(dateObj, dateStr, instance){ instance.config.maxDate = instance.input.dataset.date }
            });
        });
    </script>

    <!-- VATSIM Data Fetch -->
    <script>
        fetch("{{ route('user.vatsimhours') }}?cid={{ $user->id }}")
            .then(response => response.json())
            .then(data => {
                var vatsimHours = document.getElementById("vatsim-data");
    
                if (data.data) {
                    for (let key in data.data) {
                        if (key === "pilot") {
                            vatsimHours.innerHTML += "<dd class='mb-0'>Pilot: " + Math.round(data.data[key]) + "h</dd>"
                        } else if (key !== "id" && key !== "pilot" && key !== "atc" && data.data[key] > 0) {
                            vatsimHours.innerHTML += "<dd class='mb-0'>" + key.toUpperCase() + ": " + Math.round(data.data[key]) + "h</dd>"
                        }
                    }
                } else {
                    vatsimHours.innerHTML = vatsimHours.innerHTML + "<dd>No Data</dd>"
                }
            })
            .catch(error => {
                console.error(error);
                alert('An error occurred while fetching VATSIM hours data.');
            });
    </script>    

    <!-- Activity chart -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            // Fetch activity data
            fetch("https://statsim.net/atc/vatsimid/?vatsimid={{ $user->id }}&period=custom&from={{ now()->subMonths(11)->toDateString() }}+00%3A00&to={{ now()->toDateString() }}+22%3A00&json=true")
                .then(response => response.json())
                .then(data => {
                    if(data && data.length > 0) {

                        // Process each connection and calculate hours
                        data.forEach(function (connection) {
                            connection.logontime = new Date(connection.logontime * 1000)
                            connection.logofftime = new Date(connection.logofftime * 1000)
                            connection.hours = parseFloat(((connection.logofftime - connection.logontime) / 1000 / 60 / 60).toFixed(1))
                            connection.callsignSuffix = connection.callsign.split('_').pop()
                        })

                        // Create chart labels based on the last 11 months
                        var activity = []

                        for (var i = 11; i >= 0; i--) {
                            activity[new Date(new Date().setMonth(new Date().getMonth() - i)).toLocaleString('default', { month: 'short' })] = 0
                        }

                        data.forEach(function (connection) {
                            var month = connection.logontime.toLocaleString('default', { month: 'short' })
                            activity[month] += connection.hours
                        })

                        // Define labels and chart data
                        var chartLabels = Object.keys(activity)
                        var chartData = Object.values(activity)
                    
                        // Create the chart
                        var chart = new Chart(
                            document.getElementById('activityChart'),
                            {
                                type: 'bar',
                                data: {
                                    labels: chartLabels,
                                    datasets: [{
                                        label: 'Hours online',
                                        data: chartData,
                                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                        borderColor: 'rgb(54, 162, 235)',
                                        borderWidth: 1
                                    }]
                                },
                            }
                        );
                        
                    } else {
                        document.getElementById('activityChart').parentElement.innerHTML = '<p class="mb-0">No data available</p>'
                    }
                })
                .catch(error => {
                    console.error(error);
                    alert('An error occurred while fetching STATSIM hours data.');
                });
        });
    </script>

@endsection
