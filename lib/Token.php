<?php

require_once __DIR__ . '/Config.php';

function getPaymentMethods()
{
  try{
    // Authentication
    $authentication = Config::getAuthentication();

    // Generate url
    $url = Config::getPaymentMethodsUrl();

    // Generate data
    $request = array(

      "merchantAccount" => $authentication['merchantAccount'],
      "countryCode" => "DE",
      "shopperReference" => $_POST['shopperReference'],
      "amount" => array(
        "currency" => "EUR",
        "value" => 1000
      )

    );
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
    $paymentMethods = curl_exec($curlAPICall);

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

  return $paymentMethods;
}


if (isset($_POST['callFunc1'])) {
  $response = getPaymentMethods();
  $results = json_decode($response, true);

  echo '<div class="accordion">
    <h4 class="d-flex justify-content-between align-items-center mb-3">
      <span>Choose How You Like to Pay</span>
    </h4>';

  $i = 1;
  foreach ($results['paymentMethods'] as $methods) {
    echo '<div class="card"><div class="card-header" id="';
    echo 'method' . $i;
    echo '"><h2 class="mb-0"><img id="methodBrand" src="assets/img/'. $methods['type'] . '@2x.png" height="22" width="33"><button class="btn btn-link" type="button" data-toggle="collapse" data-target="#';
    echo 'collapse' . $i;
    echo '" aria-expanded="true" aria-controls="';
    echo 'collapse' . $i;
    echo '">';
    echo $methods['name'];
    echo '</button></h2></div><div id="';
    echo 'collapse' . $i;
    echo '" class="collapse" aria-labelledby="';
    echo 'method' . $i;
    echo '" data-parent="#paymentWindow"><div class="card-body">';
    if ($methods['name'] == 'Credit Card') {
      if (isset($results['oneClickPaymentMethods'])) {
        echo '
        <div class="checkout-container" id="cardWindow">
        <div class="form-div" id="onClickPay" display="block">
        <form class="payment-div" method="post" action="lib/Client.php">
        <input type="hidden" name="txvariant" value="card"/>
        <div class="cards-div">

        <div class="js-chckt-pm__pm-holder">
        <input type="hidden" name="txvariant" value="card" />
        <input type="hidden" id ="shopperReference" name="shopperReference" value="" />
        <div class="input-group mb-3">
          <div class="input-group-prepend">
            <label class="input-group-text" for="inputGroupSelect01">Pay with:</label>
          </div>
          <select class="custom-select" name="recurringDetailReference" id="inputGroupSelect01">
            <option selected>Choose...</option>';
        foreach ($results['oneClickPaymentMethods'] as $oneClickMethods){
          echo '<option value="';
          echo $oneClickMethods['recurringDetailReference'];
          echo '">';
          echo $oneClickMethods['name'];
          echo ' ending with ';
          echo $oneClickMethods['storedDetails']['card']['number'];
          echo '</option>';

        }
        echo '</select></div>
        <label>
        Security Code<span class="input-field" data-cse="encryptedSecurityCode" />
        </label>
        <button class="btn btn-primary btn-lg btn-block" name="submit" type="submit">Pay Now</button><hr class="mb-4">';
        echo '</div></div></form>
        <button class="btn btn-primary btn-lg btn-block" type="button" onclick="newCard()">Pay with a new card</button><hr class="mb-4"></div></div>';
      } else {
        echo '<div class="checkout-container">
        <div class="form-div">
        Card Number <img id="cardBrand" src="assets/img/card@2x.png" height="18" width="27">
        <form class="payment-div" method="post" action="lib/Client.php">
        <input type="hidden" name="txvariant" value="card"/>
        <div class="cards-div">

        <div class="js-chckt-pm__pm-holder">
        <input type="hidden" name="txvariant" value="card"/>
        <input type="hidden" id ="shopperReference" name="shopperReference" value="" />
        <label>
        <span class="input-field" data-cse="encryptedCardNumber" />
        </label>
        <label>
        Expiry Month<span class="input-field" data-cse="encryptedExpiryMonth" />
        </label>
        <label>
        Expiry Year<span class="input-field" data-cse="encryptedExpiryYear" />
        </label>
        <label>
        Security Code<span class="input-field" data-cse="encryptedSecurityCode" />
        </label>
        <div class="form-group form-check">
        <input type="checkbox" class="form-check-input" id="exampleCheck1" name="saveCard" value="true">
        <label class="form-check-label" for="exampleCheck1">Save this card</label>
        </div>
        </div>
        </div>
        <button id="payBtn" class="btn btn-primary btn-lg btn-block" name="submit" type="submit">Pay Now</button>
        <hr class="mb-4">
        </form>
        </div>
        </div>';
      }
    } else {
      echo $methods['name'];
    }
    echo '</div></div></div>';

    $i = $i + 1;
  }

}
