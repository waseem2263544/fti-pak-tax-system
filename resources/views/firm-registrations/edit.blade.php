@extends('layouts.app')
@section('title', 'Edit ' . $firm->firm_name)
@section('page-title', 'Firm Registration')

@section('content')
<form method="POST" action="{{ route('firm-registrations.update', $firm) }}">
    @csrf
    @method('PUT')
    @include('firm-registrations._form')
    <div class="d-flex gap-2 mb-5">
        <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save changes</button>
        <a href="{{ route('firm-registrations.show', $firm) }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
