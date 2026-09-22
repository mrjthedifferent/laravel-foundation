@extends('errors::minimal')

@section('title', __('foundation::foundation.errors.too_many_requests'))
@section('code', '429')
@section('message', __('foundation::foundation.errors.too_many_requests'))
