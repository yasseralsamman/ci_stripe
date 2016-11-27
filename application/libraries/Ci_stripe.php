<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Stripe Library for Codeigniter
*
* A library to interface with the Stripe API. For more information see https://stripe.com/docs/api/php
*
* @package CodeIgniter
* @author Yasser AlSamman | https://www.linkedin.com/in/yasseralsamman | y.samman@codersme.com
* @copyright Copyright (c) 2016, Coders Web Solutions.
* @link http://codersme.com
* @version Version 1.0
*/

require_once('vendor/autoload.php');

class Ci_stripe {
  protected 	$CI;
  private     $stripe_config;

	/**
  * Class Constructor.
  *
  * The constructor is responsible for loading the library configurations and
  * initilaize the CI instance and other library variables.
  *
  * @return void
  */
  function __construct() {
    $this->CI =& get_instance();
		$this->CI->config->load('ci_stripe');
    $this->stripe_config = $this->CI->config->item('ci_stripe_config');
		if(!$this->checkConfig($this->stripe_config)) {
			show_error("Configuration Error. Please check your Stripe configuration file.", 500);
		}
    \Stripe\Stripe::setApiKey($this->stripe_config['api_key']);
  }

  /**
  * Create a new customer.
  *
  * Returns a customer object as documented at https://stripe.com/docs/api/php#create_customer
  *
  * @param array $params array containing customer information
	*
  * @return object customer info
  */
	function customer_create($params) {
		if(isset($params)) {
      return $this->sendRequest('Customer', 'create', $params);
		}
		return "One or more parameters are not set";
	}

  /**
  * Send Request to Stripe Service
  *
  * Returns a formated response according the specified request.
  *
  * @param string $endpoint the endpoint name
  * @param string $method the method name
  * @param array $params the request parameters
	*
  * @return array a formatted reponse
  */
  function sendRequest($endpoint, $method, $params) {
    try {
      //\Stripe\Customer::create($request);
      $namespace = '\Stripe\\'.$endpoint;
      $result = $namespace::{$method}($params);
      return formatReponseMessage('ok', $namespace.'::'.$method, '200', $params, $result);
    } catch(\Stripe\Error\Card $e) {
      // Since it's a decline, \Stripe\Error\Card will be caught
      $err = $e->getJsonBody()['error'];
      return formatReponseMessage($e->getHttpStatus(), $err['type'], $err['code'], $err['param'], $err['message']);
    } catch (\Stripe\Error\RateLimit $e) {
      // Too many requests made to the API too quickly
      $err = $e->getJsonBody()['error'];
      return formatReponseMessage($e->getHttpStatus(), $err['type'], $err['code'], $err['param'], $err['message']);
    } catch (\Stripe\Error\InvalidRequest $e) {
      // Invalid parameters were supplied to Stripe's API
      $err = $e->getJsonBody()['error'];
      return formatReponseMessage($e->getHttpStatus(), $err['type'], $err['code'], $err['param'], $err['message']);
    } catch (\Stripe\Error\Authentication $e) {
      // Authentication with Stripe's API failed
      // (maybe you changed API keys recently)
      $err = $e->getJsonBody()['error'];
      return formatReponseMessage($e->getHttpStatus(), $err['type'], $err['code'], $err['param'], $err['message']);
    } catch (\Stripe\Error\ApiConnection $e) {
      // Network communication with Stripe failed
      $err = $e->getJsonBody()['error'];
      return formatReponseMessage($e->getHttpStatus(), $err['type'], $err['code'], $err['param'], $err['message']);
    } catch (\Stripe\Error\Base $e) {
      // Display a very generic error to the user, and maybe send
      $err = $e->getJsonBody()['error'];
      return formatReponseMessage($e->getHttpStatus(), $err['type'], $err['code'], $err['param'], $err['message']);
      // yourself an email
    } catch (Exception $e) {
      // Something else happened, completely unrelated to Stripe
      $err = $e->getJsonBody()['error'];
      return formatReponseMessage($e->getHttpStatus(), $err['type'], $err['code'], $err['param'], $err['message']);
    }
  }

	/**
	* Check configurations for errors
	*
	* Validates the set of configurations for Stripe
	*
	* @param array $config the array to validate
	*
	* @return boolean
	*/
	private function checkConfig($config) {
    if(isset($config) && !empty($config)) {
      foreach ($config as $config_value) {
        if(empty($config_value)) {
          return FALSE;
        }
      }
      return TRUE;
    }
    return FALSE;
	}
}

/**
* Format The Service Response Message
*
* @param string $status a value to get the status of the sent request
* @param string $type display the error type if erorr exists
* @param string $code the error code if exists, or 200 if the request succeeds
* @param array $param the error parameters
* @param string $message the error message if error exists, or the response body if the request succeeds
*
* @return array
*/
function formatReponseMessage($status, $type, $code, $param, $message) {
  return array(
    'status'  => $status,
    'type'    => $type,
    'code'    => $code,
    'param'   => $param,
    'message' => $message
  );
}
