<?php

// xmlupload_minimal.php5 -- absolutely minimal PHP script to
// 		receive and valid XML and reply to AcquiSuite.
// 	by Mark N. Shepard
// 	$Id: xmlupload_minimal.php,v 1.2 2011/08/02 17:50:31 mns Exp $

// Uncomment to disable uploads if server is down for maintanence.
//header("Status: 503 Server Busy");
//exit(0);

$contentLength = intval($_SERVER['CONTENT_LENGTH']);
$body = file_get_contents('php://input');

// Critical: Verify we received the ENTIRE post of data from the AcquiSuite
// by comparing the actual length of $body against the Content-Length:
// header. If the message from the AcquiSuite was truncated, fail.

if ($contentLength <= 0 || strlen($body) < $contentLength)
{	header("Status: 400 Bad Request");
	exit(0);
}

// Parse the XML to verify it hasn't been truncated; assumes that PHP will
// throw an exception and bomb out if the XML is syntactically invalid.
//
// For a good intro to XML parsing in PHP, see:
//   "XML for PHP developers, Part 2: Advanced XML parsing techniques"
//   IBM developerWorks
//   <http://www.ibm.com/developerworks/xml/library/x-xmlphp2/index.html>

$dasMsg = new SimpleXMLElement($body);		// throws exception!

// TODO:  insert code to do stuff w/ $dasMsg
// TODO:  see http://us.php.net/simplexmlelement for examples.

header("Status: 200 OK");
header("Pragma: no-cache");
header("Content-Type: text/xml");

// Send 'SUCCESS' response to AcquiSuite; this will allow AcquiSuite
// to delete data on its end. Only do this if you are ABSOLUTELY SURE
// the XML you received is valid and you have properly
// stored or processed it!

printf("<?xml version=\"1.0\"?>\n"); 		// XML response to AcquiSuite.
printf("<DAS>\n");
printf("<result>SUCCESS</result>\n");
printf("</DAS>\n");

?>
