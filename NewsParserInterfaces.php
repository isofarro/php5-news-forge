<?php

interface NewsParserInterface {
	public function getStories($dom);
	
}

class UkReutersParser implements NewsParserInterface {
	protected $timePattern = '/(\d{2}:\d{2} \w{2} \w{3})/';

	function getStories($dom) {
		$stories = array();
		// Extract a date
		$header = $dom->find('div.contentBand h1', 0);
		//echo $header->plaintext, "\n";
		if (preg_match('/(\w+, \d+ \w+ \d+)/', $header->plaintext, $matches)) {
			$date = $matches[1];
			//echo "Found date: $date\n";
		}
		
		// Extract list of stories
		$headlines = $dom->find('div.primaryContent div.headlineMed');
		foreach ($headlines as $headline) {
			$story = (object) NULL;
			
			$link = $headline->find('a', 0);
			if (!empty($link)) {
				$story->title = $link->plaintext;
				$story->link  = strtolower($link->href);
			
				if (preg_match('/id(\w+\d{9,16})/', $link->href, $matches)) {
					$validId = true;
					$id = $matches[1];
					
					// See if the last bit is a proper date
					$idDate = substr($matches[1], -8);
					$ts = mktime(0, 0, 0, 
							substr($idDate,4,2),
							substr($idDate,6,2),
							substr($idDate,0,4)
						);
					$tsDate = date('Ymd', $ts);
					//echo $tsDate;

					if ($tsDate==$idDate) {
						$story->id   = $matches[1];
						$story->guid = substr($matches[1],0, strlen($matches[1])-8);
						//echo 'Valid date.';
					} else {
						$validId = false;
						echo "INFO: Skipping invalid ID: ", $matches[1], "\n";
					}

					if ($validId && preg_match(
							$this->timePattern, 
							$headline->plaintext, 
							$matches
					)) {
						$time = $date . ' ' . $matches[1];
						$timestamp = strtotime($time);
						//echo " [", $time, "] $timestamp";
						//echo ' ', date('c', $timestamp);
						$story->published = date('c', $timestamp);
					}
				} else {
					//echo "WARN: Can't extract id from {$link->href}\n";
				}
				
			}
			//print_r($story); break;
			if (!empty($story->id)) {
				//echo "* ", $story->id, ': ', $story->title, "\n";
				$stories[] = $story;
			}
		}
		
		return $stories;
	}
	
}



?>