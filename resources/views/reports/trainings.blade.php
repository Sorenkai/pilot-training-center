@extends('layouts.app')

@section('title', 'Training Statistics')
@section('title-flex')

@endsection
@section('content')

<div class="row">

    <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-secondary shadow h-100 py-2">
        <div class="card-body">
        <div class="row g-0 align-items-center">
            <div class="col me-2">
            <div class="fs-sm fw-bold text-uppercase text-gray-600 mb-1">In queue</div>
            <div class="h5 mb-0 fw-bold text-gray-800">{{ $cardStats["waiting"] }} requests</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-hourglass fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
    <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
        <div class="row g-0 align-items-center">
            <div class="col me-2">
            <div class="fs-sm fw-bold text-warning text-uppercase mb-1">In training</div>
            <div class="h5 mb-0 fw-bold text-gray-800">{{ $cardStats["training"] }} requests</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-book-open fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
        <div class="row g-0 align-items-center">
            <div class="col me-2">
            <div class="fs-sm fw-bold text-info text-uppercase mb-1">Awaiting exam</div>
            <div class="h5 mb-0 fw-bold text-gray-800">{{ $cardStats["exam"] }} requests</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
            <div class="row g-0 align-items-center">
                <div class="col me-2">
                <div class="fs-sm fw-bold text-success text-uppercase mb-1">Completed this year</div>
                <div class="row g-0 align-items-center">
                    <div class="col-auto">
                        <div class="h5 mb-0 me-3 fw-bold text-gray-800">{{ $cardStats["completed"] }} requests</div>
                    </div>
                </div>
                </div>
                <div class="col-auto">
                <i class="fas fa-check fa-2x text-gray-300"></i>
                </div>
            </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
            <div class="row g-0 align-items-center">
                <div class="col me-2">
                <div class="fs-sm fw-bold text-danger text-uppercase mb-1">Closed this year</div>
                <div class="row g-0 align-items-center">
                    <div class="col-auto">
                        <div class="h5 mb-0 me-3 fw-bold text-gray-800">{{ $cardStats["closed"] }} requests</div>
                    </div>
                </div>
                </div>
                <div class="col-auto">
                <i class="fas fa-ban fa-2x text-gray-300"></i>
                </div>
            </div>
            </div>
        </div>
    </div>

</div>

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Training requests last 12 months
                </h6> 
            </div>
            <div class="card-body">
                <canvas id="trainingChart"></canvas>
            </div>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-xl-4 col-md-12 mb-12 d-none d-xl-block d-lg-block d-md-block">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    New requests last 6 months
                </h6> 
            </div>
            <div class="card-body">
                <canvas id="newTrainingRequests"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12 mb-12 d-none d-xl-block d-lg-block d-md-block">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Completed requests last 6 months
                </h6> 
            </div>
            <div class="card-body">
                <canvas id="completedTrainingRequests"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12 mb-12 d-none d-xl-block d-lg-block d-md-block">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Closed requests last 6 months
                </h6> 
            </div>
            <div class="card-body">
                <canvas id="closedTrainingRequests"></canvas>
            </div>
        </div>
    </div>
</div>


@endsection

@section('js')
@vite('resources/js/chart.js')
<script>

    document.addEventListener("DOMContentLoaded", function () {

        // Total training amount chart
        var ctx = document.getElementById('trainingChart').getContext('2d');
        ctx.canvas.width = 1000;
        ctx.canvas.height = 200;

        var requestData = {!! json_encode($totalRequests) !!} 
        
        var color = Chart.helpers.color;
        var cfg = {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Training Requests',
                    borderColor: 'rgb(255, 100, 100)',
                    backgroundColor: 'rgba(255,50,50, 0.1)',
                    pointBackgroundColor: 'rgb(255,75,75)',
                    pointRadius: 1,
                    data: requestData,
                    fill: {
                        target: 'origin',
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'month',
                            tooltipFormat:'DD/MM/YYYY', 
                        },
                        ticks: {
                            major: {
                                enabled: true,
                                fontStyle: 'bold'
                            },
                        },
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Requests'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
            }
        };

        var chart = new Chart(ctx, cfg);

    });

</script>

<script>

    document.addEventListener("DOMContentLoaded", function () {

        // New request chart
        var newRequestsData = {!! json_encode($newRequests) !!}

        var barChartData = {
            labels: [moment().subtract(6, "month").startOf("month").format('MMMM'),
                    moment().subtract(5, "month").startOf("month").format('MMMM'),
                    moment().subtract(4, "month").startOf("month").format('MMMM'),
                    moment().subtract(3, "month").startOf("month").format('MMMM'),
                    moment().subtract(2, "month").startOf("month").format('MMMM'),
                    moment().subtract(1, "month").startOf("month").format('MMMM'),
                    moment().startOf("month").format('MMMM')],
            datasets: [{
                label: 'PPL',
                backgroundColor: 'rgb(250, 150, 150)',
                data: newRequestsData["PPL"]
            }, {
                label: 'IR',
                backgroundColor: 'rgb(200, 100, 100)',
                data: newRequestsData["IR"]
            }, {
                label: 'CMEL',
                backgroundColor: 'rgb(100, 100, 200)',
                data: newRequestsData["CMEL"]
            }, {
                label: 'ATPL',
                backgroundColor: 'rgb(100, 200, 100)',
                data: newRequestsData["ATPL"]
            }]
        };

        var mix = document.getElementById("newTrainingRequests").getContext('2d');
        var newTrainingRequests = new Chart(mix, {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Note: One request may have multiple ratings shown indvidually in this graph'
                        }
                    },
                    y: {
                        stacked: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    });
</script>

<script>

    document.addEventListener("DOMContentLoaded", function () {

        // Completed requests chart
        var completedRequestsData = {!! json_encode($completedRequests) !!}

        var barChartData = {
            labels: [moment().subtract(6, "month").startOf("month").format('MMMM'),
                    moment().subtract(5, "month").startOf("month").format('MMMM'),
                    moment().subtract(4, "month").startOf("month").format('MMMM'),
                    moment().subtract(3, "month").startOf("month").format('MMMM'),
                    moment().subtract(2, "month").startOf("month").format('MMMM'),
                    moment().subtract(1, "month").startOf("month").format('MMMM'),
                    moment().startOf("month").format('MMMM')],
            datasets: [{
                label: 'PPL',
                backgroundColor: 'rgb(250, 150, 150)',
                data: completedRequestsData["PPL"]
            }, {
                label: 'IR',
                backgroundColor: 'rgb(200, 100, 100)',
                data: completedRequestsData["IR"]
            }, {
                label: 'CMEL',
                backgroundColor: 'rgb(100, 100, 200)',
                data: completedRequestsData["CMEL"]
            }, {
                label: 'ATPL',
                backgroundColor: 'rgb(100, 200, 100)',
                data: completedRequestsData["ATPL"]
            }]

        };

        var mix = document.getElementById("completedTrainingRequests").getContext('2d');
        var completedTrainingRequests = new Chart(mix, {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Note: One request may have multiple ratings shown indvidually in this graph'
                        }
                    },
                    y: {
                        stacked: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

    });
</script>

<script>

    document.addEventListener("DOMContentLoaded", function () {

        // Closed requests chart
        var closedRequestsData = {!! json_encode($closedRequests) !!}

        var barChartData = {
            labels: [moment().subtract(6, "month").startOf("month").format('MMMM'),
                    moment().subtract(5, "month").startOf("month").format('MMMM'),
                    moment().subtract(4, "month").startOf("month").format('MMMM'),
                    moment().subtract(3, "month").startOf("month").format('MMMM'),
                    moment().subtract(2, "month").startOf("month").format('MMMM'),
                    moment().subtract(1, "month").startOf("month").format('MMMM'),
                    moment().startOf("month").format('MMMM')],
                    datasets: [{
                label: 'PPL',
                backgroundColor: 'rgb(250, 150, 150)',
                data: closedRequestsData["PPL"]
            }, {
                label: 'IR',
                backgroundColor: 'rgb(200, 100, 100)',
                data: closedRequestsData["IR"]
            }, {
                label: 'CMEL',
                backgroundColor: 'rgb(100, 100, 200)',
                data: closedRequestsData["CMEL"]
            }, {
                label: 'ATPL',
                backgroundColor: 'rgb(100, 200, 100)',
                data: closedRequestsData["ATPL"]
            }]

        };

        var mix = document.getElementById("closedTrainingRequests").getContext('2d');
        var closedTrainingRequests = new Chart(mix, {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Note: One request may have multiple ratings shown indvidually in this graph'
                        }
                    },
                    y: {
                        stacked: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    });
</script>


@endsection