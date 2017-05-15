<?php

namespace mason88\WikiTest;


/**
 * Class for running the wikipedia category readability tests.
 */
class WikiTest
{
	const WIKI_API = 'https://en.wikipedia.org/w/api.php';
	const EXLIMIT = 20;
	
	/** @var MediawikiApi */
	protected $wiki_api;
	
	/**
	 * @var array Array of pageids from categorymembers query
	 * https://en.wikipedia.org/w/api.php?action=help&modules=query%2Bcategorymembers
	 */
	protected $cat_pages = array();
	
	/**
	 * @var array Array of paragraphs from pageids query
	 * https://en.wikipedia.org/w/api.php?action=help&modules=query
	 */
	protected $paragraphs = array();


	public function __construct()
	{
		$this->wiki_api = new \Mediawiki\Api\MediawikiApi(self::WIKI_API);
	}

	/**
	 * Downloads articles from Wikipedia and provides readability scores.
	 * @param string $cat_name Name of category
	 * @param string $page_cnt Number of pages to load
     * @return array paragraphs data with scores
	 */
	public function get_readability($cat_name, $page_cnt)
	{
		if (! $this->load_cat_pages($cat_name, $page_cnt))
			return(array());
		
		if (! $this->load_paragraphs())
			return(array());
		
		foreach($this->paragraphs as $index => $paragraph) {
			$text_stat = new \DaveChild\TextStatistics\TextStatistics();
			$text_stat->normalise = FALSE; // dont bound scores to be >= 0
			$this->paragraphs[$index]['fk_score'] = $text_stat->fleschKincaidReadingEase($paragraph['paragraph']);
			$this->paragraphs[$index]['ari_score'] = $text_stat->automatedReadabilityIndex($paragraph['paragraph']);
		}
		
		return($this->paragraphs);
	}
	
	/**
	 * Load pageids data from Wikipedia API into $this->cat_pages.
	 * @param string $cat_name Name of category
	 * @param string $page_cnt Number of pages to load
     * @return int Count of pages loaded
	 */
	public function load_cat_pages($cat_name, $page_cnt)
	{
		$this->cat_pages = array();
		
		$response = $this->wiki_api->getRequest(\Mediawiki\Api\FluentRequest::factory()
			->setAction('query')
			->setParam('list', 'categorymembers')
			->setParam('format', 'json')
			->setParam('utf8', '1')
			->setParam('cmnamespace', '0')
			->setParam('cmtype', 'page')
			->setParam('cmlimit', $page_cnt)
			->setParam('cmtitle', "Category:{$cat_name}")
			->setParam('redirects', '1'));
		
		$this->cat_pages = $response['query']['categorymembers'];
		return(count($this->cat_pages));
	}
	
	/**
	 * Load data from Wikipedia API into $this->paragraphs.
	 * @return int Count of paragraphs loaded
	 */
	public function load_paragraphs()
	{
		$this->paragraphs = array();
		$pageids = array_column($this->cat_pages, 'pageid');
		
		foreach(array_chunk($pageids, self::EXLIMIT) as $pageids_grp) {
			$response = $this->wiki_api->getRequest(\Mediawiki\Api\FluentRequest::factory()
				->setAction('query')
				->setParam('prop', 'extracts')
				->setParam('format', 'json')
				->setParam('utf8', '1')
				->setParam('explaintext', '1')
				->setParam('exintro', '1')
				->setParam('exlimit', self::EXLIMIT)
				->setParam('pageids', implode('|', $pageids_grp))
				->setParam('redirects', '1'));
			
			$this->paragraphs = array_merge($this->paragraphs, $response['query']['pages']);
		}
		
		// create 'paragraph' key with first line from extract
		array_walk($this->paragraphs, function(&$row, $key) {
			$exploded_lines = explode("\n", $row['extract']);
			$row['paragraph'] = $exploded_lines[0];
		});
		
		return(count($this->paragraphs));
	}
}
