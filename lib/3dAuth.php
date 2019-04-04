<?php

require_once __DIR__ . '/Config.php';

// Authentication
$authentication = Config::getAuthentication();


$conn = pg_connect("host=localhost dbname=adyen user=adyen password=password");
if ($conn){
  $query = "select * from transactions where md='" . $_POST['MD'] . "';";
  $txn = pg_query($conn, $query);
  $row = pg_fetch_row($txn);
}


// Generate url
$url = Config::getPaymentDetailsUrl();
$request = array(
  "paymentData" => $row[0],
  "details" => array(
    "MD" => $_POST['MD'],
  	"PaRes" => $_POST['PaRes']
  )
);


$data = json_encode($request, JSON_UNESCAPED_SLASHES);
//  Initiate curl
$curlAPICall = curl_init();

// Set to POST
curl_setopt($curlAPICall, CURLOPT_CUSTOMREQUEST, "POST");

// Will return the response, if false it print the response
curl_setopt($curlAPICall, CURLOPT_RETURNTRANSFER, true);

// Add JSON message
curl_setopt($curlAPICall, CURLOPT_POSTFIELDS, $data);

// Set the url
curl_setopt($curlAPICall, CURLOPT_URL, $url);

// Api key
curl_setopt($curlAPICall, CURLOPT_HTTPHEADER,
  array(
    "X-Api-Key: " . $authentication['checkoutAPIkey'],
    "Content-Type: application/json",
    "Content-Length: " . strlen($data)
  )
);
// Execute
$result = curl_exec($curlAPICall);
$result = json_decode($result, true);
if ($result['resultCode'] == "Authorised"){
  $query = "update transactions set status='Authorised' where paymentdata='" . $row[0] . "';" ;
  pg_query($conn, $query);
  echo '<!DOCTYPE html>
  <html class="html">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="robots" content="noindex"/>
    <title>Adyen Checkout</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="../assets/css/main.css">

  </head>

  <body>

    <div class="container" id="paymentResult">
      <div class="py-5 text-center">
        <img class="d-block mx-auto mb-4" src="../assets/img/shopping.png" alt="" width="100" height="100">
        <h2 class="heading">Payment Successful! Your Order is On the Way!</h2>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>

  </body>
  </html>';
} else {
  echo '<!DOCTYPE html>
  <html class="html">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="robots" content="noindex"/>
    <title>Adyen Checkout</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="../assets/css/main.css">

  </head>

  <body>

    <div class="container" id="paymentResult">
      <div class="py-5 text-center">
        <img class="d-block mx-auto mb-4" src="../assets/img/error.png" alt="" width="100" height="100">
        <h2 class="heading">Oops! Something Went Wrong..</h2>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>

  </body>
  </html>';
}
curl_close($curlAPICall);
pg_close($conn);
