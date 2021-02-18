<?php
namespace Mrpck\PayPal;

/**
 * This package uses the new paypal rest api.
 * @author Michele Rosica <michelerosica@gmail.com>
 *
 * https://developer.paypal.com/docs/api/overview/
 */
class PayPal 
{
	var $user = '';
	var $password = '';


	/**
	* Constructor
	*/
    function __construct($host="localhost", $database=null, $user="root", $password="") 
	{
		//...
    }

	/**
	* destructor
	*/
    function __destruct() 
	{
        // closing connection...
    }
	
	/**
	* Connect to server
	* @access private
	*/
	private function connect()
	{
        // Connecting...
    }
	
	/**
	* returns your access token
	*/
    function retrieveAccessToken()
    {
        $clientId = env('PAYPAL_CLIENT_ID');
        $secret   = env('PAYPAL_SECRET');
        $data = "grant_type=client_credentials";

        $url = 'https://api-m.sandbox.paypal.com/v1/oauth2/token';
        $result = json_decode(Util::callAPI('POST', $url, $data, null, $clientId.":".$secret), true);
        if (empty($result) || !isset($result['access_token']))
            return response()->json(['message' => 'Token not found!'], 404);

        return array(
            "token" => $result['access_token'],
            "token_type" => "bearer",
            "expires_in" => $result['expires_in'],
            "app_id" => $result['app_id'],
            "request_id" => Util::randomPassword(16)
        );
    }
}
