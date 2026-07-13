@extends('layouts.patient')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/profile-completion.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('front/js/profile-completion.js') }}"></script>
@endpush

@section('content')
@php
    $profileCompletionValue = (int) (
        data_get($patient ?? [], 'profile_completion')
        ?? $profileCompletion
        ?? data_get($profileForm ?? [], 'profile_completion')
        ?? data_get($profileForm ?? [], 'completion_percentage')
        ?? 0
    );

    $doctorIsSelected = (bool) (
        data_get($doctor ?? [], 'is_selected')
        ?? data_get($patient ?? [], 'has_selected_doctor')
        ?? false
    );

    $doctorRequestStatus =
        data_get($doctor ?? [], 'request_status')
        ?? data_get($patient ?? [], 'doctor_request_status')
        ?? null;

    $hasDoctorRequest = $doctorIsSelected
        || in_array($doctorRequestStatus, ['pending', 'approved', 'rejected', 'declined'], true);

    if (request()->boolean('edit')) {
        $viewMode = 'complete_profile';
    } elseif ($profileCompletionValue < 100) {
        $viewMode = 'complete_profile';
    } else {
        $viewMode = 'profile_summary';
    }
@endphp

@if ($viewMode === 'complete_profile')
    @include('patient.partials.profile-completion-form')
@else
    @include('patient.partials.profile-summary')
@endif
@endsection