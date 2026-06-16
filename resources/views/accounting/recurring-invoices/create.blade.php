@extends('layouts.app')
@section('title', 'New Recurring Invoice')
@section('page-title', 'New Recurring Invoice')

@section('content')
@include('accounting.recurring-invoices._form', ['action' => route('accounting.recurring-invoices.store'), 'method' => 'POST'])
@endsection
