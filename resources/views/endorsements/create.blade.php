@extends('layouts.app')

@section('title', 'Create Endorsement')
@section('content')

<div class="row" id="giveEndorsements">
    <div class="col-xl-5 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Create Endorsement
                </h6> 
            </div>
            <div class="card-body" id="training-selector">
                <form id="endorsementForm" action="{!! action('EndorsementController@store') !!}" method="POST">
                    @csrf

                    {{-- User --}} 
                    <div class="mb-3">
                        <label class="form-label" for="user">Instructor</label>
                        <input 
                            id="user"
                            class="form-control"
                            type="text"
                            name="user"
                            list="userList"
                            v-model="user"
                            v-bind:class="{'is-invalid': (validationError && user == null)}"
                            value="{{ $prefillUserId }}">

                        <datalist id="userList">
                            @foreach($users as $user)
                                @browser('isFirefox')
                                    <option>{{ $user->id }}</option>
                                @else
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endbrowser
                            @endforeach
                        </datalist>
                    </div>

                    <label class="form-label my-1 me-2" for="ratingSelect">Training level</label>
                    <select id="ratingSelect" name="rating[]" class="form-select @error('ratings') is-invalid @enderror" size="5">
                        <option v-for="rating in ratings" :value="rating.id"> @{{ rating.name }}</option>
                    </select>


                    <!--


                    {{-- Examiner/Visiting Areas --}}
                    <div class="mb-3" style="display: none" v-show="endorsementType == 'EXAMINER' || endorsementType == 'VISITING'">
                        <label class="form-label" for="areas">Areas: <span class="badge bg-secondary">Ctrl/Cmd+Click</span> to select multiple</label>
                        <select multiple class="form-select" name="areas[]" id="areas" v-model="areas" v-bind:class="{'is-invalid': (validationError && !areas.length)}">
                            @foreach($ratings as $rating)
                                <option value="{{ $rating->id }}">{{ $rating->name }}</option>
                            @endforeach
                        </select>
                        <span v-show="validationError && !areas.length" class="text-danger">Select one or more areas</span>
                    </div>

                    {{-- Training Checkbox --}}
                    <div class="form-check" class="mt-5" style="display: none" v-show="endorsementType == 'SOLO'">
                        <input class="form-check-input" type="checkbox" id="soloChecked" v-model="soloChecked">
                        <label class="form-check-label" for="soloChecked">
                            {{ Setting::get('trainingSoloRequirement') }}
                        </label>
                        <p v-show="validationError && soloChecked == false" class="text-danger">Confirm that the requirements are filled</p>
                    </div> -->

                    <button type="submit" id="submit_btn" class="btn btn-success mt-4">Create endorsement</button>
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
        app.mount('#training-selector');

    });
</script>

@endsection
