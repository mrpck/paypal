# mysqldb
The MySqlDB Class Library is a high level wrapper around the MySql.

## Install
Copy the files under `src/` to your program

OR

```bash
composer require websvc/php-mysql-db 1.0.2
```


## Usage

```php
use Mrpck\PayPal\PayPal;

$PAYPAL_CLIENT_ID='AW7a1Xvdfkdfpdprkspq7mJZwDRNNFxYSRxRTApuiuiuiuitT0Gq5';
$PAYPAL_SECRET='Eghgh4h2MiSGghghgh7DCviixQ7WMrbBfE7cRfo4KLqghghgUJKWYghghgh8HpK5eGK';
$sandbox = false;

$p = new PayPal($PAYPAL_CLIENT_ID, $PAYPAL_SECRET, $sandbox);

echo 'IsConnected: '.$p->IsConnected().'<br/><br/>';

$data = array(
	"name" => "Video Streaming",
	"description" => "Video streaming service",
	"type" => "DIGITAL",
	"category" => "ONLINE_SERVICES",
	"image_url" => "https://packagist.org/streaming.jpg",
	"home_url"  => "https://packagist.org/home"
);

// 1. Create a product
$productId = $p->CreateProduct($data);
echo 'Product: '.$productId.'<br/><br/>';


$data = array(
	"product_id" => $productId,
	"name"   => "Affiliazione DAY",
	"status" => "ACTIVE",
	"description" => "Video streaming service",
	"billing_cycles" => array(
		array(
			"pricing_scheme"  => array(
				"fixed_price" => array(
					"currency_code" => "EUR",
					"value" => "10.90"
				)
			),
			"frequency" => array(
				"interval_unit"  => "DAY",
				"interval_count" => 1
			),
			"tenure_type" => "REGULAR",
			"sequence"    => 1,
			"total_cycles" => 0
		)
	),
	"payment_preferences" => array(
		"auto_bill_outstanding" => false,
		"payment_failure_threshold" => 0
	),
	"quantity_supported" => false
);

// 2. Create a plan
$planId = $p->CreatePlan($data);
echo 'Plan: '.$planId.'<br/><br/>';


$data = array(
	'plan_id'  => $planId,
	'quantity' => '1',
	'application_context' => array(
	  'brand_name' => 'Packagist',
	  'shipping_preference' => 'NO_SHIPPING',
	  'return_url' => 'https://packagist.org/account/myads',
	  'cancel_url' => 'https://packagist.org/premium'
	)
);

// 3. Create a subscription
$subId = $p->CreateSubscription($data);
echo 'Subscription: '.$subId.'<br/><br/>';


// ...
echo 'GetProductById: '.$p->GetProductById($productId).'<br/><br/>';

echo 'GetPlanById: '.$p->GetPlanById($planId).'<br/><br/>';

echo 'GetPlanBySubId: '.$p->GetPlanBySubId($subId).'<br/><br/>';

echo 'GetSubscription: '.$p->GetSubscription($subId).'<br/><br/>';

echo 'Status: '.$p->GetStatus($subId).'<br/><br/>';

echo 'IsActive: '.$p->IsActive($subId).'<br/><br/>';

```
