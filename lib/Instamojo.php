<?php
/**
 * ObvPayment
 * used to manage ObvPayment API calls
 * 
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . "curl.php";
include_once __DIR__ . DIRECTORY_SEPARATOR . "ValidationException.php";

use \ValidationException as ValidationException;
use \Exception as Exception;

Class ObvPayment
{
	private $api_endpoint;
	private $auth_endpoint;
	private $auth_headers;
	private $access_token;
	private $client_id;
	private $client_secret;
	
	 function __construct()
	{
		$this->curl = new Curl();
		$this->curl->setCacert(__DIR__."/cacert.pem");

		$this->api_endpoint  = "https://payment.obv.me/api/api.php";

		$this->auth_endpoint = "https://payment.obv.me/api/api.php"; 
	}
	
	public function createOrderPayment($data)
	{
		$endpoint = $this->api_endpoint;
		$result = $this->curl->post($endpoint,$data);
			$result =json_decode($result);
		if(isset($result->order))
		{
			return $result;
		}else{
			$errors = array();  
			if(isset($result->message))
				throw new ValidationException("Validation Error with message: $result->message",array($result->message),$result);
			
			foreach($result as $k=>$v)
			{
				if(is_array($v))
					$errors[] =$v[0];
			}
			if($errors)
				throw new ValidationException("Validation Error Occurred with following Errors : ",$errors,$result);
		}
	}
	
	
	public function getOrderById($id)
	{
		$endpoint = $this->api_endpoint;
		$result = $this->curl->get($endpoint,array("headers"=>$this->auth_headers));
		
		$result = json_decode($result);
		if(isset($result->id) and $result->id)
			return $result;
		else
			throw new Exception("Unable to Fetch Payment Request id:'$id' Server Responds ".print_R($result,true));
	}

	public function getPaymentStatus($payment_id, $payments){
		foreach($payments as $payment){
		    if($payment->id == $payment_id){
			    return $payment->status;
		    }
		}
	}
	
}
