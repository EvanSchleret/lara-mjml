@extends('layouts.mjml')

@section('title')
LaraMjmlGeneratedSubject
@endsection

@section('content')
  <mj-text font-size="20px" font-weight="bold">LaraMjmlGeneratedSubject</mj-text>
  <mj-text>Hello{{ isset($name) ? ' '.$name : '' }},</mj-text>
  <mj-text>This email was generated with LaraMJML.</mj-text>
@endsection
