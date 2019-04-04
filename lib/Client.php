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
      $url = Config::getTerminalUrl();
      $date = new DateTime();
      // Generate data
      $serviceID = strval(mt_rand(1000000,9999999));
      $request = array(
        "SaleToPOIRequest" => array(
          "MessageHeader" => array(
            "ProtocolVersion" => "3.0",
            "MessageClass" => "Service",
            "MessageCategory" => "Payment",
            "MessageType" => "Request",
            "ServiceID" => $serviceID,
            "SaleID" => "liwenShopID001",
            "POIID" => "e285-401552056"
          ),
          "PaymentRequest" => array(
            "SaleData" => array(
              "SaleTransactionID" => array(
                "TransactionID" => "27908",
                "TimeStamp" => "2019-04-04T01:44:13.573Z"
              )
            ),
            "PaymentTransaction" => array(
              "AmountsReq" => array(
                "Currency" => "EUR",
                "RequestedAmount" => (float)$_POST["paymentAmount"]
              )
            )
          )
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
  echo $result["SaleToPOIResponse"]["PaymentResponse"];
  echo $result["PaymentResponse"];
  echo $result["SaleToPOIResponse"];
  echo $result["SaleToPOIResponse"]["PaymentResponse"]["Response"]["Result"];

}

?>
