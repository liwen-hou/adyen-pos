<?php

require_once __DIR__ . '/Config.php';

class Client
{

  public function doPostRequest()
  {
    try{
      // Authentication
      $authentication = Config::getAuthentication();

      // Generate url
      $url = Config::getPaymentUrl();
      $date = new DateTime();
      // Generate data

      if(isset($_POST['recurringDetailReference'])) {
        $request = array(
          /** All order specific settings can be found in payment/Order.php */

          "amount" => array(
            "currency" => "EUR",
            "value" => 1000
          ),
          "reference" => $date->getTimestamp(),
          "paymentMethod" => array(
            "type" => "scheme",
            "recurringDetailReference" => $_POST["recurringDetailReference"],
            "encryptedSecurityCode" => $_POST["encryptedSecurityCode"]
          ),
          "shopperReference" => $_POST["shopperReference"],
          "returnUrl" => "https://54.169.153.135/lib/3dAuth.php",
          "merchantAccount" => $authentication['merchantAccount'],
          "additionalData" => array(
            "executeThreeD" => "true"
          )

        );
      } else {

        if (isset($_POST['saveCard']) && $_POST['saveCard'] == 'true') {

          $request = array(
            /** All order specific settings can be found in payment/Order.php */
            "amount" => array(
              "currency" => "EUR",
              "value" => 1000
            ),
            "reference" => $date->getTimestamp(),
            "paymentMethod" => array(
              "type" => "scheme",
              "encryptedCardNumber" => $_POST["encryptedCardNumber"],
              "encryptedExpiryMonth" => $_POST["encryptedExpiryMonth"],
              "encryptedExpiryYear" => $_POST["encryptedExpiryYear"],
              "encryptedSecurityCode" => $_POST["encryptedSecurityCode"],
              "storeDetails" => "true"
            ),
            "shopperReference" => $_POST["shopperReference"],
            "returnUrl" => "https://54.169.153.135/lib/3dAuth.php",
            "merchantAccount" => $authentication['merchantAccount'],
            "additionalData" => array(
              "executeThreeD" => "true"
            )
          );

        } else {
          $request = array(
            /** All order specific settings can be found in payment/Order.php */
            "amount" => array(
              "currency" => "EUR",
              "value" => 1000
            ),
            "reference" => $date->getTimestamp(),
            "paymentMethod" => array(
              "type" => "scheme",
              "encryptedCardNumber" => $_POST["encryptedCardNumber"],
              "encryptedExpiryMonth" => $_POST["encryptedExpiryMonth"],
              "encryptedExpiryYear" => $_POST["encryptedExpiryYear"],
              "encryptedSecurityCode" => $_POST["encryptedSecurityCode"]
            ),
            "returnUrl" => "https://54.169.153.135/lib/3dAuth.php",
            "merchantAccount" => $authentication['merchantAccount'],
            "additionalData" => array(
              "executeThreeD" => "true"
            )
          );
        }


      }

      $data = json_encode($request);
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
      // Error Check
      if ($result === false){
        throw new Exception(curl_error($curlAPICall), curl_errno($curlAPICall));
      }

      // Closing
      curl_close($curlAPICall);
    } catch (Exception $e) {
      trigger_error(sprintf(
        'API call failed with error #%d, %s', $e->getCode(), $e->getMessage()
        ), E_USER_ERROR);
    }

    // When this file gets called by javascript or another language, it will respond with a json object
    return $result;
  }


}

if(isset($_POST['submit']))
{
  $result = Client::doPostRequest();
  $result = json_decode($result, true);
  $conn = pg_connect("host=localhost dbname=adyen user=adyen password=password");
  if ($conn){
    $query = "insert into transactions (paymentData, status, md) VALUES ('";
    $query = $query . $result['paymentData'] . "','" ;
    $query = $query . $result['resultCode'] . "','" ;
    $query = $query . $result['redirect']['data']['MD'] . "');";
    pg_query($conn, $query);
  }
  pg_close($conn);
  if($result['resultCode'] == 'RedirectShopper'){
    $IssuerUrl = $result['redirect']['url'];
    $PaReq = $result['redirect']['data']['PaReq'];
    $MD = $result['redirect']['data']['MD'];
    $TermUrl = $result['redirect']['data']['TermUrl'];
    ?>
    <body onload="document.getElementById('3dform').submit();">
      <form method="POST" action="<?php echo $IssuerUrl; ?>" id="3dform">
        <input type="hidden" name="PaReq" value="<?php echo $PaReq; ?>" />
        <input type="hidden" name="MD" value="<?php echo $MD; ?>" />
        <input type="hidden" name="TermUrl" value="<?php echo $TermUrl; ?>" />
        <noscript>
          <br>
          <br>
          <div style="text-align: center">
            <h1>Processing your 3-D Secure Transaction</h1>
            <p>Please click continue to continue the processing of your 3-D Secure transaction.</p>
            <input type="submit" class="button" value="continue"/>
          </div>
        </noscript>
      </form>
    </body>
    <?php
  } else {
    if ($result['resultCode'] == 'Authorised') {
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
  }

}

?>
