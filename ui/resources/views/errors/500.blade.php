@extends('errors::minimal')

@section('title', __('foundation::foundation.errors.server_error'))
@section('code', '500')
@section('message', __('foundation::foundation.errors.server_error'))
