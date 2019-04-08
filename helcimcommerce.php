<?php

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FUNCTION - CONFIG
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function helcimcommerce_config(){
	
	// SET
	$configarray = array(
	 "FriendlyName" => array("Type" => "System", "Value"=>"Helcim Commerce"),
	 "accountId" => array("FriendlyName" => "Account ID", "Type" => "text", "Size" => "20", ),
	 "token" => array("FriendlyName" => "Token", "Type" => "text", "Size" => "20", ),     
	 "url" => array("FriendlyName" => "Gateway URL", "Type" => "text", "Value"=>"https://secure.myhelcim.com/api/", "Size" => "128", ),
	 "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Tick this to run test transactions only", ),
	);
	
	// RETURN
	return $configarray;
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FUNCTION - CAPTURE
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function helcimcommerce_capture($params){

	// GATEWAY SPECIFIC VARIABLES
	$accountId = $params['accountId'];
	$apiToken = $params['token'];
	$gatewayurl = $params['url'];
	$gatewaytestmode = $params['testmode'] == 'Yes' ? 1 : 0;
	$cvvIndicator = 1; # Change to 4 to disable CVV check

	// INVOICE VARIABLES
	$invoiceid = $params['invoiceid'];
	$amount = $params['amount']; // FORMAT: ##.##
	$currency = $params['currency']; // CURRENCY CODE

	// CLIENT VARIABLES
	$clientid = $params['clientdetails']['id'];
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];

	// CARD DETAILS
	// IF TOKEN EXISTS, USE IT
	list($cardToken, $cardF4l4) = explode(';', $params['gatewayid']);
	if (!$cardToken || !$cardF4l4) {	
		$cardtype = $params['cardtype'];
		$cardnumber = $params['cardnum'];
		$cardexpiry = $params['cardexp']; // FORMAT: MMYY
		$cardstart = $params['cardstart']; // FORMAT: MMYY
		$cardissuenum = $params['cardissuenum'];
		$cardcvv = $params["cardcvv"];
		if (!$cardcvv)
			$cvvIndicator = 4;
	}

	if ($cardToken && $cardF4l4) {
		$cvvIndicator = 4;
		$cardFields = '&cardToken='.$cardToken.'&cardF4L4='.$cardF4l4;
	} else {
		$cardFields = '&cardNumber='.$cardnumber.'&cardExpiry='.$cardexpiry;
	}

	$postFields = 'accountId='.$accountId.'&apiToken='.$apiToken.'&test='.$gatewaytestmode.
				  '&transactionType=purchase&amount='.$amount.$cardFields.'&cvvIndicator='.$cvvIndicator.
				  '&cardCVV='.$cardcvv.'&orderNumber='.$invoiceid.'&billing_contactName='.$firstname.' '.$lastname.'&billing_email='.$email.
				  '&billing_street1='.$address1.'&billing_street2='.$address2.'&billing_city='.$city.
				  '&billing_province='.$state.'&billing_postalCode='.$postcode.'&billing_country='.$country.
				  '&billing_phone='.$phone;
	
	// PERFORM TRANSACTION HERE & GENERATE $RESULTS ARRAY, EG:
	$curlOptions = array( CURLOPT_RETURNTRANSFER => 1,
						  CURLOPT_AUTOREFERER => TRUE,
						  CURLOPT_FRESH_CONNECT => TRUE,
						  CURLOPT_HEADER => FALSE,
						  CURLOPT_POST => TRUE,
						  CURLOPT_POSTFIELDS => $postFields,
						  CURLOPT_TIMEOUT => 30 );
	
	// NEW CURL RESOURCE
	$curl = curl_init($gatewayurl);

	// SET CURL OPTIONS ARRAY
	curl_setopt_array($curl, $curlOptions);

	// GET URL RESPONSE
	$response = curl_exec($curl);

	// CLOSE CURL RESOURCE
	curl_close($curl);
	
	// BUILD RESPONSE
	$responseObj = @simplexml_load_string($response);
	$responseArray = formatSimpleXMLToArray($responseObj);

	// CHECK GATEWAY RESPONSE
	if (@$responseObj->response == 1) {

		// TRANSACTION COMPLETED SUCCESSFULLY

		// UPDATE TOKEN
		$table = "tblclients";
		$update = array("gatewayid"=>$responseArray['transaction']['cardToken'].';'.str_replace('*', '', $responseArray['transaction']['cardNumber']));
		$where = array("id"=>$clientid);
		update_query($table,$update,$where);

		return array("status"=>"success","transid"=>$responseArray['transaction']["transactionId"],"rawdata"=>$responseArray);
	}else{

		// TRANSACTION DECLINED
		return array("status"=>"declined","rawdata"=>$responseArray);
	
	}

} // END - FUNCTION

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FUNCTION - REFUND
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function helcimcommerce_refund($params) {	
	
	// GATEWAY SPECIFIC VARIABLES
	$accountId = $params['accountId'];
	$apiToken = $params['token'];
	$gatewayurl = $params['url'];
	$gatewaytestmode = $params['testmode'] == 'Yes' ? 1 : 0;

	// INVOICE VARIABLES
	$invoiceid = $params['invoiceid'];
	$amount = $params['amount']; // FORMAT: ##.##
	$currency = $params['currency']; // CURRENCY CODE

	// CLIENT VARIABLES
	$clientid = $params['clientdetails']['id'];
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];

	// IF TOKEN EXISTS, USE IT
	list($cardToken, $cardF4l4) = explode(';', $params['gatewayid']);
	if (!$cardToken || !$cardF4l4) {
		
		// CARD DETAILS
		$cardtype = $params['cardtype'];
		$cardnumber = $params['cardnum'];
		$cardexpiry = $params['cardexp']; // FORMAT: MMYY
		$cardstart = $params['cardstart']; // FORMAT: MMYY
		$cardissuenum = $params['cardissuenum'];
	}
	if ($cardToken && $cardF4l4) {
		$cardFields = '&cardToken='.$cardToken.'&cardF4L4='.$cardF4l4;
	} else {
		$cardFields = '&cardNumber='.$cardnumber.'&cardExpiry='.$cardexpiry;
	}

	$postFields = 'accountId='.$accountId.'&apiToken='.$apiToken.'&test='.$gatewaytestmode.
				  '&transactionType=refund&amount='.$amount.$cardFields.
				  '&billing_contactName='.$firstname.' '.$lastname.'&billing_email='.$email.
				  '&billing_street1='.$address1.'&billing_street2='.$address2.'&billing_city='.$city.
				  '&billing_province='.$state.'&billing_postalCode='.$postcode.'&billing_country='.$country.
				  '&billing_phone='.$phone;
	
	// PERFORM TRANSACTION HERE & GENERATE $RESULTS ARRAY, EG:
	$curlOptions = array( CURLOPT_RETURNTRANSFER => 1,
						  CURLOPT_AUTOREFERER => TRUE,
						  CURLOPT_FRESH_CONNECT => TRUE,
						  CURLOPT_HEADER => FALSE,
						  CURLOPT_POST => TRUE,
						  CURLOPT_POSTFIELDS => $postFields,
						  CURLOPT_TIMEOUT => 30 );
	
	// NEW CURL RESOURCE
	$curl = curl_init($gatewayurl);
	
	// SET CURL OPTIONS ARRAY
	curl_setopt_array($curl, $curlOptions);

	// GET URL RESPONSE
	$response = curl_exec($curl);

	// CLOSE CURL RESOURCE
	curl_close($curl);
	
	// BUILD RESPONSE
	$responseObj = @simplexml_load_string($response);
	$responseArray = formatSimpleXMLToArray($responseObj);

	// CHECK GATEWAY RESPONSE
	if ($responseObj->response == 1) {

		// TRANSACTION COMPLETED SUCCESSFULLY
		return array("status"=>"success","transid"=>$responseArray['transaction']["transactionId"],"rawdata"=>$responseArray);
	
	}else{

		// TRANSACTION DECLINED
		return array("status"=>"declined","rawdata"=>$responseArray);
	
	}

} // END - FUNCTION

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FUNCTION - STORE REMOTE
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function helcimcommerce_storeremote($params){

	// GATEWAY SPECIFIC VARIABLES
	$accountId = $params['accountId'];
	$apiToken = $params['token'];
	$gatewayurl = $params['url'];
	$gatewaytestmode = $params['testmode'] == 'Yes' ? 1 : 0;
	$cvvIndicator = 1; # Change to 4 to disable CVV check, must also be removed from WHMCS template

	// CLIENT VARIABLES
	$clientid = $params['clientdetails']['id'];
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];

	// CARD DETAILS
	$cardtype = $params['cardtype'];
	$cardnumber = $params['cardnum'];
	$cardexpiry = $params['cardexp']; // FORMAT: MMYY
	$cardstart = $params['cardstart']; // FORMAT: MMYY
	$cardissuenum = $params['cardissuenum'];
	$cardcvv = $params["cardcvv"];

	$cardFields = '&cardNumber='.$cardnumber.'&cardExpiry='.$cardexpiry;

	$postFields = 'accountId='.$accountId.'&apiToken='.$apiToken.'&test='.$gatewaytestmode.
				  '&transactionType=verify&amount=0'.$cardFields.'&cvvIndicator='.$cvvIndicator.
				  '&cardCVV='.$cardcvv.'&billing_contactName='.$firstname.' '.$lastname.'&billin g_email='.$email.
				  '&billing_street1='.$address1.'&billing_street2='.$address2.'&billing_city='.$city.
				  '&billing_province='.$state.'&billing_postalCode='.$postcode.'&billing_country='.$country.
				  '&billing_phone='.$phone;
	
	// PERFORM TRANSACTION HERE & GENERATE $RESULTS ARRAY, EG:
	$curlOptions = array( CURLOPT_RETURNTRANSFER => 1,
						  CURLOPT_AUTOREFERER => TRUE,
						  CURLOPT_FRESH_CONNECT => TRUE,
						  CURLOPT_HEADER => FALSE,
						  CURLOPT_POST => TRUE,
						  CURLOPT_POSTFIELDS => $postFields,
						  CURLOPT_TIMEOUT => 30 );
	
	// NEW CURL RESOURCE
	$curl = curl_init($gatewayurl);

	// SET CURL OPTIONS ARRAY
	curl_setopt_array($curl, $curlOptions);

	// GET URL RESPONSE
	$response = curl_exec($curl);

	// CLOSE CURL RESOURCE
	curl_close($curl);

	// BUILD RESPONSE
	$responseObj = @simplexml_load_string($response);
	$responseArray = formatSimpleXMLToArray($responseObj);

	// CHECK GATEWAY RESPONSE
	if ($responseObj->response == 1){

		// TRANSACTION COMPLETED SUCCESSFULLY
		$gatewayid = $responseArray['transaction']['cardToken'].';'.str_replace('*', '', $responseArray['transaction']['cardNumber']);
		return array("status"=>"success","gatewayid"=>$gatewayid,"rawdata"=>$responseArray);
	
	}else{

		// TRANSACTION DECLINED
		return array("status"=>"declined","rawdata"=>$responseArray);
	}

} // END - FUNCTION

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FUNCTION - SIMPLE XML TO ARRAY
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function formatSimpleXMLToArray($simpleXMLObj){

	// INIT
	$array = array();

	// CHECK IF OBJECT
	if(is_object($simpleXMLObj)){

		// LOOP CHILDREN
		foreach ($simpleXMLObj->children() as $key => $child) {

			//
			// CHECK IF CHILDREN HAS DUPLICATE KEYS
			//

			// INIT
			$keys = array_keys((array)$simpleXMLObj->children());

			// CHECK IF SAME AMOUT OF KEYS AS CHILDREN
			if(count($keys) == count($simpleXMLObj->children())){

				// SET
				$duplicatKeys = false;

			}else{

				// SET
				$duplicatKeys = true;
			
			}

			// IF CONTAINS MULTIPLE CHILDREN
			if(count($child->children()) > 0 ){

				// IF DUPLICATE KEYS
				if($duplicatKeys){

					// RECURSE
					$array[] = formatSimpleXMLToArray($child);

				}else{

					// RECURSE
					$array[$key] = formatSimpleXMLToArray($child);

				}
		
			}else{
				
				// CAST
				$array[$key] = (string)$child;

			}
		
		}

	}

	// RETURN
	return $array;

} // END - FUNCTION

?>
