<!--resources/views/dashboard/sent_email_notifier.blade.php-->
@extends('dashboard.base')
@section('title', 'Email Verification Required')
@section('sign', $sign)
@section('name', $name)
@section('url', $url)
@section('content')
      <section>
	      <h5 align="center">You're Almost There!</h5>
		  <p>A login link has been sent to <b>{{$masked_email}}</b>. To resend the link, click <u><a href="{{route('display.auth')}}">here</a></u>.</p>
      </section>
@endsection
