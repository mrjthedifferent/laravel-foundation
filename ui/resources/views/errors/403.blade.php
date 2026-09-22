@extends('errors::minimal')

@section('title', __('foundation::foundation.errors.forbidden'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: __('foundation::foundation.errors.forbidden')))
