<?php

require_once 'NewsForgeCache.php';

$rootCacheDir = '/home/user/data/news-forge/cache/';
$cache = new NewsForgeCache();
$cache->setRootDir($rootCacheDir);
//print_r($cache);

$url = 'http://www.example.com/helloWorld.html';
$body = <<<HTML
<html>
	<head>
		<title>Hello World</title>
	</head>
	<body>
		<h1>Hello World</h1>	
	</body>
</html>
HTML;

if ($cache->urlExists($url)) {
	echo "INFO: Caching url\n";
	$cache->cacheUrl($url, $body);
}

echo "INFO: Getting cached URL\n";
$cachedBody = $cache->getUrl($url);

if ($body===$cachedBody) {
	echo "INFO: Cached entry match\n";
}

?>