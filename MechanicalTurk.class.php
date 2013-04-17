<?php
/*
* PHP wrapper for Mechanical Turk's REST API
* Jack Weeden <jack@ajack.org> 2011
*/


date_default_timezone_set('Europe/Amsterdam');

class MechanicalTurk {

	private $MTURK_ROOT_URL = 'http://mechanicalturk.amazonaws.com/?';
	private $MTURK_SERVICE = 'AWSMechanicalTurkRequester';
	private $aws_access_key;
	private $aws_secret_key;

	// These values are the defaults used for a HIT creation. It's useful to use these if you're only generating one kind of HIT
	private $defaults = array(
		'title' => 			'My default title',
		'description' =>		'My default description',
		'keywords' => 			array('some', 'descriptive', 'keywords'),
		'reward' =>			'0.02',			// How much we're going to pay the worker
		'reward_currency' =>		'USD',			// The currency
		'duration' =>			180,			// How long a worker has to complete the HIT once they accept it
		'lifetime' =>			'86400',		// How long the HIT will remain on MechanicalTurk without being accepted (1 day)
		'auto_approve' =>		'86400', 		// How long after the HIT has been accepted before it is automatically approved (1 day)
		'qualification_requirement' =>	'90',			// Minimum percentage of HITs that the worker has done that have been accepted to be eligible for this HIT 
		'max_assignments' =>		'1',			// How many different workers can do the HIT
	);
	
	
	
	public function __construct($access = null, $secret = null) {
	
		if ($access === null || $secret === null) { die('Please provide your AWS Access Key and Secet Key'); }
		
		$this->aws_access_key = $access;
		$this->aws_secret_key = $secret;
		
	}
	
	/*
	* Create a HIT. Generates the entire URL required to interface wtih the Mechanical Turk REST API.
	* Retuens the XML response from the API
	* $question is your XML-based QuestionForm datastructure, see http://docs.amazonwebservices.com/AWSMechTurk/2008-08-02/AWSMturkAPI/ApiReference_QuestionFormDataStructureArticle.html
	* $parms allows you to override some or all of the defaults 
	*/	
	public function createHit($question, $params = null) {
	
		$ts = $this->Unix2UTC(time());
		$url = $this->startUrl();
		$url .= '&Operation=CreateHIT';
		$url .= '&Signature=' . $this->generateSignature($this->MTURK_SERVICE, 'CreateHIT', $ts);
		$url .= '&Timestamp=' . $ts;
		$url .= '&Title=' . (isset($params['title'])) ? urlencode($params['title']) : urlencode($this->defaults['title']);
		$url .= '&Description=' . (isset($params['description'])) ? urlencode($params['description']) : urlencode($this->defaults['description']);
		$url .= '&Reward.1.Amount=' . (isset($params['reward'])) ? urlencode($params['reward']) : $this->defaults['reward'];
		$url .= '&Reward.1.CurrencyCode=' . (isset($params['reward_currency'])) ? urlencode($params['reward_currency']) : $this->defaults['reward_currency'];
		$url .= '&Question=' . ($question != null) ? $question : urlencode($defaults['question']);
		$url .= '&AssignmentDurationInSeconds=' . $this->defaults['duration'];
		$url .= '&AutoApprovalDelayInSeconds=' . $this->defaults['auto_approve'];
		$url .= (isset($params['qualification_requirement'])) ? $this->generateQualificationRequirement($params['qualification_requirement']) : $this->generateQualificationRequirement($this->defaults['qualification_requirement']);
		$url .= '&LifetimeInSeconds=' . (isset($params['lifetime'])) ? urlencode($params['lifetime']) : $this->defaults['lifetime'];
		$url .= '&Keywords=' . (isset($params['keywords'])) ? urlencode(implode(', ', $params['keywords'])) : urlencode(implode(', ', $this->defaults['keywords']));
		if (isset($params['requester_annotation'])) {
			$url .= '&RequesterAnnotation=' . $params['requester_annotation'];
		} else if (isset($defaults['requester_annotation'])) {
			$url .= '&RequesterAnnotation=' . $defaults['requester_annotation'];
		}

		$response = file_get_contents($url);
		return $response;
	}
	

	/*
	* Gets a set of your HITs that have been submitted by workers
	* Returns an array of HIT IDs
	* See http://docs.amazonwebservices.com/AWSMechTurk/2008-08-02/AWSMturkAPI/ApiReference_GetReviewableHITsOperation.html
	*/	
	public function getReviewableHITs($page_size = 50) {
		$ts = $this->Unix2UTC(time());
		
		$url = $this->startUrl();
		$url .= '&Operation=GetReviewableHITs';
		$url .= '&Signature=' . $this->generateSignature($this->MTURK_SERVICE, 'GetReviewableHITs', $ts);
		$url .= '&Timestamp=' . $ts;
		$url .= '&PageSize=' . $page_size;
		
		$ret = array();	// Array of HITs to return
		
		$xml = simplexml_load_string(file_get_contents($url));	// Get the XML
		$hits = $xml->xpath('/GetReviewableHITsResponse/GetReviewableHITsResult/HIT');	// Use XPATH to traverse to the actual HITs
		
		foreach ($hits as $hit) {
			$ret[] = (string)$hit->HITId[0];
		}
		
		return $ret;
	}

	/*
	* Gets the results for a given HIT
	* See http://docs.amazonwebservices.com/AWSMechTurk/2008-08-02/AWSMturkAPI/ApiReference_GetAssignmentsForHITOperation.html
	*/	
	public function getHITResult($hid, $page_size = 50) {
		$ts = $this->Unix2UTC(time());
		
		$url = $this->startUrl();
		$url .= '&Operation=GetAssignmentsForHIT';
		$url .= '&Signature=' . $this->generateSignature($this->MTURK_SERVICE, 'GetAssignmentsForHIT', $ts);
		$url .= '&Timestamp=' . $ts;
		$url .= '&HITId=' . $hid;
		$url .= '&PageSize=' . $page_size;
		
		return file_get_contents($url);
	}

	/*
	* Gets all the information about a HIT
	* See http://docs.amazonwebservices.com/AWSMechTurk/2008-08-02/AWSMturkAPI/ApiReference_GetHITOperation.html
	*/	
	public function getHITDetails($hid) {
		$ts = $this->Unix2UTC(time());
		
		$url = $this->startUrl();
		$url .= '&Operation=GetHIT';
		$url .= '&Signature=' . $this->generateSignature($this->MTURK_SERVICE, 'GetHIT', $ts);
		$url .= '&Timestamp=' . $ts;
		$url .= '&HITId=' . $hid;
		
		return file_get_contents($url);
	}

	/*
	* Approves a HIT. This will pay the worker who carried out the HIT and pay AWS their overhead fee
	* See http://docs.amazonwebservices.com/AWSMechTurk/2008-08-02/AWSMturkAPI/ApiReference_ApproveAssignmentOperation.html
	*/	
	public function approveHIT($hit_id) {
		// First we need to get the assignment ID that corresponds to the HIT
		$assignment_id = $this->getAssignmentID($hit_id);
		
		$ts = $this->Unix2UTC(time());
		
		$url = $this->startUrl();
		$url .= '&Operation=ApproveAssignment';
		$url .= '&Signature=' . $this->generateSignature($this->MTURK_SERVICE, 'ApproveAssignment', $ts);
		$url .= '&Timestamp=' . $ts;
		$url .= '&AssignmentId=' . $assignment_id;
		
		return file_get_contents($url);
	}
	
	/*
	* When a worker accepts a HIT, it's condiered 'assigned' and so has an assignment ID, this returns it
	* See http://docs.amazonwebservices.com/AWSMechTurk/2008-08-02/AWSMturkAPI/ApiReference_GetAssignmentsForHITOperation.html
	*/
	public function getAssignmentID($hit_id) {
		$ts = $this->Unix2UTC(time());
		
		$url = $this->startUrl();
		$url .= '&Operation=GetAssignmentsForHIT';
		$url .= '&Signature=' . $this->generateSignature($this->MTURK_SERVICE, 'GetAssignmentsForHIT', $ts);
		$url .= '&Timestamp=' . $ts;
		$url .= '&HITId=' . $hit_id;
		
		$xml = simplexml_load_string(file_get_contents($url));
		$assignment_id = $xml->GetAssignmentsForHITResult->Assignment->AssignmentId;
		
		return $assignment_id;
	}

	/*
	* Removes a HIT from Mechanial Turk
	* NOTE: You can dispose only HITs in the Reviewable state, with all submitted assignments approved or rejected.
	* See http://docs.amazonwebservices.com/AWSMechTurk/2008-08-02/AWSMturkAPI/ApiReference_DisposeHITOperation.html
	*/	
	public function removeHIT($hid) {
		$ts = $this->Unix2UTC(time());
		
		$url = $this->startUrl();
		$url .= '&Operation=DisposeHIT';
		$url .= '&Signature=' . $this->generateSignature($this->MTURK_SERVICE, 'DisposeHIT', $ts);
		$url .= '&Timestamp=' . $ts;
		$url .= '&HITId=' . $hid;
		
		return file_get_contents($url);
	}
	
	/*
	* Returns the current AWS Mechanical Turk account balance
	* Passing 'true' returns a nicely formatted amount (e.g. $1.50), otherwise the value only is returned
	* See http://docs.amazonwebservices.com/AWSMechTurk/2008-08-02/AWSMturkAPI/ApiReference_GetAccountBalanceOperation.html
	*/	
	public function getAccountBalance($nice = true) {
		$ts = $this->Unix2UTC(time());
		
		$url = $this->startUrl();
		$url .= '&Operation=GetAccountBalance';
		$url .= '&Signature=' . $this->generateSignature($this->MTURK_SERVICE, 'GetAccountBalance', $ts);
		$url .= '&Timestamp=' . $ts;
		
		$xml = simplexml_load_string(file_get_contents($url));
		
		if ($nice) {
			return $xml->GetAccountBalanceResult->AvailableBalance->FormattedPrice;
		} else {
			return $xml->GetAccountBalanceResult->AvailableBalance->Amount;
		}
	}
	
	/*
	* Utility method to return the first part of the Mechanical Turk API URL (which is always the same)
	*/
	private function startUrl() {
		return $this->MTURK_ROOT_URL . 'AWSAccessKeyId=' . $this->aws_access_key;
	}

	/*
	* Utility method to generate the part of the URL that requires a certain approval rate
	* See http://docs.amazonwebservices.com/AWSMturkAPI/2008-08-02/ApiReference_QualificationRequirementDataStructureArticle.html
	*/
        private function generateQualificationRequirement($qual) {
                return  '&QualificationRequirement.1.QualificationTypeId=000000000000000000L0'.
                                '&QualificationRequirement.1.Comparator=GreaterThan'.
                                '&QualificationRequirement.1.IntegerValue=' . $qual;
        }

	
	/*
	* Generates the signature AWS needs for authenticating requests
	* See http://docs.amazonwebservices.com/AWSMechTurk/2008-08-02/AWSMechanicalTurkRequester/
	*/
	private function generateSignature($service, $operation, $timestamp) {
		// Generate the signed HMAC signature AWS APIs require
		$hmac = $this->hasher($service.$operation.$timestamp);
		$hmac_b64 = $this->base64($hmac);
		return urlencode($hmac_b64);
	}
	
	/*
	* Returns the HMAC for generating the signature
	* Algorithm adapted (stolen) from http://pear.php.net/package/Crypt_HMAC/ (via http://code.google.com/p/php-aws/)
	*/
	private function hasher($data) {
		$key = $this->aws_secret_key;
		if(strlen($key) > 64)
			$key = pack('H40', sha1($key));
		if(strlen($key) < 64)
			$key = str_pad($key, 64, chr(0));
		$ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
		$opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));
		return sha1($opad . pack('H40', sha1($ipad . $data)));
	}
       
	 
	private function base64($str) {
		$ret = '';
		for($i = 0; $i < strlen($str); $i += 2)
			$ret .= chr(hexdec(substr($str, $i, 2)));
		return base64_encode($ret);
	}

	/* 
	* Takes a UNIX timestamp and returns a timestamp in UTC	
	*/
	private function Unix2UTC($unix) {
		return date('Y-m-d\TH:i:s', $unix) . 'Z';
	}
} ?>
