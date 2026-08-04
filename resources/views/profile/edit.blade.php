@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h3" style="color: var(--garuda-gold); font-weight: 700; text-transform: uppercase;">
                {{ __('Profile Settings') }}
            </h2>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card p-4 h-100 shadow-sm" style="border-top: 3px solid var(--garuda-gold);">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card p-4 h-100 shadow-sm" style="border-top: 3px solid var(--garuda-maroon);">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card p-4 shadow-sm" style="border-top: 3px solid #dc3545;">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection
