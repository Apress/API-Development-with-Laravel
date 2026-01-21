<!--resources/views/dashboard/base.blade.php-->
<!DOCTYPE html>
<html lang="en">
   <head>
      <link rel="icon" href="https://via.placeholder.com/70x70">
      <link rel="stylesheet" href="{{ asset('css/dashboard/classless.css') }}">
      <link rel="stylesheet" href="{{ asset('css/dashboard/tabbox.css') }}">
      <meta charset="utf-8">
      <meta name="description" content="My description">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>@yield('title') - Dexy Payment Proccessing API</title>
   </head>
   <body>
      <header>
         <nav>
            <ul>
               <li><img alt="Logo" src="{{ asset('img/dexypay4.png') }}" height="40"></li>
               <li class="float-right sticky"><a href="@yield('url')">@yield('sign')</a></li>
               <li><a href="#">Home </a></li>
               <li>
                  <a href="#">Developer ▾</a>
                  <ul>
                     <li><a href="#">Documentation</a></li>
                     <li><a href="#">Integrate Our API</a></li>
                  </ul>
               </li>
               <li><a href="#">About Us </a></li>
               <li><a href="#">Contact Us </a></li>
            </ul>
         </nav>
      </header>
      <main>
      <div  class="float-right "> Welcome <em>@yield('name')</em></div>
         <br/>
         @yield('content')
      </main>
      <footer>
         <hr>
         <div id="copyright"align="center"></div>
      </footer>
      <script>
         const d = new Date();
         let year = d.getFullYear();
         let elem = document.getElementById("copyright");
         elem.innerHTML = "<b>Dexy Pay</b> &copy; All Rights Reserved 2024 - "+year;
      </script>
   </body>
</html>
