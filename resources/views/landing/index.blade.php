@extends('layouts.landing')
@section('title', 'Arzonet — Enterprise Bulk Email Platform')

@section('content')
@include('landing.partials.hero')
@include('landing.partials.features')
@include('landing.partials.how_it_works')

{{-- PRICING --}}
@include('landing.partials.pricing')

@include('landing.partials.faq')

@include('landing.partials.cta')

@endsection
