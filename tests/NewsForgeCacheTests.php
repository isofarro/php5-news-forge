<?php

require_once 'PHPUnit/Framework.php';
require_once '../NewsForge.php';
require_once '../NewsForgeCache.php';

class NewsForgeCacheTests extends PHPUnit_Framework_TestCase {
	protected $cacheRootDir = '/tmp/cache-test/';

	protected $htmlUrl  = 'http://www.example.com/index.html';
	protected $htmlBody = '<h1>This is a test html file</h1>';

	protected $xmlUrl  = 'http://www.example.com/index.xml';
	protected $xmlBody = '<xml>This is a test xml file</xml>';

	protected function setUp() {
		// Create a cache directory
		if (!mkdir($this->cacheRootDir)) {
			echo "ERROR: cannot create cache root directory ", 
				$this->cacheRootDir, "\n";
		}
	}

	protected function tearDown() {
		if (file_exists($this->cacheRootDir)) {
			$output = `rm -rf /tmp/cache-test/*`;
			//echo "INFO: $output\n";
			rmdir($this->cacheRootDir);
		}
	}

	public function testInit() {
		$cache = new NewsForgeCache();
		$this->assertNotNull($cache);

		// Make sure our cache root dir is writeable
		$this->assertTrue(file_exists($this->cacheRootDir));
		$this->assertTrue(is_dir($this->cacheRootDir));
		$this->assertTrue(is_writeable($this->cacheRootDir));

		$cache->setRootDir($this->cacheRootDir);
		
		$this->assertNotNull($cache);
	}

	public function testRootDir() {
		$cache = new NewsForgeCache();
		
		$cache->setRootDir($this->cacheRootDir);
		$this->assertEquals($this->cacheRootDir, $cache->getRootDir());
		
		// Check it adds the trailing slash
		$cache->setRootDir('/tmp');
		$this->assertEquals('/tmp/', $cache->getRootDir());
		
		$cache->setRootDir($this->cacheRootDir);
		$this->assertEquals($this->cacheRootDir, $cache->getRootDir());
	}	

	// TODO: test caching without setting a root dir
	
	public function testHtmlCache() {
		$cache = new NewsForgeCache();
		$cache->setRootDir($this->cacheRootDir);

		$this->assertFalse($cache->isCached('html', $this->htmlUrl));
		
		$isCreated = $cache->cache('html', $this->htmlUrl, $this->htmlBody);
		$this->assertTrue($isCreated);
		$this->assertTrue($cache->isCached('html', $this->htmlUrl));
		
		$cacheFilename = $this->cacheRootDir . 'html/www.example.com/' .
			md5($this->htmlUrl) . '.html';
		$this->assertTrue(file_exists($cacheFilename));
		
		$htmlBody = $cache->get('html', $this->htmlUrl);

		$this->assertNotNull($htmlBody);
		$this->assertEquals($htmlBody, $this->htmlBody);
		
		$success = $cache->delete('html', $this->htmlUrl);
		$this->assertTrue($success);
		$this->assertFalse($cache->isCached('html', $this->htmlUrl));
		
		$isDeleted = $cache->delete('html', $this->htmlUrl);
		$this->assertFalse($isDeleted);
		$this->assertFalse($cache->isCached('html', $this->htmlUrl));
	}

	public function testXmlCache() {
		$cache = new NewsForgeCache();
		$cache->setRootDir($this->cacheRootDir);

		$this->assertFalse($cache->isCached('xml', $this->xmlUrl));
		
		$isCreated = $cache->cache('xml', $this->xmlUrl, $this->xmlBody);
		$this->assertTrue($isCreated);
		$this->assertTrue($cache->isCached('xml', $this->xmlUrl));
		
		$cacheFilename = $this->cacheRootDir . 'xml/www.example.com/' .
			md5($this->xmlUrl) . '.xml';
		$this->assertTrue(file_exists($cacheFilename));		
		
		$xmlBody = $cache->get('xml', $this->xmlUrl);

		$this->assertNotNull($xmlBody);
		$this->assertEquals($xmlBody, $this->xmlBody);
		
		$isDeleted = $cache->delete('xml', $this->xmlUrl);
		$this->assertTrue($isDeleted);
		$this->assertFalse($cache->isCached('xml', $this->xmlUrl));
		
		$success = $cache->delete('xml', $this->xmlUrl);
		$this->assertFalse($success);
		$this->assertFalse($cache->isCached('xml', $this->xmlUrl));
	}

	public function testObjectCache() {
		$cache = new NewsForgeCache();
		$cache->setRootDir($this->cacheRootDir);
		
		// Create an object and guid
		$object = (object) NULL;
		$object->title     = 'This is a test object';
		$object->timestamp = time();
		$guid = 'unit-test-' . $object->timestamp;

		$this->assertFalse($cache->isCached('object', $guid));
		
		$isCreated = $cache->cache('object', $guid, $object);
		$this->assertTrue($isCreated);
		$this->assertTrue($cache->isCached('object', $guid));
		
		$cacheFilename = $this->cacheRootDir . 'object/' .
			$guid . '.obj';
		$this->assertTrue(file_exists($cacheFilename));		
		
		$cachedObj = $cache->get('object', $guid);

		$this->assertNotNull($cachedObj->title);
		$this->assertEquals($cachedObj->title, $object->title);
		$this->assertEquals($cachedObj->timestamp, $object->timestamp);
		
		$isDeleted = $cache->delete('object', $guid);
		$this->assertTrue($isDeleted);
		$this->assertFalse($cache->isCached('object', $guid));
		
		$success = $cache->delete('object', $guid);
		$this->assertFalse($success);
		$this->assertFalse($cache->isCached('object', $guid));
	}

	public function testStoryCache() {
		$cache = new NewsForgeCache();
		$cache->setRootDir($this->cacheRootDir);
		
		// Create a story and guid
		$guid = 'unit-test-' . time();

		$story = new NewsForgeStory();
		$story->setTitle('Unit Test');
		$story->setLink($this->htmlUrl);
		$story->setGuid($guid);
		$story->setPublished(date('c'));
		$story->setBody('This is a unit test story');

		$this->assertFalse($cache->isCached('story', $guid, $story));
		
		$isCreated = $cache->cache('story', $guid, $story);
		$this->assertTrue($isCreated);
		$this->assertTrue($cache->isCached('story', $guid, $story));
		
		$cacheFilename = $this->cacheRootDir . 'story/www.example.com/' .
			md5($guid) . '.ser';
		$this->assertTrue(file_exists($cacheFilename));
		
		$cachedObj = $cache->get('story', $guid, $story);

		$this->assertFalse(is_string($cachedObj));
		$this->assertTrue(is_object($cachedObj));
		$this->assertTrue(is_a($cachedObj, 'NewsForgeStory'));
		$this->assertEquals($cachedObj->getTitle(), $story->getTitle());
		$this->assertEquals($cachedObj->getPublished(), $story->getPublished());
		
		$isDeleted = $cache->delete('story', $guid, $story);
		$this->assertTrue($isDeleted);
		$this->assertFalse($cache->isCached('story', $guid, $story));
		
		$success = $cache->delete('story', $guid, $story);
		$this->assertFalse($success);
		$this->assertFalse($cache->isCached('story', $guid, $story));
	}


	public function testStoryCacheDomainString() {
		$cache = new NewsForgeCache();
		$cache->setRootDir($this->cacheRootDir);
		
		// Create a story and guid
		$guid = 'unit-test-' . time();
		$domain = 'www.example.com';

		$story = new NewsForgeStory();
		$story->setTitle('Unit Test');
		$story->setLink($this->htmlUrl);
		$story->setGuid($guid);
		$story->setPublished(date('c'));
		$story->setBody('This is a unit test story');

		$this->assertFalse($cache->isCached('story', $guid, $domain));
		
		$isCreated = $cache->cache('story', $guid, $story);
		$this->assertTrue($isCreated);
		$this->assertTrue($cache->isCached('story', $guid, $domain));
		
		$cacheFilename = $this->cacheRootDir . 'story/www.example.com/' .
			md5($guid) . '.ser';
		$this->assertTrue(file_exists($cacheFilename));
		
		$cachedObj = $cache->get('story', $guid, $domain);

		$this->assertFalse(is_string($cachedObj));
		$this->assertTrue(is_object($cachedObj));
		$this->assertTrue(is_a($cachedObj, 'NewsForgeStory'));
		$this->assertNotNull($cachedObj->getTitle());
		$this->assertEquals($cachedObj->getTitle(), $story->getTitle());
		$this->assertEquals($cachedObj->getPublished(), $story->getPublished());
		
		$isDeleted = $cache->delete('story', $guid, $domain);
		$this->assertTrue($isDeleted);
		$this->assertFalse($cache->isCached('story', $guid, $domain));
		
		$success = $cache->delete('story', $guid, $domain);
		$this->assertFalse($success);
		$this->assertFalse($cache->isCached('story', $guid, $domain));
	}

	public function testStoryCacheLinkString() {
		$cache = new NewsForgeCache();
		$cache->setRootDir($this->cacheRootDir);
		
		// Create a story and guid
		$guid = 'unit-test-' . time();
		$domain = 'www.example.com';

		$story = new NewsForgeStory();
		$story->setTitle('Unit Test');
		$story->setLink($this->htmlUrl);
		$story->setGuid($guid);
		$story->setPublished(date('c'));
		$story->setBody('This is a unit test story');

		$this->assertFalse($cache->isCached('story', $guid, $this->htmlUrl));
		
		$isCreated = $cache->cache('story', $guid, $story);
		$this->assertTrue($isCreated);
		$this->assertTrue($cache->isCached('story', $guid, $this->htmlUrl));
		
		$cacheFilename = $this->cacheRootDir . 'story/www.example.com/' .
			md5($guid) . '.ser';
		$this->assertTrue(file_exists($cacheFilename));
		
		$cachedObj = $cache->get('story', $guid, $this->htmlUrl);

		$this->assertFalse(is_string($cachedObj));
		$this->assertTrue(is_object($cachedObj));
		$this->assertTrue(is_a($cachedObj, 'NewsForgeStory'));
		$this->assertNotNull($cachedObj->getTitle());
		$this->assertEquals($cachedObj->getTitle(), $story->getTitle());
		$this->assertEquals($cachedObj->getPublished(), $story->getPublished());
		
		$isDeleted = $cache->delete('story', $guid, $this->htmlUrl);
		$this->assertTrue($isDeleted);
		$this->assertFalse($cache->isCached('story', $guid, $this->htmlUrl));
		
		$success = $cache->delete('story', $guid, $this->htmlUrl);
		$this->assertFalse($success);
		$this->assertFalse($cache->isCached('story', $guid, $this->htmlUrl));
	}
}


?>