@extends('layouts.app')

@section('title', 'Create Exam Result')
@section('content')

<div class="row" id="giveExamResult">
    <div class="col-xl-5 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Create Exam Result
                </h6>
            </div>

            <div class="card-body" id="examselector">
                <form action="{{ route('exam.practical.store') }}" method="POST" enctype="multipart/form-data">
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
                            v-model="selectedUserId"
                            v-bind:class="{'is-invalid': (validationError && selectedUserId == null)}"
                            @input="updateTrainings(selectedUserId)"
                            required>

                        <datalist id="userList">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </datalist>
                        @error('user')
                            <span class="text-danger">{{ $errors->first('user') }}</span>
                        @enderror
                    </div>

                    {{-- Training (only visible when a user is selected and has trainings) --}}
                    <div class="mb-3" v-if="trainings.length > 0">
                        <label class="form-label" for="trainingSelect">Training</label>
                        <select id="trainingSelect" name="training" class="form-select @error('training') is-invalid @enderror" required>
                            <option v-for="training in trainings" :value="training.id">@{{ selectedUser.first_name }}'s training for: @{{ training.pilot_ratings[0].name }}</option>
                        </select>
                        @error('training')
                            <span class="text-danger">{{ $errors->first('training') }}</span>
                        @enderror
                    </div>

                    {{-- Result --}}
                    <div class="mb-3">
                        <label for="result" class="form-label my-1 me-2">Result</label>
                        <select id="result" name="result" class="form-select @error('result') is-invalid @enderror" required>
                            <option value="PASS">PASS</option>
                            <option value="PARTIAL PASS">PARTIAL PASS</option>
                            <option value="FAIL">FAIL</option>
                        </select>
                        @error('result')
                            <span class="text-danger">{{ $errors->first('result') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="attachments">Attachments</label>
                        <div>
                            <input type="file" name="files[]" id="add-file" class="@error('file') is-invalid @enderror" accept=".pdf, .xls, .xlsx, .doc, .docx, .txt, .png, .jpg, .jpeg" multiple>
                        </div>
                        @error('files')
                            <span class="text-danger">{{ $errors->first('files') }}</span>
                        @enderror
                    </div>

                    <hr>

                    <button type="submit" class="btn btn-success mt-4">Save Result</button>
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

        var users = {!! json_encode($users, true) !!};  // Ensure that users includes the pilotTrainings relation
        var payload = {!! json_encode($ratings, true) !!}; // Make sure you also include ratings
        
        const app = createApp({
            data() {
                return {
                    selectedUserId: null,
                    selectedUser: null,  // Holds the selected user ID
                    trainings: [],       // Holds the trainings for the selected user
                    ratings: payload,    // Hold ratings from the server
                };
            },
            methods: {
                updateTrainings(userId) {
                    let user = users.find(u => u.id == userId);
                    
                    if (user && Array.isArray(user.pilot_trainings)) { // Check if pilotTrainings is an array
                        this.trainings = user.pilot_trainings; // Populate trainings
                        this.selectedUser = user;

                    } else {
                        this.trainings = []; // Reset if no trainings found
                        this.selectedUser = null;

                    }
                }
            }
        });

        var submitClicked = false
        document.addEventListener("submit", function(event) {
            if (event.target.tagName === "FORM") {
                submitClicked = true;
            }
        });

        app.mount('#examselector'); // Mount the Vue app
    });
</script>
@endsection
