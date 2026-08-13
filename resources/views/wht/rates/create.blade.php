@extends('layouts.app')
@section('title', 'Add Tax Rate')
@section('page-title', 'Add Tax Rate')

@section('content')
<div class="card" style="max-width: 860px;">
    <div class="card-body">
        <form method="POST" action="{{ route('wht.rates.store') }}">
            @csrf
            @include('wht.rates._fields', ['rate' => null])
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Add Rate</button>
                <a href="{{ route('wht.rates.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
