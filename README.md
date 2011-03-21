A PHP wrapper for the Amazon Mechanical Turk REST API. You'll need an Amazon Web Services account to use it (you can get one from http://aws.amazon.com)

Here's an example of how to use the class.

	<?php
	require_once('MechanicalTurk.class.php');
	$mturk = new MechanicalTurk(YOUR_ACCESS_KEY_HERE, YOUR_SECRET_KEY_HERE);

	$my_question = 'my question properly formed as a QuestionForm datastructure';
	$response = $mturk->createHit($my_question);

	$another_question = 'another question formed as a QuestionForm datastructure';
	$params = array(
		'title' => 'a custom title',
		'reward' => '1.00',
		'reward_currency' => 'EUR'
	);
	$response = $mturk->createHit($another_question, $params);
	
	$hits = $mturk->getReviewableHITs();

	$account_balance = $mturk->getAccountBalance();
	?>


You can change the default settings by editing the $default variable at the top of the class. These values will be used when creating HITs if no other values are provided.

This class doesn't provide full access to all of the API, just a limited subset - enough to create, manage and delete hits.
