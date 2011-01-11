<?php
// @see https://github.com/fabpot/Goutte

require_once(__DIR__.'/vendors/goutte/goutte.phar');

use Goutte\Client;
$client = new Client();

$storiesRangeStart = 1;
$storiesRangeEnd = 1000;
$i = $storiesRangeStart;

// TODO : CLI arguments
// TODO : drop all records
$fullRebuild = true;

// Grab stories
while ($i <= $storiesRangeEnd)
{
	// Instanciate connection to Mongo
	$mongoConnection = new Mongo();
	$mongoDb = $mongoConnection->histoiresDeSexe;
	$mongoCollection = $mongoDb->stories;

	// Make sure story has not already been grabbed
	if (!$fullRebuild && $mongoCollection->count(array('storyId' => $i)) > 0) {
		logMessage(sprintf('Story %d has already been indexed, skipping.', $i));
		$i++;
		continue;
	}

	// Grab story
	$storyUrl = sprintf('http://www.histoires-de-sexe.net/sexe.php?histoire=%d', $i);
	$crawler = $client->request('GET', $storyUrl);

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
		$storyWords = array_merge(explode(' ', strtolower($storyText)), explode(' ', strtolower($storyTitle)));

		// Extract person data
		$surnamesDatabase = array('sophie', 'marie-lou', 'julien', 'véronique', 'flor');
		$storySurnames = array_unique(array_intersect($storyWords, $surnamesDatabase));

		// Extract places data
		$storyPlaces = array();

		// Extract accessories data
		$storyAccessories = array();

		// Save in database
		$mongoCollection->insert(array(
			'title'       => $storyTitle, 
			'text'        => $storyText,
			'storyUrl'    => $storyUrl,
			'storyId'     => $i,
			'words'       => $storyWords,
			'surnames'    => $storySurnames,
			'places'      => $storyPlaces,
			'accessories' => $storyAccessories,
		));
		logMessage(sprintf(
			'Extracted story %d : "%s" (strlen: %d, surnames: %d, places: %d, accessories: %d)', 
			$i, 
			$storyTitle, 
			strlen($storyText),
			count($storySurnames),
			count($storyPlaces),
			count($storyAccessories)
		));
	} else {
		logMessage(sprintf('No story text for story %d, skipping.', $i));
	}
	$i++;
}

function logMessage($message)
{
	echo sprintf("[%s] %s\n", date('r'), $message);
}
