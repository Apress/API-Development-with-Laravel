<!DOCTYPE html>
<html>
<head>
    <title>Dexy Pay - Payment Status</title>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta content="ie=edge" http-equiv="x-ua-compatible">


    <!-- STYLE TO IMPORT TO YOUR PROJECT -->
    <link href=" " rel="stylesheet">
    <!-- STYLE TO IMPORT TO YOUR PROJECT -->

</head>
<body>
    <style>
    .centered{
        width:400px;
        margin-left:auto;
        margin-right:auto;

    }
    .gif{
width:1oo%;
    }
    .text-centered{

    }
    </style>

<div  class = "centered">
    @if($status === "successful")
    <img class = "gif" src="{{asset('img/payment/tx_success.gif')}}"  alt = "Payment successful">
    <h3 class = ""  align-"center"> Please wait while you are being redirected .......</h3>
    @elseif($status === "failed")
    <img class = "gif" src="{{asset('img/payment/tx_failed.gif')}}"  alt = "Payment failed"><br>
    <h2  onclick="history.back()"  align="center" >Retry</h2>
    @endif
</div>
<script>
    @if($status === "successful")
    setTimeout(
        function(){
            window.location.replace(
                "{{$redirect_url}}"
            )
        },
        10000
    )
    @endif

</script>

</body>
</html>
