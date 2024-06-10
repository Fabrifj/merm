<?php

// xmlupload_fancy.php5 -- demonstration of receiving XML uploads from AcquiSuite.
//   by Mark N. Shepard
//   $Id: xmlupload_fancy.php,v 1.7 2011/08/03 13:24:38 mns Exp $
//
// ======================================================================================
// ABOUT THIS SCRIPT:
//
// This script requires PHP version 5 or later.
//
// This script will receive XML uploads from one or more AcquiSuites. Each XML message
// will be stored in a separate file in the server's $UPLOADDIR (defined below as /tmp).
//
// These files will be named /tmp/das_xmlupload_xxxx.xml (where xxxx is a random string of
// letters). This script will also validate and parse the XML using PHP's builtin functions.
//
// This script will also parse some of the XML messages from the AcquiSuites (the 'STATUS'
// messages), and will create a /tmp/das_status_SNSNSNSNSN.txt file containing
// bits of information extracted from the XML message.  This isn't especially useful, it is
// simply a demonstration of how to parse the XML.
//
// Finally, this script will send a proper XML reply back to the AcquiSuite.
//
// This script can be modified to perform more on-the-fly parsing of the XML, or it can
// be used as-is to upload XML messages to a directory for batch-processing by another task.
// ======================================================================================

define('PREFIX', "das_");		// prefix for files created by this script: das_xmlupload, das_status
define('UPLOADDIR', "/tmp");	// uploaded XML blobs are stored in files here.
define('chaosMonkeyMode', 0);	// adds random whitespace to our responses to AcquiSuite

// ======================================================================================
// OVERVIEW OF THE 'AcquiSuite-XML' PROTOCOL:
//
// The 'AcquiSuite-XML' protocol is intended to be simple above all else.
//
// In this protocol, the AcquiSuite acts has an HTTP client.  The AcquiSuite is
// configured to use the 'AcquiSuite-XML' protocol by enabling an upload channel, specifying
// a Upload URL and an optional password. During each upload cycle, the AcquiSuite will
// make one or more HTTP requests to the specified Upload URL. Each HTTP transaction will
// be a POST request, of Content-Type: text/xml, and the body of the HTTP POST will contain
// an XML message, the format of which is defined below.
//
// All HTTP requests and replies are in plain, uncompressed text.
//
// Only the HTTP Basic Authentication scheme is supported.
//
// The AcquiSuite sends two types of XML messages, which we call "STATUS" and "LOGFILEUPLOAD".
//
// In each of these messages, the particular AcquiSuite making the request identifies itself
// by including its serial number (which happens to be its Ethernet MAC address) as part of
// the XML message.
//
// All requests in this protocol are 'stateless' and 'idempotent':  The AcquiSuite does not
// use any mechanism such as Cookies or session-ID's to keep track of session state.
// 
// The "STATUS" XML message provides the server with overall health and status of the
// AcquiSuite, such as its uptime, the % of free disk space, the firmware version it is
// running, etc.
//
//-------------------- EXAMPLE OF AcquiSuite 'STATUS' XML MESSAGE --------------------
// <DAS>
//   <mode>STATUS</mode>
//   <name>A8812 Dallas CNT:US CIT:DFW &amp; BLD:Mark1 &lt;mark@shepard.org&gt;</name>
//   <serial>0050C230EF52</serial>
//   <uptime>145200</uptime>
//   <percentblocksinuse>58</percentblocksinuse>
//   <percentinodesused>0</percentinodesused>
//   <acquisuiteversion>v02.11.0718b</acquisuiteversion>
//   <usrversion>v02.11.0714b</usrversion>
//   <rootversion>v02.11.0714b</rootversion>
//   <kernelversion>2.6.28.10-r1.92b-ge055112</kernelversion>
// </DAS>
//------------------------------------------------------------------------------------
//
// The "LOGFILEUPLOAD" XML message delivers the actual meter data to the server.
// Each "LOGFILEUPLOAD" XML message contains one or more <device> fields, which identify
// the particular meter, sensor or other data-reporting device for which readings are being
// uploaded, and for each device, the LOGFILEUPLOAD message will contain one or more
// <record> fields, each of which will contain the values of all data-points, sampled
// from the device at a particular instant in time.
//
// A device is identified in two ways:
//
// - by an <address> field (shown below) containing the Modbus Address of the meter or sensor.
//   The <address> value may be from 0 to 255. Note that Modbus Addresses can and do change.
//   These are typically set on DIP switches or in software when the meter is installed.
//
// - by a <serialnumber> field (not shown, but at the same level as the <address>),
//   which contains the unique serial number of the meter or sensor (do not confuse this with
//   the AcquiSuite's serial number). Serial numbers do not change; they are set at the
//   factory when the meter or sensor is manufactured.
//   
//   IMPORTANT:
//   ------------------------------------------------------------------------------
//   | The device serial number can be up to 128 characters and should be treated |
//   | case-sensitively.                                                          |
//   ------------------------------------------------------------------------------
//   | Some devices have ONLY a modbus address (no serial number). Others have    |
//   | ONLY a serial number (no modbus address). Some devices have both.          |
//   ------------------------------------------------------------------------------
//   | If a device has BOTH a modbus address and a serial number, the server      |
//   | should use the modbus address to identify the device; this is what the     |
//   | AcquiSuite will do.  In this case, the serial number can either be ignored |
//   | (for simplicity) or it can be stored. If the modbus address remains the    |
//   | same but a serial number changes, this usually means the meter was replaced|
//   | with a different hardware unit.                                            |
//   ------------------------------------------------------------------------------
//
// Each <record> contains:
//
// - a <time> field, which is the absolute time (in UTC) when the sample was collected
//   by the AcquiSuite; 
//
// - a <statuscode> field, which is the Modbus Error Code returned by the device
//   (or 0 for success).
//
// - a <status> field, which decodes the <statuscode> value into a human-readable message.
//
// - one or more <point ...> fields which provide the value of each data point,
//   along with its name and units.
//
//----------------- EXAMPLE OF AcquiSuite 'LOGFILEUPLOAD' XML MESSAGE ----------------
// <DAS>
//   <mode>LOGFILEUPLOAD</mode>
//   <name>A8812 Dallas CNT:US CIT:DFW &amp; EML:Mark1 &lt;mark@shepard.org&gt;</name>
//   <serial>0050C230EF52</serial>
//   <devices>
//     <device>
//       <name>DEV:Veris H8238 MTR8</name>
//       <address>72</address>
// 	     <type>Veris Multi-Circuit Power Meter 8, H8238, PMM1, MTR8</type>
// 	     <class>7</class>
// 	     <numpoints>28</numpoints>
// 	     <records>
// 	       <record>
// 		     <time zone="UTC">2011-07-18 00:38:24</time>
// 		     <error text="Ok">0</error>
// 		     <point number="0" name="Energy Consumption" units="kWh" value="0" />
// 		     <point number="1" name="Real Power" units="kW" value="0" />
// 		     <point number="2" name="Reactive Power" units="kVAR" value="0" />
// 		     ...
// 		     <point number="27" name="Maximum Demand" units="kW" value="0" />
// 		   </record>
// 		   <record>
// 		     ...
// 		   </record>
// 		   <record>
// 		     ...
// 		   </record>
// 	     </records>
// 	   </device>
// 	 </devices>
// </DAS>
//------------------------------------------------------------------------------------
//
// The AcquiSuite expects to receive an XML message in reply to each of its POST's.
// This XML reply has the same format for both the STATUS and LOGFILEUPLOAD messages.
//
// --------------------------- REPLY INDICATING SUCCESS -----------------------------
// <DAS>
//   <result>SUCCCESS</result>
// </DAS>
// --------------------------- REPLY INDICATING FAILURE -----------------------------
// <DAS>
//   <result>FAILURE: one-line error message without HTML tags</result>
// </DAS>
// ----------------------------------------------------------------------------------
// 
// 'SUCCESS' tells the AcquiSuite that its message was received and processed correctly.
// In the case of a LOGFILEUPLOAD message, this tells the AcquiSuite that the server has
// stored the meter's data and the AcquiSuite can now delete the data from its flash-memory.
//
// 'FAILURE' is typically returned by the server if the server is unable to process the
// request for some reason such: out of disk space, database down for maintenance,
// AcquiSuite's serial number is not recognized, password is invalid, etc.
//
// A one-line error message may optionally be included with the 'FAILURE' keyword. This
// is displayed by the AcquiSuite in its Connection Test and also in its upload logs.
//
// The XML reply (whether a 'SUCCESS' or 'FAILURE') may also contain an optional
// set of <notes>, like so:
// ------------------------ EXAMPLE SHOWING OPTIONAL <notes> ------------------------
// <DAS>
//   <result>...</result>
//   <notes>
//     <note> connecting to database server: foobar </note>
//     <note> creating table xyz for serial-number 123456789ABCDEF </note>
//     <note> storing data... </note>
//     <note> data stored successfully </note>
//     ...
//   </notes>
// </DAS>
// ----------------------------------------------------------------------------------
//
// These <notes> are not parsed by the AcquiSuite -- they are included in the AcquiSuite's
// upload log (but only if the AcquiSuite's logging level is set to
// 'Full Debug with Protocol Trace'.  The intent of the <notes> field is to provide a
// free-form area where the server can output debugging or diagnostic information to
// to help the AcquiSuite's installer or user debug problems.
//
// When parsing the server's reply, the AcquiSuite also interprets the HTTP status code.
//
// The AcquiSuite tries to use a "common-sense" interpretation of the HTTP status code to
// make it easy to integrate into existing web frameworks.
//
// In cases of 'FAILURE' the server can influence exactly how the AcquiSuite responds to
// the 'FAILURE' by the particular HTTP status code the server returns.
//
// For instance, normally, the server would reply to the AcquiSuite with an XML message
// (as defined above) and with HTTP status code "200 OK", regardless of whether the reply
// was a 'SUCCESS' or a 'FAILURE'.  By default, the AcquiSuite treats all FAILURE's as
// transient communcations problems, and so will retry any FAILURE several times during
// a single upload session (the number of retry attempts can be configured on the AcquiSuite).
//
// Some FAILURE's -- such as a bad password failure or invalid URL -- cannot be fixed by
// retrying immediately, and retrying simply wastes server CPU time and network bandwidth.
// If server replies with any of the HTTP status codes below, the AcquiSuite will
// abort the entire upload session:
//
// 	HTTP 401 Unauthorized
// 	HTTP 402 Payment Required
// 	HTTP 403 Forbidden
// 	HTTP 404 Not Found
// 	HTTP 407 Proxy Auth Required
//
// The server can also return one of the above HTTP errors if the server is down for
// maintenance and wishes to minimize the bandwidth while it is offline.
//
// THE END
// ======================================================================================

// -------------------------------------------------------------------------------
// BEGIN OBVIUS QATEST CODE
// The following code contributes nothing to this demo; it defines a function for outputting
// random runs of whitespace, which is used for add some chaos in our replies to the AcquiSuite
// for testing/QA. Of course in a production system you would *not* want to do this as it just
// wastes bandwidth!
srand();
define('CMM_BASE',rand(0,5));
function sp()
{	if (chaosMonkeyMode)
	{	$i = CMM_BASE * rand(0,6);
		while ($i-- > 0)
			switch (rand(1,3))					// space 2x as common as newline
			{	case 1:  printf("\n"); break;	// newline
				default: printf(" "); break;	// space
			}
	}
	else
	{	printf("\n");
	}
}
// END OBVIUS QATEST CODE

// -------------------------------------------------------------------------------
// This function shows how to send a reply back to the AcquiSuite.
//
// BTW, "DAS" = data acquisition system, i.e., the AcquiSuite
//
// This function begins the reply;  dasEndReplyAndExit() should be called to finish it.
// Between these two functions, you can also output <note>'s which are not parsed by
// the AcquiSuite -- they simply go into the AcquiSuite's logfile as a debugging aid
// (and only if the AcquiSuite is set to "Full Debug with Protocol Trace").
//
// 'dasStatus' should be either "SUCCESS" or "FAILURE" as defined above in the OVERVIEW.
//
// 'httpStatus' would normally be "200 OK", but other HTTP status codes may be returned to
// tweak the AcquiSuite's behavior.
//
function dasBeginReply($httpStatus, $dasStatus)
{	
	// All replies to the AcquiSuite must be in the form of an XML message as defined
	// below and must be of Content-Type: text/xml.
	//
	// The XML reply to the AcquiSuite should look like this:
	// --------------------------- REPLY INDICATING SUCCESS -----------------------------
	// <DAS>
	//   <result>SUCCCESS</result>
	//   <notes>
	//     <note> blah1 </note>
	//     ...
	//   </notes>
	// </DAS>
	// --------------------------- REPLY INDICATING FAILURE -----------------------------
	// <DAS>
	//   <result>FAILURE: one-line error message without HTML tags</result>
	//   <notes>
	//     <note> blah1 </note>
	//     ...
	//   </notes>
	// </DAS>
	// ----------------------------------------------------------------------------------

	header("Status: $httpStatus");
	header("Pragma: no-cache");	
	header("Content-Type: text/xml");

	// Note: PHP automatically inserts a blank line after HTTP headers, so we must not
	// as (technically) the "<?xml" token is required to be at the start of the body.

	printf("<?xml version=\"1.0\"?>\n");	// XML response to AcquiSuite.
	sp(); printf("<DAS>");
	sp(); printf("<result>");
	sp(); printf("%s",strip_tags($dasStatus));	// required: must be SUCCESS or FAILURE...
	sp(); printf("</result>");
	sp();
}
// -------------------------------------------------------------------------------
// This function finishes a reply to the AcquiSuite. Call it after dasBeginReply().
//
function dasEndReplyAndExit()
{	sp(); printf("</DAS>");
	sp();
	exit(0);
}
// -------------------------------------------------------------------------------
// This function sends a reply to the AcquiSuite and exits, using the above two functions
// together.
//
function dasReplyAndExit($httpStatus,$dasStatus)
{	dasBeginReply($httpStatus,$dasStatus);
	dasEndReplyAndExit();
}
// -------------------------------------------------------------------------------
// This function is called to handle errors in this script.
//
// If we catch a PHP exception, rather than simply crashing, we send back a proper
// HTTP error code and FAILURE message to the AcquiSuite.  In a production system,
// you'd probably want to make a log entry or notify a sysadmin, too.
//
// Note that PHP lets you prevent a function from throwing exceptions by prefixing the
// function name with '@'.
//
function onServerError($errno, $errstr, $errfile, $errline)
{
	// Below, we check if the PHP exception we've trapped is related to memory
	// allocation.  The SimpleXMLElement function must allocate memory for the ENTIRE
	// XML message, and if our server has very limited memory (such as a shared ISP account or
	// virtual hosting arrangement) and the XML happens to be large, SimpleXMLElement can
	// run out of memory and fail. This will typically happen if the AcquiSuite is set
	// to upload once per day, or it is logging from a meter such as the Veris E30/E31
	// Branch Current Monitors with 420 points on a single device!
	//
	// The proper solution to this problem is parse the XML w/o a better algorithm with
	// _constant_, O(1) memory usage instead of O(n).
	//
	// See IBM's developerWorks "XML for PHP developers, Part 2: Advanced XML parsing techniques"
	// at <http://www.ibm.com/developerworks/xml/library/x-xmlphp2/index.html> for a good overview
	// of alternate XML parsers.
	//
	// For demonstration purposes, however, we catch these memory allocation failures, and
	// send the AcquiSuite a HTTP 406 status code. This tells the AcquiSuite there is a problem
	// with the current XML message but rather than aborting the upload session, we skip over THIS
	// ONE BLOCK of problem-data and continue with subsequent data for this meter. This is still
	// considered an error; the problem data remains in the AcquiSuite's flash-memory and will be
	// tried again later. The benefit of this is that we continue uploading OTHER data from the same
	// device and from other devices, rather than letting one problem block create a
	// "log-jam" :-)

	if (stristr($errstr,"memory"))
		$httpStatus = "406 Not Acceptable";
	else
		$httpStatus = "500 Server Error";

	dasReplyAndExit(
		$httpStatus,
		"FAILURE: Server error $errno at line $errline of $errfile: $errstr");
}

// Here we establish our error handler. PHP calls this function when something in this
// script fails.
//
set_error_handler("onServerError");

// -------------------------------------------------------------------------------
// Here we begin the main portion of this program.
//
// We get the XML message sent from the AcquiSuite, parse it, decide what to do, and
// send a reply back to the AcquiSuite.
//

$contentLength = intval($_SERVER['CONTENT_LENGTH']);

$body = file_get_contents('php://input');

// Verify request method is POST.

if ($_SERVER['REQUEST_METHOD'] != 'POST')
{	dasReplyAndExit("405 Method Not Allowed", "FAILURE: Only POST is allowed");
}

// Critical: Verify we received the ENTIRE post of data from the AcquiSuite
// by comparing the actual length of $body against the Content-Length:
// header. If the message from the AcquiSuite was truncated, fail.

if ($contentLength <= 0 || strlen($body) < $contentLength)
{	dasReplyAndExit(
		"400 Bad Request",
		"FAILURE: Content-Length header missing or Request body was truncated, content-length=$contentLength, body=" . strlen($body) . ".");
}

// To make debugging easier, we first save a copy of _every_ XML message in a unique
// filename in the server's /tmp directory.
//
// Use the Unix command:
//   'ls -ltra /tmp/das_xmlupload_*'
// to list the requests in chronological order.

if (FALSE === ($tmpFile = tempnam(UPLOADDIR, PREFIX . "xmlupload_")))	// Example: /tmp/das_xmlupload_xudjfke473j
{	dasReplyAndExit("503 Service Unavailable", "FAILURE: tempnam() failed");
}
if (FALSE === (file_put_contents($tmpFile, $body)))
{	dasReplyAndExit("503 Service Unavailable", "FAILURE: file_put_contents($tmpFile,...) failed");
}
chmod($tmpFile, 0644);							// open up permissions (default are 0600)

// Next, do some sanity checking on the XML message.

if ($body == NULL || $body == '')
	dasReplyAndExit(
		"400 Bad Request",
		"FAILURE: Body of HTTP POST Request is empty, expected XML payload.");

// Parse the XML.  SimpleXMLElement requires PHP5. See <http://us.php.net/simpleXMLElement>
//
// Note: in this example, if the XML is syntactically-invalid, PHP will throw an error,
// and our onServerError function will reply to the AcquiSuite with an HTTP 500 Internal
// Error msg. Strictly speaking we should actually reply with a 4xx code because the
// problem is with the AcquiSuite's request, not with the server.

$dasMsg = new SimpleXMLElement($body);

// Figure out what kind of XML message we received from the AcquiSuite (the 'das').

if ($dasMsg->mode == 'STATUS')
{	// A STATUS message is sent by AcquiSuite to report its health.
	// This is sent ONCE at the start of each upload cycle.
	//
	// We only need the most recent STATUS report, so we'll save this in a filename
	// based on the AS' serial number. We also demonstrate how to parse the XML and
	// convert it to a nice text file, though generally the server would put the info
	// directly into a database.
	//
	// Example filename:  /tmp/das_status_0050C230EF52.txt
	//
	// The XML of the STATUS message will look like this -- whitespace added for readability:
	//
	//------------------------------------------------------------------------------------
	// <DAS>
	//   <mode> STATUS </mode>
	//   <name> A8812 Dallas CNT:US CIT:DFW &amp; BLD:Mark1 &lt;mark@shepard.org&gt; </name>
	//   <serial> 0050C230EF52 </serial>
	//   <uptime> 145200 </uptime>
	//   <percentblocksinuse> 58 </percentblocksinuse>
	//   <percentinodesused> 0 </percentinodesused>
	//   <acquisuiteversion> v02.11.0718b </acquisuiteversion>
	//   <usrversion> v02.11.0714b </usrversion>
	//   <rootversion> v02.11.0714b </rootversion>
	//   <kernelversion> 2.6.28.10-r1.92b-ge055112 </kernelversion>
	// </DAS>
	//------------------------------------------------------------------------------------
	//
	$statusFile = UPLOADDIR . "/" . PREFIX . "status_" . $dasMsg->serial . ".txt";
	file_put_contents($statusFile,
		  "AcquiSuite Serial Number:   " . $dasMsg->serial . "\n"
		. "AcquiSuite Name:            " . $dasMsg->name   . "\n"
		. "AcquiSuite Version:         " . $dasMsg->acquisuiteversion. "\n"
		. "AcquiSuite Uptime:          " . $dasMsg->uptime . "\n"
		. "AcquiSuite % Blocks In Use: " . $dasMsg->percentblocksinuse . "\n"
		. "Raw XML is stored in:       " . $tmpFile . "\n"
		);
}
else if ($dasMsg->mode == 'LOGFILEUPLOAD')
{
	// A LOGFILEUPLOAD message is sent containing one or more records of meter data.
	// Each <record> has a timestamp, and contains a sample of all data points of the meter
	// taken at that instant.
	//
	// In this demonstration script, we don't actually _do_ anything with the data.
	// We simply leave it in the /tmp directory.
	//
	// In general, you must either insert the data into your database NOW, or you must
	// store the data in a file, reply to the AcquiSuite and do additional processing
	// on the data later (i.e., as a batch or background job).
	//
	//------------------------------------------------------------------------------------
	// The XML of the LOGFILEUPLOAD message will look like this -- this example shows
	// exactly 1 <record>, but generally there would be more than one.
	//
	// A "device" means a meter, sensor or other data source.
	//
	// A device is identified in two mutually exclusive ways, depending on the device:
	// 1) by Modbus Address Number (1..254)
	// 2) by Device Serial Number (not to be confused with the AcquiSuite serial number).
	//
	// If a Modbus Address is being used, this will be provided in the
	//   <DAS><devices><device><address> field.
	//
	// If a Device Serial Number is being used, this will be provided in the
	//   <DAS><devices><device><serialnumber> field (not shown).
	//
	// Each <record> represents a point in time when all the data points of the meter
	// were sampled.
	//
	// The ...<record><statuscode> field gives the modbus status code for the sample.
	// For example:
	//     0 indicates a successful sample,
	//   128 indicates "error out of range",
	//   129 indicates "illegal function",
	//   130 indicates "illegal data address",
	//   131 indicates "illegal data value",
	//   ...
	//   138 indicates a problem with the modbus gateway,
	//   139 indicates the device was offline when queried,
	//   ...
	//
	//------------------------------------------------------------------------------------
	// <DAS>
	//   <mode>LOGFILEUPLOAD</mode>
	//   <name>A8812 Dallas CNT:US CIT:DFW &amp; EML:Mark1 &lt;mark@shepard.org&gt;</name>
	//   <serial>0050C230EF52</serial>
	//   <devices>
	//     <device>
	//       <name>DEV:Veris H8238 MTR8</name>
	//       <address>72</address>
	// 	     <type>Veris Multi-Circuit Power Meter 8, H8238, PMM1, MTR8</type>
	// 	     <class>7</class>
	// 	     <numpoints>28</numpoints>
	// 	     <records>
	// 	       <record>
	// 		     <time zone="UTC">2011-07-18 00:38:24</time>
	// 		     <error text="Ok">0</error>
	// 		     <point number="0" name="Energy Consumption" units="kWh" value="0" />
	// 		     <point number="1" name="Real Power" units="kW" value="0" />
	// 		     <point number="2" name="Reactive Power" units="kVAR" value="0" />
	// 		     <point number="3" name="Apparent Power" units="kVA" value="0" />
	// 		     <point number="4" name="Power Factor" units="" value="0" />
	// 		     <point number="5" name="Voltage, Line to Line" units="Volts" value="NULL" />
	// 		     <point number="6" name="Voltage, Line to Neutral" units="Volts" value="NULL" />
	// 		     <point number="7" name="Current" units="Amps" value="NULL" />
	// 		     <point number="8" name="Frequency" units="Hz" value="60.000" />
	// 		     <point number="9" name="Real Power phase A" units="kW" value="0" />
	// 		     <point number="10" name="Real Power phase B" units="kW" value="0" />
	// 		     <point number="11" name="Real Power phase C" units="kW" value="NULL" />
	// 		     <point number="12" name="Power Factor phase A" units="" value="0" />
	// 		     <point number="13" name="Power Factor phase B" units="" value="0" />
	// 		     <point number="14" name="Power Factor phase C" units="" value="NULL" />
	// 		     <point number="15" name="Voltage phase A-B" units="Volts" value="0" />
	// 		     <point number="16" name="Voltage phase B-C" units="Volts" value="NULL" />
	// 		     <point number="17" name="Voltage phase C-A" units="Volts" value="NULL" />
	// 		     <point number="18" name="Voltage phase A-N" units="Volts" value="121.1" />
	// 		     <point number="19" name="Voltage phase B-N" units="Volts" value="120.0" />
	// 		     <point number="20" name="Voltage phase C-N" units="Volts" value="NULL" />
	// 		     <point number="21" name="Current phase A" units="Amps" value="0" />
	// 		     <point number="22" name="Current phase B" units="Amps" value="0" />
	// 		     <point number="23" name="Current phase C" units="Amps" value="NULL" />
	// 		     <point number="24" name="Current Neutral" units="Amps" value="NULL" />
	// 		     <point number="25" name="Average Demand" units="kW" value="0" />
	// 		     <point number="26" name="Minimum Demand" units="kW" value="0" />
	// 		     <point number="27" name="Maximum Demand" units="kW" value="0" />
	// 		   </record>
	// 	     </records>
	// 	   </device>
	// 	 </devices>
	// </DAS>
	//------------------------------------------------------------------------------------
}
else 
{	// Hmm. Garbled XML.
	dasReplyAndExit(
		"400 Bad Request",
		"FAILURE: Mode [$dasMsg->mode] is not recognized");
}

// -------------------------------------------------------------------------------
// If we reach this point, we successfully handled the request. Give success reply.
//
// 'SUCCESS' tells the AcquiSuite it can delete its copy of the data it just uploaded,
// so be absolutely certain you have properly saved the data.

dasBeginReply("200 OK", "SUCCESS");
// We also send back some <notes> for debugging. These are visible in the 
// "Last Data Upload" log _if_ the Upload Debug Information level is set to
// "Full Debug with Trace".
printf("<notes>\n");
  printf("<note>Server filename: [%s]</note>\n", $tmpFile);	// file we just created
  printf("<note>Upload CWD: [%s]</note>\n", getcwd());		// our current dir
  printf("<note>Upload PID: [%d]</note>\n", getmypid());	// our process ID
printf("</notes>\n");
dasEndReplyAndExit();

?>
