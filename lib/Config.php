<?php

class Config
{
    const ENDPOINT_TEST = "https://checkout-test.adyen.com/services/PaymentSetupAndVerification";
    const VERSION = "/v40";
    const PAYMENTS = "/payments";
    const PAYMENTMETHODS = "/paymentMethods";
    const PAYMENTDETAILS = "/payments/details";
    const POS_TEST = "https://terminal-api-test.adyen.com/sync";

    /** Function to define the protocol and base URL */
    public static function url()
    {
        if (!empty (getenv('PROTOCOL'))) {
            $protocol = getenv('PROTOCOL');
        } else {
            $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https' : 'http';
        }

        return sprintf(
            "%s://%s", $protocol, $_SERVER['HTTP_HOST']
        );
    }

    public static function getOrigin()
    {
        return self::url();
    }

    public static function getShopperIP()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    public static function getReturnUrl()
    {
        return self::url();
    }

    public static function getPaymentDetailsUrl()
    {
      return self::ENDPOINT_TEST . self::VERSION . self::PAYMENTDETAILS;
    }

    public static function getPaymentMethodsUrl()
    {
        return self::ENDPOINT_TEST . self::VERSION . self::PAYMENTMETHODS;
    }

    public static function getPaymentUrl()
    {
        return self::ENDPOINT_TEST . self::VERSION . self::PAYMENTS;
    }

    public static function getTerminalUrl()
    {
      return self::POS_TEST;
    }

    public static function getAuthentication()
    {
        $authentication = array();
        if (!empty (getenv('MERCHANT_ACCOUNT')) && !empty(getenv('CHECKOUT_API_KEY'))) {
            $authentication['merchantAccount'] = getenv('MERCHANT_ACCOUNT');
            $authentication['checkoutAPIkey'] = getenv('CHECKOUT_API_KEY');
        } else {
            if (file_exists(__DIR__ . '/../config/authentication.ini')) {
                $authentication = parse_ini_file(__DIR__ . '/../config/authentication.ini', true);
            }
        }
        if (empty($authentication)) {
            echo "Authentication not set. Please check README file.";
        }
        return $authentication;
    }
}
