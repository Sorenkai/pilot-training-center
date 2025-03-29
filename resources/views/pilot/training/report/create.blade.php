@extends('layouts.app')

@section('title', 'New Training Report')
@section('content')

<div class="row">
    <div class="col-xl-5 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    New Training Report for {{ $training->user->first_name }}'s training for {{ $training->pilotRatings[0]->name}}
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('pilot.training.report.store', ['training' => $training->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="lesson">Lesson</label>
                        
                        <!-- Input to display the lesson name -->
                        <input
                            id="lesson_name"
                            class="form-control @error('lesson_id') is-invalid @enderror"
                            type="text"
                            name="lesson_name"
                            list="lessons"
                            value="{{ old('lesson_name') }}"
                            required>
                        
                        <!-- Hidden input to store and submit lesson_id -->
                        <input
                            type="hidden"
                            name="lesson_id"
                            id="lesson_id"
                            value="{{ old('lesson_id') }}">
                    
                        <!-- Datalist for lessons (only lesson names are displayed in the dropdown) -->
                        <datalist id="lessons">
                            @foreach ($lessons as $lesson)
                                <option value="{{ $lesson->name }}" data-id="{{ $lesson->id }}"></option>
                            @endforeach
                        </datalist>
                    
                        @error('lesson_id')
                            <span class="text-danger">{{ $errors->first('lesson_id') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="date">Date</label>
                        <input id="date" class="datepicker form-control @error('report_date') is-invalid @enderror" type="text" name="report_date" value="{{ old('report_date') }}" required>
                        @error('report_date')
                            <span class="text-danger">{{ $errors->first('report_date') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="instructor_hours" class="form-label">Hours flown</label>
                        <input type="time" id="instructor_hours" class="form-control @error('instructor_hours') is-invalid @enderror" name="instructor_hours" value="00:00" required>
                        @error('instructor_hours')
                            <span class="text-danger">{{ $errors->first('instructor_hours') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="contentBox">Session Details</label>
                        <textarea class="form-control @error('content') is-invalid @enderror" name="content" id="contentBox" rows="8" placeholder="Write the session details here.">{{ old('content') }}</textarea>
                        @error('content')
                            <span class="text-danger">{{ $errors->first('content') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="contentimprove">Report</label>
                        <textarea class="form-control @error('contentimprove') is-invalid @enderror" name="contentimprove" id="contentimprove" rows="4" placeholder="Write the training report and comments here.">{{ old('contentimprove') }}</textarea>
                        @error('contentimprove')
                            <span class="text-danger">{{ $errors->first('contentimprove') }}</span>
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

                    <button type="submit" id="training-submit-btn" class="btn btn-success">Save report</button>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection

@section('js')

<!-- Flatpickr -->
@vite(['resources/js/flatpickr.js', 'resources/sass/flatpickr.scss'])
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var defaultDate = "{{ old('report_date') }}"
        document.querySelector('.datepicker').flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d', strtotime('-1 months')) !!}", maxDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate, locale: {firstDayOfWeek: 1 } });  
    });
</script>

<!-- Markdown Editor -->
@vite(['resources/js/easymde.js', 'resources/sass/easymde.scss'])
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var simplemde1 = new EasyMDE({ 
            element: document.getElementById("contentBox"), 
            status: false, 
            toolbar: ["bold", "italic", "heading-3", "|", "quote", "unordered-list", "ordered-list", "|", "link", "preview", "side-by-side", "fullscreen", "|", "guide"],
            insertTexts: {
                link: ["[","](link)"],
            }
        });
        var simplemde2 = new EasyMDE({ 
            element: document.getElementById("contentimprove"), 
            status: false, 
            toolbar: ["bold", "italic", "heading-3", "|", "quote", "unordered-list", "ordered-list", "|", "link", "preview", "side-by-side", "fullscreen", "|", "guide"],
            insertTexts: {
                link: ["[","](link)"],
            }
        });

        var submitClicked = false
        document.addEventListener("submit", function(event) {
            if (event.target.tagName === "FORM") {
                submitClicked = true;
            }
        });

        // Confirm closing window if there are unsaved changes
        window.addEventListener('beforeunload', function (e) {
            if(!submitClicked && (simplemde1.value() != '' || simplemde2.value() != '')){
                e.preventDefault();
                e.returnValue = '';
            }
        });
    })
</script>
<script>
    document.getElementById('lesson_name').addEventListener('input', function() {
        // Get the entered lesson name
        var lessonName = this.value;

        // Find the matching option in the datalist
        var options = document.querySelectorAll('#lessons option');
        var lessonId = '';

        options.forEach(function(option) {
            if (option.value === lessonName) {
                // Set the corresponding lesson_id
                lessonId = option.getAttribute('data-id');
            }
        });

        // Update the hidden input with the lesson_id
        document.getElementById('lesson_id').value = lessonId;

        console.log('Selected lesson_id:', lessonId);
    });
</script>


@endsection
