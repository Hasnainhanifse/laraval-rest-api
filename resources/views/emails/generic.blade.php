@extends('layouts.base-email')
@section('title', $emailSubject)

@section('content')
{!! $emailBody !!}
@endsection