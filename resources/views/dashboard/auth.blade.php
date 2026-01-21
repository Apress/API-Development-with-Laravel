<!--resources/views/dashboard/auth.blade.php-->
@extends('dashboard.base')
@section('title', 'Sign Up Page')
@section('sign', $sign)
@section('name', $name)
@section('url', $url)
@section('content')
<section>
   <div class="row">
      <div class="col-3"></div>
      <div class="col-6">
         <h4 align ="center" >Get Started</h4>
         <br/>
         <form action = "{{route('do.auth')}}" method = "post">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="text" placeholder="Enter your email to Start?" name = "email" required>
            @error('email')
              {{$message}}
            @enderror
            <br/>
            <input id="submit" type="submit" value="Submit">
         </form>
      </div>
      <div class="col-3"></div>
   </div>
</section>
@endsection
