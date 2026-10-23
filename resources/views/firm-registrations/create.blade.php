@extends('layouts.app')
@section('title', 'New Firm Registration')
@section('page-title', 'Firm Registration')

@section('content')
<form method="POST" action="{{ route('firm-registrations.store') }}">
    @csrf
    @include('firm-registrations._form')
    <div class="d-flex gap-2 mb-5">
        <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save &amp; prepare documents</button>
        <a href="{{ route('firm-registrations.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
