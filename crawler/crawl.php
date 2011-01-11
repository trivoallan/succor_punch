<?php
// @see https://github.com/fabpot/Goutte

require_once(__DIR__.'/vendors/goutte/goutte.phar');

use Goutte\Client;
$client = new Client();

$storiesRangeStart = 1;
$storiesRangeEnd = 1000;
$i = $storiesRangeStart;

// Grab stories
while ($i <= $storiesRangeEnd)
{
	// Grab story
	$crawler = $client->request('GET', sprintf('http://www.histoires-de-sexe.net/sexe.php?histoire=%d', $i));

	// Extract interesting part of page
	$storyNodes = $crawler->filter('.t0')->extract('_text', 'div');

        if (isset($storyNodes[2])) {
		// Cleanup
		$story = trim(strip_tags($storyNodes[2]));
		$storyLines = explode("\n", $story);
		$storyLines = array_map('trim', $storyLines);

		// Extract story title
		$storyTitle = $storyLines[1];

		// Extract story text
		$blockSentence = 'Vous êtes :';
		$storyLines = array_slice($storyLines, 2, array_search($blockSentence, $storyLines));
		$storyText = implode("\n", $storyLines);

		logMessage(sprintf('Extracted story %d : "%s" (strlen: %d)', $i, $storyTitle, strlen($storyText)));
	} else {
		logMessage(sprintf('No story text for story %d, skipping.', $i));
	}
	$i++;
}

function logMessage($message)
{
	echo sprintf("[%s] %s\n", date('r'), $message);
}
