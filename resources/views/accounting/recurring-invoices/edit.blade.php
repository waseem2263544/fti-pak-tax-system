@extends('layouts.app')
@section('title', 'Edit Recurring Invoice')
@section('page-title', 'Edit Recurring Invoice')

@section('content')
@include('accounting.recurring-invoices._form', ['action' => route('accounting.recurring-invoices.update', $template), 'method' => 'PUT'])
@endsection
