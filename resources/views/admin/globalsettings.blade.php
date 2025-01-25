@extends('layouts.app')

@section('title', 'Global System Settings')

@section('content')

<div class="row">

    <div class="col-xl-6 col-md-12 mb-12">

        @if(Session::has('success') OR isset($success))
            <div class="alert alert-success" role="alert">
                {!! Session::has('success') ? Session::pull("success") : $error !!}
            </div>
        @endif
        <form action="{{ route('admin.settings.store') }}" method="POST">
            @csrf

            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">System Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-6 col-md-6 mb-12">

                            <div class="mb-4">
                                <label class="form-label" for="spoUrl">Pilot Training Center Version</label>
                                <input type="text" class="form-control" required value="{{ config('app.version') }}" disabled>
                            </div>

                            @if(!Setting::get('_updateAvailable'))
                                <div class="alert alert-success" role="alert">
                                    You're running newest version
                                </div>
                            @else
                            <div class="alert alert-warning" role="alert">
                                You're running an old version. <a href="https://github.com/Sorenkai/pilot-training-center/releases" target="_blank">Update {{ Setting::get('_updateAvailable') }} available.</a>
                            </div>
                            @endif

                        </div>

                        <div class="col-xl-6 col-md-6 mb-12">

                            <div class="mb-4">
                                <label class="form-label" for="spoUrl">Last Cronjob Run</label>
                                <input type="text" class="form-control" required value="{{ \Carbon\Carbon::parse(Setting::get('_lastCronRun', '2000-01-01'))->diffForHumans() }}" disabled>
                            </div>

                            @if(\Carbon\Carbon::parse(Setting::get('_lastCronRun', '2000-01-01')) > \Carbon\Carbon::now()->subMinutes(5))
                                <div class="alert alert-success" role="alert">
                                    Cronjob is running as expected
                                </div>
                            @else
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i>&nbsp;&nbsp;Cronjob is not running! Are the cron jobs set up?
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Training</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 col-md-12 mb-12">
                            <!--
                            <div class="form-check">
                                <input class="form-check-input @error('trainingEnabled') is-invalid @enderror" type="checkbox" id="check0" name="trainingEnabled" {{ Setting::get('trainingEnabled') ? "checked" : "" }}>
                                <label class="form-check-label" for="check0">
                                    Accept new training requests
                                </label>
                            </div>

                            <hr>
                            -->
                            <div class="mb-4">
                                <label for="ptmEmail" class="form-label">Pilot Training Manager Email</label>
                                <input type="email" class="form-control" id="ptmEmail" name="ptmEmail" required value="{{Setting::get("ptmEmail")}}">
                                <small class="form-text">Email of the Pilot Training Manager.</small>
                            </div>

                            <div class="mb-4">
                                <label for="ptmCID" class="form-label">Pilot Training Manager CID</label>
                                <input type="number" class="form-control" id="ptmCID" name="ptmCID" required value="{{Setting::get("ptmCID")}}">
                                <small class="form-text">CID of the Pilot Training Manager.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="spoUrl">Student Policy URL</label>
                                <input type="url" class="form-control @error('trainingSOP') is-invalid @enderror" id="spoUrl" name="trainingSOP" required value="{{ Setting::get("trainingSOP") }}">
                                <small class="form-text">Link to PDF or webpage to make student accept when applying for training</small>
                            </div>
                            @error('trainingSOP')
                                <span class="text-danger">{{ $errors->first('trainingSOP') }}</span>
                            @enderror

                            <div class="mb-4">
                                <label class="form-label" for="exmUrl">Exam Template URL</label>
                                <input type="url" class="form-control @error('trainingExamTemplate') is-invalid @enderror" id="exmUrl" name="trainingExamTemplate" value="{{ (Setting::get("trainingExamTemplate") != false) ? Setting::get("trainingExamTemplate") : '' }}">
                                <small class="form-text">Link to examination template for examiners. Leave blank to disable.</small>
                            </div>
                            @error('trainingExamTemplate')
                                <span class="text-danger">{{ $errors->first('trainingExamTemplate') }}</span>
                            @enderror

                            <div class="mb-4">
                                <label class="form-label" for="trainingSubDivisions">Subdivisions accepted for training</label>
                                <input type="text" class="form-control @error('trainingSubDivisions') is-invalid @enderror" id="trainingSubDivisions" name="trainingSubDivisions" value="{{ Setting::get("trainingSubDivisions") }}">
                                <small class="form-text">List subdivisions separated by comma, e.g. SCA, ITA</small>
                            </div>
                            @error('trainingSubDivisions')
                                <span class="text-danger">{{ $errors->first('trainingSubDivisions') }}</span>
                            @enderror

                            <div class="mb-4">
                                <label for="ptdCallsign" class="form-label">Pilot Training Department Callsign</label>
                                <input type="text" class="form-control" id="ptdCallsign" maxlength="3" name="ptdCallsign" required value="{{Setting::get("ptdCallsign")}}">
                                <smal class="form-text">The prefix to be used for assigning callsigns.</smal>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Links</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 col-md-12 mb-12">

                            <div class="mb-4">
                                <label class="form-label" for="linkDomain">Division domain</label>
                                <input type="text" class="form-control @error('linkDomain') is-invalid @enderror" id="linkDomain" name="linkDomain" required value="{{ Setting::get("linkDomain") }}">
                                <small class="form-text">Enter domain without http or any slashes</small>
                            </div>
                            @error('linkDomain')
                                <span class="text-danger">{{ $errors->first('linkDomain') }}</span>
                            @enderror


                            <div class="mb-4">
                                <label for="linkHome">Division homepage</label>
                                <input type="url" class="form-control @error('linkHome') is-invalid @enderror" id="linkHome" name="linkHome" required value="{{ Setting::get("linkHome") }}">
                                <small class="form-text">Enter full homepage url</small>
                            </div>
                            @error('linkHome')
                                <span class="text-danger">{{ $errors->first('linkHome') }}</span>
                            @enderror


                            <div class="mb-4">
                                <label class="form-label" for="linkJoin">Join info</label>
                                <input type="url" class="form-control @error('linkJoin') is-invalid @enderror" id="linkJoin" name="linkJoin" required value="{{ Setting::get("linkJoin") }}">
                                <small class="form-text">Enter link to a page explaining on how to join your division. Shown in FAQ</small>
                            </div>
                            @error('linkJoin')
                                <span class="text-danger">{{ $errors->first('linkJoin') }}</span>
                            @enderror


                            <div class="mb-4">
                                <label class="form-label" for="linkContact">Contact list</label>
                                <input type="url" class="form-control @error('linkContact') is-invalid @enderror" id="linkContact" name="linkContact" required value="{{ Setting::get("linkContact") }}">
                                <small class="form-text">Enter link to staff or contact list. Shown in FAQ and inactivity warning</small>
                            </div>
                            @error('linkContact')
                                <span class="text-danger">{{ $errors->first('linkContact') }}</span>
                            @enderror


                            <div class="mb-4">
                                <label class="form-label" for="linkVisiting">Visiting Controller Info</label>
                                <input type="url" class="form-control @error('linkVisiting') is-invalid @enderror" id="linkVisiting" name="linkVisiting" required value="{{ Setting::get("linkVisiting") }}">
                                <small class="form-text">Enter link to webpage informing about visiting controlling. Shown in FAQ</small>
                            </div>
                            @error('linkVisiting')
                                <span class="text-danger">{{ $errors->first('linkVisiting') }}</span>
                            @enderror


                            <div class="mb-4">
                                <label class="form-label" for="linkDiscord">Discord</label>
                                <input type="url" class="form-control @error('linkDiscord') is-invalid @enderror" id="linkDiscord" name="linkDiscord" required value="{{ Setting::get("linkDiscord") }}">
                                <small class="form-text">Enter Discord invite link. Shown in e-mails to contact mentor on assignment</small>
                            </div>
                            @error('linkDiscord')
                                <span class="text-danger">{{ $errors->first('linkDiscord') }}</span>
                            @enderror


                            <div class="mb-4">
                                <label class="form-label" for="linkMoodle">Moodle</label>
                                <input type="url" class="form-control @error('linkMoodle') is-invalid @enderror" id="linkMoodle" name="linkMoodle" value="{{ (Setting::get("linkMoodle") != false) ? Setting::get("linkMoodle") : '' }}">
                                <small class="form-text">Enter full link to Moodle or leave blank to disable</small>
                            </div>
                            @error('linkMoodle')
                                <span class="text-danger">{{ $errors->first('linkMoodle') }}</span>
                            @enderror

                            <div class="mb-4">
                                <label class="form-label" for="linkWiki">Wiki</label>
                                <input type="url" class="form-control @error('linkWiki') is-invalid @enderror" id="linkWiki" name="linkWiki" value="{{ (Setting::get("linkWiki") != false) ? Setting::get("linkWiki") : '' }}">
                                <small class="form-text">Enter full link to Wiki or leave blank to disable</small>
                            </div>
                            @error('linkWiki')
                                <span class="text-danger">{{ $errors->first('linkWiki') }}</span>
                            @enderror

                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Division API</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 col-md-12 mb-12">

                            <div class="form-check mb-4">
                                <input class="form-check-input @error('divisionApiEnabled') is-invalid @enderror" type="checkbox" id="divisionApiEnabled" name="divisionApiEnabled" {{ Setting::get('divisionApiEnabled') ? "checked" : "" }}>
                                <label class="form-check-label" for="divisionApiEnabled">
                                    Enable division API calls
                                </label>
                                <small class="form-text d-block">Automatic calls based on the environmental configuration.</small>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Feedback</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 col-md-12 mb-12">

                            <div class="form-check mb-4">
                                <input class="form-check-input @error('feedbackEnabled') is-invalid @enderror" type="checkbox" id="checkFeedback" name="feedbackEnabled" {{ Setting::get('feedbackEnabled') ? "checked" : "" }}>
                                <label class="form-check-label" for="checkFeedback">
                                    Enable feedback functionality
                                </label>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="feedbackForwardEmail">Forward feedback to e-mail</label>
                                <input type="email" class="form-control @error('feedbackForwardEmail') is-invalid @enderror" id="feedbackForwardEmail" name="feedbackForwardEmail" value="{{ (Setting::get("feedbackForwardEmail") != false) ? Setting::get("feedbackForwardEmail") : '' }}">
                                <small class="form-text">Forward feedback to the provided address. Leave blank to disable.</small>
                            </div>
                            @error('feedbackForwardEmail')
                                <span class="text-danger">{{ $errors->first('feedbackForwardEmail') }}</span>
                            @enderror

                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Telemetry</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 col-md-12 mb-12">

                            <div class="form-check">
                                <input class="form-check-input @error('telemetryEnabled') is-invalid @enderror" type="checkbox" id="checkTele" name="telemetryEnabled" {{ Setting::get('telemetryEnabled') ? "checked" : "" }}>
                                <label class="form-check-label" for="checkTele">
                                    Enable telemetry
                                </label>
                                <small class="form-text d-block">This is used to prioritise development based on stats and who is using Pilot Training Center. Telemetry only sends the url, version and division name.</small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div>
                <button class="btn btn-success mt-3 mb-4" type="submit">Save</button>
            </div>

        </form>
    </div>

</div>
@endsection
