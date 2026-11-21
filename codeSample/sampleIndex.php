<?php

// 
// Variables
// 


// Will be retrieved From Database
$TIN = '111111111';
$VRN = '';
$certKey = '10TZ999999';
$DC = 1;
$RCTNUM = 1;
$GC = $RCTNUM;
$ZNUM = '20190930';

// 
// Send Details to TRA
// 

// General Information
$xml_doc = "<?xml version='1.0' encoding='UTF-8'?>";
$efdms_open = "<EFDMS>";
$efdms_close = "</EFDMS>";
$efdms_signatureOpen = "<EFDMSSIGNATURE>";
$efdms_signatureClose = "</EFDMSSIGNATURE>";

// Extract Client Public and Private Digital Signatures
$cert_store = file_get_contents('vfdClient.pfx');
$clientSignature = openssl_pkcs12_read($cert_store, $cert_info, 'Password');
$privateKey = $cert_info['pkey'];
$publicKey = openssl_get_privatekey($privateKey);
$certBase = base64_encode('00 11 22 33 44 55 66 77 88 99 aa bb cc dd ee ff');

// Compute Signature with SHA1
$payloadData = "<REGDATA><TIN>$TIN</TIN><CERTKEY>$certKey</CERTKEY></REGDATA>";
$payloadDataSignature = signPayloadPlain($payloadData, $publicKey);
$signedMessageRegistration = $xml_doc . $efdms_open . $payloadData . $efdms_signatureOpen . $payloadDataSignature . $efdms_signatureClose . $efdms_close;

// Step 1
// Send Request To TRA for Registration
$urlReceipt = 'https://virtual.tra.go.tz/efdmsRctApi/api/vfdRegReq';
$headers = array(
    'Content-type: application/xml',
    'Cert-Serial: ' . $certBase,
    'Client: WEBAPI'
);

$registrationACK = sendRequest($urlReceipt, $headers, $signedMessageRegistration);
$xmlACKRegistration = new SimpleXMLElement($registrationACK);
$ackCode = $xmlACKRegistration->EFDMSRESP->ACKCODE;

// Step 2
// Send Request To TRA for Token Authentication
if ($ackCode == 0) /* 0 = Response Code for Successful Registration */ {
    $username = $xmlACKRegistration->EFDMSRESP->USERNAME;
    $password = $xmlACKRegistration->EFDMSRESP->PASSWORD;
    $routingKey = $xmlACKRegistration->EFDMSRESP->ROUTINGKEY;
    $registrationID = $xmlACKRegistration->EFDMSRESP->REGID;
    $receiptCode = $xmlACKRegistration->EFDMSRESP->RECEIPTCODE;
    $UIN = $xmlACKRegistration->EFDMSRESP->UIN;
    $urlReceipt = 'https://virtual.tra.go.tz/efdmsRctApi/vfdtoken';
    $headers = '';
    $authenticationData = "username=$username&password=$password&grant_type=password";
    echo $authenticationData;
    $tokenACKData = sendRequest($urlReceipt, $headers, $authenticationData);
    $token = $tokenACKData['access_token'];
} else {
    $ackMsg = $xmlACKRegistration->EFDMSRESP->ACKMSG;
    echo 'Error ' . $ackMsg;
    exit();
}

// Step 3
// Post Receipt to TRA
// Generate Payload Data
$RCTVNUM = $receiptCode . $GC;
$payloadData = "<RCT><DATE>2019- 09 -25</DATE><TIME>11:38:00</TIME><TIN>111111111</TIN><REGID>TZ090055567</REGID><EFDSERIAL>10TZ999999</EFDSERIAL><CUSTIDTYPE>1</CUSTIDTYPE><CUSTID>111222333</CUSTID><CUSTNAME>RichardKazimoto</CUSTNAME><MOBILENUM>0713655545</MOBILENUM><RCTNUM>1</RCTNUM><DC>1</DC><GC>1</GC><ZNUM>20190625</ZNUM><RCTVNUM>GU72D81</RCTVNUM><ITEMS><ITEM><ID>1</ID><DESC>Sponsorship deal to TRAFC</DESC><QTY>1</QTY><TAXCODE>1</TAXCODE><AMT>20000.01</AMT></ITEM></ITEMS><TOTALS><TOTALTAXEXCL>18000.00</TOTALTAXEXCL><TOTALTAXINCL>38000.0</TOTALTAXINCL><DISCOUNT>0.00</DISCOUNT></TOTALS><PAYMENTS><PMTTYPE>CASH</PMTTYPE><PMTAMOUNT>50000.00</PMTAMOUNT><PMTTYPE>CHEQUE</PMTTYPE><PMTAMOUNT>100000.00</PMTAMOUNT><PMTTYPE>CCARD</PMTTYPE><PMTAMOUNT>68000.00</PMTAMOUNT><PMTTYPE>EMONEY</PMTTYPE><PMTAMOUNT>0.00</PMTAMOUNT></PAYMENTS><VATTOTALS><VATRATE>A</VATRATE><NETTAMOUNT>100000.00</NETTAMOUNT><TAXAMOUNT>16500.00</TAXAMOUNT><VATRATE>B</VATRATE><NETTAMOUNT>100000.00</NETTAMOUNT><TAXAMOUNT>0.00</TAXAMOUNT><VATRATE>C</VATRATE><NETTAMOUNT>100000.00</NETTAMOUNT><TAXAMOUNT>0.00</TAXAMOUNT></VATTOTALS></RCT>";

$payloadDataSignatureReceipt = signPayloadPlain($payloadData, $publicKey);
$signedMessageReceipt = $xml_doc . $efdms_open . $payloadData . $efdms_signatureOpen . $payloadDataSignatureReceipt . $efdms_signatureClose . $efdms_close;

$urlReceipt = 'https://virtual.tra.go.tz/efdmsRctApi/api/efdmsRctInfo';

$headers = array(
    'Content-type: application/xml',
    'Routing-Key: ' . $routingKey,
    'Cert-Serial: ' . $certBase,
    'Client: WEBAPI',
    'Authorization: Bearer ' . $token
);

$receiptACK = sendRequest($urlReceipt, $headers, $signedMessageReceipt);
$xmlACKReceipt = new SimpleXMLElement($receiptACK);
$ackCodeReceipt = $xmlACKReceipt->RCTACK->ACKCODE;
$ackReceiptMessage = $xmlACKReceipt->RCTACK->ACKMSG;
echo htmlentities($receiptACK);

// 
// Functions
// 

/**
 * Compute signature with SHA-256
 * @version 1.0
 * @author TRA
 * @author AlphaOlomi <hello@alphalomi.com>
 * @param $payload_data
 * @param $publicKey
 * @return string
 */
function signPayloadPlain($payload_data, $publicKey)
{
    openssl_sign($payload_data, $signature, $publicKey, OPENSSL_ALGO_SHA1);
    return base64_encode($signature);
}

/**
 * Send a request to the given URL with the given headers and body
 * Send Signed Request to TRA
 * @version 1.0
 * @author TRA
 * @author AlphaOlomi <hello@alphalomi.com>
 * @param string $urlReceipt
 * @param array $headers, 
 * @param array $signedData
 * @return mixed
 */
function sendRequest($urlReceipt, $headers, $signedData)
{
    $curl = curl_init($urlReceipt);
    if ($headers != '') {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    } else {
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); // For Token Authentication

    }
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $signedData);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $resultEfd = curl_exec($curl);

    if ($headers == '') {
        $resultEfd = json_decode($resultEfd, true);
    }
    if (curl_errno($curl)) {
        throw new Exception(curl_error($curl));
    }
    curl_close($curl);
    return $resultEfd;
}
