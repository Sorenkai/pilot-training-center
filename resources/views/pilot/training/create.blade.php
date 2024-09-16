@extends('layouts.app')

@section('title', 'Add training request')
@section('content')

<div class="row">
    <div class="col-xl-4 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Create
                </h6>
            </div>
            <div class="card-body" id="training-selector">
                <form action="{{ route('pilot.training.store') }}" method="post">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="student">Student</label>
                        <input
                            id="student"
                            class="form-control @error('student') is-invalid @enderror"
                            type="text"
                            name="user_id"
                            list="students"
                            value="{{ isset($prefillUserId) ? $prefillUserId : old('student') }}"
                            required>

                        <datalist id="students">
                            @foreach($students as $student)
                                @browser('isFirefox')
                                    <option>{{ $student->id }}</option>
                                @else
                                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                                @endbrowser
                            @endforeach
                        </datalist>

                        @error('student')
                            <span class="text-danger">{{ $errors->first('student') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input value="true" type="checkbox" class="form-check-input" id="englishOnly" name="englishOnly">
                        <label class="form-check-label" for="englishOnly">English only training</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label my-1 me-2" for="ratingSelect">Training level <span class="badge bg-secondary">Ctrl/Cmd+Click</span> to select multiple</label>
                        <select multiple id="ratingSelect" name="training_level" class="form-select @error('pilotRatings') is-invalid @enderror" size="5">
                            <option v-for="rating in pilotRatings" :value="rating.id"> @{{ rating.name }}</option>
                        </select>

                        @error('pilotRatings')
                            <span class="text-danger">{{ $errors->first('pilotRatings') }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-success">Create training</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
@vite('resources/js/vue.js')
<script>
    document.addEventListener("DOMContentLoaded", function () {

        var payload = {!! json_encode($pilotRatings, true) !!}
        console.log(payload)
        const app = createApp({
            data(){
                return {
                    pilotRatings: payload,
                }
            },
            methods: {
                showTrainingLevels: function(event) {
                    const selectedTrainingArea = event.srcElement.options[event.srcElement.selectedIndex];
                    this.pilotRatings = payload.pilotRatings;
                },
            },
        })
        app.mount('#training-selector');

    });
</script>
@endsection
