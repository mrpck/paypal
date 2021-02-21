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
	var $host    = '';
	var $host_sandbox = 'https://api-m.sandbox.paypal.com';
	var $host_live    = 'https://api-m.paypal.com';
	var $clientId  = '';
	var $secret    = '';
	var $connected = false;
	var $token     = '';
	var $requestId = '';
	var $paypalId  = '';
	var $status    = '';
    var $productId = '';
	var $planId    = '';
    var $subId     = '';

	/**
	* Constructor
	*/
    function __construct($clientId, $secret, $sandbox = false) 
	{
		//...
		$this->clientId = $clientId;
		$this->secret = $secret;
		$this->host   = $sandbox ? $this->host_sandbox : $this->host_live;
		
		// connecting to paypal
        $this->GetAccessToken();
    }

	/**
	* destructor
	*/
    function __destruct() 
	{
        // closing connection...
        $this->token = '';
        $this->connected = false;
    }
	
	function IsConnected()
    {
        return $this->connected;
    }

	/**
	* Gets an access token for a set of permissions.
	* 
	* returns your access token
	*/
    function GetAccessToken()
    {
        $data = "grant_type=client_credentials";

        $url = $this->host.'/v1/oauth2/token';
        $result = json_decode($this->callAPI('POST', $url, $data, null, $this->clientId.":".$this->secret), true);
        if (empty($result) || !isset($result['access_token']))
            return json_encode(['message' => 'Token not found!'], 404);

		$this->token = $result['access_token'];
		$this->requestId = $this->randomPassword(16);
		$this->connected = true;
        return json_encode(array(
            "token" => $result['access_token'],
            "token_type" => "bearer",
            "expires_in" => $result['expires_in'],
            "app_id" => $result['app_id'],
            "request_id" => $this->requestId
        ));
    }

    /** 1. CREATE A PRODUCT
	 *
     * Plan: {"id":"P-3WM27231DU1309943MAZIMJA","status":"ACTIVE","link":""}
     */
	function CreateProduct($data)
    {
		if (empty($this->token)) {
			$req = json_decode($this->GetAccessToken(), true);
			if (is_array($req) && isset($req['token']))
				$this->token = $req['token'];
		}

		if (empty($this->token))
			return null;

        $url = $this->host.'/v1/catalogs/products';
        $httpHeader = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->token,
            'PayPal-Request-Id: PLAN-'. $this->requestId
        );

        $result = json_decode($this->callAPI('POST', $url, json_encode($data), $httpHeader), true);
        //echo '<pre>'; var_dump($result); echo '</pre>'; die;
        if (!isset($result['id'])) {
            //$this->log->Error(json_encode($result['details']));
            return json_encode(["status" => "fail", "message" => $result['details']], 200);
        }

        $this->productId  = $result['id'];
        //return json_encode($result);
        return $this->productId;
    }

    /** 2. CREATE A PLAN
	 *
     */
	function CreatePlan($data)
    {
		if (empty($this->token)) {
			$req = json_decode($this->GetAccessToken(), true);
			if (is_array($req) && isset($req['token']))
				$this->token = $req['token'];
		}

		if (empty($this->token))
			return null;

        $url = $this->host.'/v1/billing/plans';
        $httpHeader = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->token,
            'PayPal-Request-Id: PLAN-'. $this->requestId
        );

        $result = json_decode($this->callAPI('POST', $url, json_encode($data), $httpHeader), true);
        echo '<pre>'; var_dump($result); echo '</pre>'; die;
        if (!isset($result['id'])) {
            //$this->log->Error(json_encode($result['details']));
            return json_encode(["status" => "fail", "message" => $result['details']], 200);
        }

        $this->planId = $result['id'];
        //return json_encode($result);
        return $this->planId;
    }
	
	/** 3. CREATE A SUBSCRIPTION
	 *
     * If activate subscription succeeds, it triggers the BILLING.SUBSCRIPTION.CREATED webhook.
     */
	function CreateSubscription($data)
    {
		if (empty($this->token)) {
			$req = json_decode($this->GetAccessToken(), true);
			if (is_array($req) && isset($req['token']))
				$this->token = $req['token'];
		}

		if (empty($this->token))
			return null;

        $url = $this->host.'/v1/billing/subscriptions';
        $httpHeader = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->token,
            'PayPal-Request-Id: SUBSCRIPTION-'. $this->requestId
        );

        $result = json_decode($this->callAPI('POST', $url, json_encode($data), $httpHeader), true);
        //echo '<pre>'; var_dump($result); echo '</pre>'; die;
        if (!isset($result['id'])) {
            //$this->log->Error(json_encode($result['details']));
            return json_encode(["status" => "fail", "message" => $result['details']], 200);
        }

        $link_approve = '';
        foreach ($result['links'] as $link) {
            if ($link['rel'] == 'approve')
                $link_approve = $link['href'];
        }

        $this->subId    = $result['id'];
		$this->paypalId = $result['id'];
		$this->status   = $result['status'];
        if (empty($link_approve))
            return $this->subId;
        return json_encode(array(
            "id" => $result['id'],
            "status" => $result['status'],
            "link"   => $link_approve
        ));
    }
	
	/** GET A SUBSCRIPTION
	 *
     */
	function GetSubscription($paypal_id)
    {
		if (empty($this->token)) {
			$req = json_decode($this->GetAccessToken(), true);
			if (is_array($req) && isset($req['token']))
				$this->token = $req['token'];
		}

		if (empty($this->token) || empty($paypal_id))
			return null;

        $url = $this->host.'/v1/billing/subscriptions/'.$paypal_id;
        $httpHeader = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->token
        );

        $result = json_decode($this->callAPI('GET', $url, null, $httpHeader), true);
        //echo '<pre>'; var_dump($result); echo '</pre>'; die;
        if (empty($result) || !isset($result['id']))
            return null;

		$this->paypalId = $paypal_id;
		$this->status   = $result['status'];
		$this->planId   = $result['plan_id'];
		return json_encode($result);
    }
	
	/** GET A STATUS SUBSCRIPTION
	 *
	 * The status of the subscription.
	 * 
	 * The possible values are:
     *
	 * APPROVAL_PENDING. The subscription is created but not yet approved by the buyer.
	 * APPROVED. The buyer has approved the subscription.
	 * ACTIVE. The subscription is active.
	 * SUSPENDED. The subscription is suspended.
	 * CANCELLED. The subscription is cancelled.
	 * EXPIRED. The subscription is expired.
     */
	function GetStatus($paypal_id)
    {
		if ($this->paypalId == $paypal_id && !empty($this->status))
			return $this->status;

		$req = json_decode($this->GetSubscription($paypal_id), true);
		return !empty($req) ? $req['status'] : null;
	}

    /** GET A PRODUCT
	 *
     */
	function GetProductById($product_id)
    {
        if (empty($this->token)) {
			$req = json_decode($this->GetAccessToken(), true);
			if (is_array($req) && isset($req['token']))
				$this->token = $req['token'];
		}

		if (empty($this->token) || empty($product_id))
			return null;

        $url = $this->host.'/v1/catalogs/products/'.$product_id;
        $httpHeader = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->token
        );

        $result = json_decode($this->callAPI('GET', $url, null, $httpHeader), true);
        //echo '<pre>'; var_dump($result); echo '</pre>'; die;
        if (empty($result) || !isset($result['id']))
            return null;

		$this->productId = $result['id'];
		return json_encode($result);
	}
	
	/** GET A PLAN
	 *
     */
	function GetPlanById($plan_id)
    {
        if (empty($this->token)) {
			$req = json_decode($this->GetAccessToken(), true);
			if (is_array($req) && isset($req['token']))
				$this->token = $req['token'];
		}

		if (empty($this->token) || empty($plan_id))
			return null;

        $url = $this->host.'/v1/billing/plans/'.$plan_id;
        $httpHeader = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->token
        );

        $result = json_decode($this->callAPI('GET', $url, null, $httpHeader), true);
        //echo '<pre>'; var_dump($result); echo '</pre>'; die;
        if (empty($result) || !isset($result['id']))
            return null;

		$this->status = $result['status'];
		$this->planId = $plan_id;
		return json_encode($result);
	}

    /** GET A PLAN SUBSCRIPTION
	 *
     */
	function GetPlanBySubId($sub_id)
    {
        if (empty($this->token)) {
			$req = json_decode($this->GetAccessToken(), true);
			if (is_array($req) && isset($req['token']))
				$this->token = $req['token'];
		}

		if (empty($this->token) || empty($sub_id))
			return null;

		$req = json_decode($this->GetSubscription($sub_id), true);
		$plan_id = !empty($req) ? $req['plan_id'] : null;

        if (empty($plan_id))
            return null;

        $url = $this->host.'/v1/billing/plans/'.$plan_id;
        $httpHeader = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->token
        );

        $result = json_decode($this->callAPI('GET', $url, null, $httpHeader), true);
        //echo '<pre>'; var_dump($result); echo '</pre>'; die;
        if (empty($result) || !isset($result['id']))
            return null;

		$this->status = $result['status'];
		$this->planId = $plan_id;
		return json_encode($result);
	}
	
	/** GET A STATUS SUBSCRIPTION
	 *
     */
	function IsActive($paypal_id)
    {
		if ($this->paypalId == $paypal_id && !empty($this->status))
			return $this->status == 'ACTIVE';

		$req = json_decode($this->GetSubscription($paypal_id), true);
		return !empty($req) && $req['status'] == 'ACTIVE';
	}
	
	/** ACTIVATE A SUBSCRIPTION
	 *
     * If activate subscription succeeds, it triggers the BILLING.SUBSCRIPTION.CREATED webhook.
     */
	function ActivateSubscription($paypal_id, $reason = 'Reactivating the subscription')
    {
		if (empty($this->token)) {
			$req = json_decode($this->GetAccessToken(), true);
			if (is_array($req) && isset($req['token']))
				$this->token = $req['token'];
		}

		if (empty($this->token))
			return null;

        $url = $this->host.'/v1/billing/subscriptions/'.$paypal_id.'/activate';
        $httpHeader = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->token,
            'PayPal-Request-Id: SUBSCRIPTION-'. $this->requestId
        );

		$data = array(
            'reason' => $reason
        );

        $result = json_decode($this->callAPI('POST', $url, json_encode($data), $httpHeader), true);
        //echo '<pre>'; var_dump($result); echo '</pre>'; die;
        if (!isset($result['id'])) {
            //$this->log->Error(json_encode($result['details']));
            return json_encode(["status" => "fail", "message" => $result['details']], 200);
        }

        $link_approve = '';
        foreach ($result['links'] as $link) {
            if ($link['rel'] == 'approve')
                $link_approve = $link['href'];
        }

		$this->paypalId = $result['id'];
		$this->status   = $result['status'];
        return json_encode(array(
            "id" => $result['id'],
            "status" => $result['status'],
            "link" => $link_approve
        ));
    }
	
	/** CANCEL A SUBSCRIPTION
	 *
     * If subscription cancellation succeeds, it triggers the BILLING.SUBSCRIPTION.CANCELLED webhook.
     */
    function CancelSubscription($paypal_id, $reason = 'Not satisfied with the service')
    {
		if (empty($this->token)) {
			$req = json_decode($this->GetAccessToken(), true);
			if (is_array($req) && isset($req['token']))
				$this->token = $req['token'];
		}

		if (empty($this->token))
			return null;

		$url = $this->host.'/v1/billing/subscriptions/'.$paypal_id.'/cancel';

        $httpHeader = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->token
        );
		
		$data = array(
            'reason' => $reason
        );
		
		try {
			$result = json_decode($this->callAPI('POST', $url, json_encode($data), $httpHeader), true);
			if (!empty($result)) {
				//$this->log->Error(json_encode($result));
				//return response()->json(["status" => "fail", "message" => $result['details']], 200);
			}
		} catch (\Exception $e) {
			//$this->log->Error($e->getMessage());
		}

        return json_encode(["status" => "success", "message" => 'Your subscription has been successfully canceled'], 200);
    }
	
	private function callAPI($method, $url, $data=false, $httpHeader=null, $userpwd=null)
    {
        $ch = curl_init();
        switch ($method){
          case "POST":
             curl_setopt($ch, CURLOPT_POST, true);
             if ($data)
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
             break;
          case "PUT":
             curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
             if ($data)
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);			 					
             break;
          default:
             if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
        }
    
        // OPTIONS:
        // Set the url
        curl_setopt($ch, CURLOPT_URL, $url);
        if (empty($httpHeader))
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("REMOTE_ADDR: ".$this->fakeip(),"X-Client-IP: ".$this->fakeip(),"Client-IP: ".$this->fakeip(),"HTTP_X_FORWARDED_FOR: ".$this->fakeip(),"X-Forwarded-For: ".$this->fakeip()));
        else    
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        //curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1:8888");
        if (!empty($userpwd))
            curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
    
        // EXECUTE:
        $result = curl_exec($ch);
        if(!$result){
            //die("Connection Failure");
            return null;
        }
        curl_close($ch);

        return $result;
    }
	
	private function fakeip()  
    {  
        return long2ip(mt_rand(0, 65537) * mt_rand(0, 65535));   
    }
	
	private function randomPassword($length=8, $toupper=false, $alphanumeric=true)
    {
        $alphabet = "abcdefghjklmnpqrstuwxyzABCDEFGHJKLMNPQRSTUWXYZ23456789";
        if (!$alphanumeric)
            $alphabet = "123456789";

        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i=0; $i<$length; $i++){
            $n = rand(0,$alphaLength);
            $pass[] = $alphabet[$n];
        }
        return $toupper ? strtoupper(implode($pass)) : implode($pass); //turn the array into a string
    }
}
