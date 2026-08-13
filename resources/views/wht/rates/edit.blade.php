@extends('layouts.app')
@section('title', 'Edit Tax Rate')
@section('page-title', 'Edit Tax Rate')

@section('content')
<div class="card" style="max-width: 860px;">
    <div class="card-body">
        <form method="POST" action="{{ route('wht.rates.update', $rate) }}">
            @csrf @method('PUT')
            @include('wht.rates._fields')
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('wht.rates.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
