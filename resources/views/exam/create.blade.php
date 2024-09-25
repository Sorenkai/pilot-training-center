@extends('layouts.app')

@section('title', 'Create Theory Result')
@section ('content')

<div class="row" id="giveExamResult">
    <div class="col-xl-5 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Create Theory Result
                </h6>
            </div>

            <div class="card-body" id="examselector">
                <form action="{{ route('exam.store') }}" method="POST">
                    @csrf

                    {{-- User --}}
                    <div class="mb-3">
                        <label for="user" class="form-label">Student</label>
                        <input
                            id="user"
                            class="form-control @error('user') is-invalid @enderror"
                            type="text"
                            name="user"
                            list="userList"
                            v-model="user"
                            v-bind:class="{'is-invalid': (validationError && user == null)}"
                            value="{{ isset($prefillUserId) ? $prefillUserId : old('user') }}"
                            required>

                        <datalist id="userList">
                            @foreach ($users as $user)
                                @browser('isFirefox')
                                    <option>{{ $user->id }}</option>
                                @else
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endbrowser
                            @endforeach
                        </datalist>
                        @error('user')
                            <span class="text-danger">{{ $errors->first('user') }}</span>
                        @enderror
                    </div>

                    {{-- Rating --}}
                    <div class="mb-3">
                        <label class="form-label my-1 me-2" for="ratingSelect">Rating</label>
                        <select id="ratingSelect" name="rating" class="form-select @error('ratings') is-invalid @enderror" size="5" required>
                            <option v-for="rating in ratings" :value="rating.id"> @{{ rating.name }}</option>
                        </select>
                        @error('ratings')
                            <span class="text-danger">{{ $errors->first('ratings') }}</span>
                        @enderror
                    </div>

                    {{-- URL --}}
                    <div class="mb-3">
                        <label for="url" class="form-label my-1 me-3">Link to moodle result</label>
                        <input
                            id="url"
                            class="form-control @error('url') is-invalid @enderror"
                            type="url"
                            name="url"
                            >
                        @error('url')
                            <span class="text-danger">{{ $errors->first('url') }}</span>
                        @enderror
                    </div>
                    {{-- Score --}}
                    <div class="mb-3">
                        <label for="score" class="form-lavel my-1 me-2">Achieved Score</label>
                        <input
                            id="score"
                            class="form-control @error('score') is-invalid @enderror"
                            type="number"
                            name="score"
                            min="0"
                            max="100"
                            step="0.01"
                            value="{{ old('score') }}">

                        @error('score')
                            <span class="text-danger">{{ $errors->first('score') }}</span>
                        @enderror
                    </div>

                    <button class="btn btn-success mt-4">Save Result</button>
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

        var payload = {!! json_encode($ratings, true) !!}
        const app = createApp({
            data(){
                return {
                    ratings: payload,
                }
            },
            methods: {
                showTrainingLevels: function(event) {
                    const selectedTrainingArea = event.srcElement.options[event.srcElement.selectedIndex];
                    this.ratings = payload;
                },
            },
        })
        app.mount('#examselector');

    });
</script>

@endsection
