<?php

/**
 * simple php SDK for Action Network API v2
 * verson 1.2 - October 2016
 * author: Jonathan Kissam, jonathankissam.com
 * API documentation: https://actionnetwork.org/docs
 */

class ActionNetwork {

	private $api_key = getenv('ACTION_NETWORK_API_KEY', true) ?: getenv('ACTION_NETWORK_API_KEY');
	
	private $api_version = '2';
	private $api_base_url = 'https://actionnetwork.org/api/v2/';

	public function __construct($api_key = null) {
		if(!extension_loaded('curl')) trigger_error('ActionNetwork requires PHP cURL', E_USER_ERROR);
		if(is_null($api_key)) trigger_error('api key must be supplied', E_USER_ERROR);
		$this->api_key = $api_key;
	}

	public function call($endpoint, $method = 'GET', $object = null) {
		
		// if endpoint is passed as an absolute URL (i.e., if it came from an API response), remove the base URL
		$endpoint = str_replace($this->api_base_url,'',$endpoint);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 100);
		if ($method == "POST") {
			curl_setopt($ch, CURLOPT_POST, 1);
			if ($object) {
				$json = json_encode($object);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'OSDI-API-Token: '.$this->api_key,
					'Content-Type: application/json',
					'Content-Length: ' . strlen($json))
				);
			}
		} else {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('OSDI-API-Token:'.$this->api_key));
		}
		curl_setopt($ch, CURLOPT_URL, $this->api_base_url.$endpoint);

		$response = curl_exec($ch);

		curl_close($ch);

		return json_decode($response);
	}

	// helper functions for collections

	public function getResourceId($resource) {
		if (!isset($resource->identifiers) || !is_array($resource->identifiers)) { return null; }
		foreach ($resource->identifiers as $identifier) {
			if (substr($identifier,0,15) == 'action_network:') { return substr($identifier,15); }
		}
	}

	public function getResourceTitle($resource) {
		if (isset($resource->title)) { return $resource->title; }
		if (isset($resource->name)) { return $resource->name; }
		if (isset($resource->email_addresses) && is_array($resource->email_addresses) && count ($resource->email_addresses)) {
			if (isset($resource->email_addresses[0]->address)) {
				return $resource->email_addresses[0]->address;
			}
		}
	}
	public function getResourceStartDate($resource) {
		if (isset($resource->start_date)) { return $resource->start_date; }
	}
	public function getResourceURL($resource) {
		if (isset($resource->browser_url)) { return $resource->browser_url; }
	}
	public function getResourceLocation($resource) {
		if (isset($resource->location)) { return $resource->location; }
	}
	public function getResourceImage($resource) {
		if (isset($resource->featured_image_url)) { return $resource->featured_image_url; }
	}
	
	public function getNextPage($response) {
		return isset($response->_links) && isset($response->_links->next) && isset($response->_links->next->href) ? $response->_links->next->href : false;
	}
	
	// get embed codes
	public function getEmbedCodes($resource, $array = false) {
		$embed_endpoint = isset($resource->_links->{'action_network:embed'}->href) ? $resource->_links->{'action_network:embed'}->href : '';
		if (!$embed_endpoint) { return $array ? array() : null; }
		$embed_codes = $this->call($embed_endpoint);
		return $array ? (array) $embed_codes : $embed_codes;
	}
//customized:
	public function simplifyCollection($response, $endpoint) {
		$osdi = 'osdi:'.$endpoint;
		$collection = array();
		if (isset($response->_embedded->$osdi)) {
			$collection_full = $response->_embedded->$osdi;
			foreach ($collection_full as $resource) {
				$resource_id = $this->getResourceId($resource);
				$resource_title = $this->getResourceTitle($resource);
				$resource_date = $this->getResourceStartDate($resource);
				$resource_url = $this->getResourceURL($resource);
				$resource_location = $this->getResourceLocation($resource);
				$resource_image = $this->getResourceImage($resource);
				
				$collection[] = array('id' => $resource_id, 'title' => $resource_title, 
						'start_date' => $resource_date, 'url'=>$resource_url,
						'location' => $resource_location, 'image'=>$resource_image); 
			}
		}
		return $collection;
	}

	public function getCollection($endpoint, $page = 1, $per_page = null) {
		if ($page > 1) { $endpoint .= '?page='.$page; }
		if ($per_page) { $endpoint .= ( ($page > 1) ? '&' : '?') . 'per_page=' . $per_page; }
		return $this->call($endpoint);
	}

	public function getSimpleCollection($endpoint, $page = 1, $per_page = null) {
		$response = $this->getCollection($endpoint, $page, $per_page);
		return $this->simplifyCollection($response, $endpoint);
	}

	public function getFullSimpleCollection($endpoint) {
		$response = $this->getCollection($endpoint);
		if (isset($response->total_pages)) {
			if ($response->total_pages > 1) {
				$full_simple_collection = $this->simplifyCollection($response, $endpoint);
				for ($page=2;$page<=$response->total_pages;$page++) {
					$response = $this->getCollection($endpoint, $page);
					$full_simple_collection = array_merge($full_simple_collection, $this->simplifyCollection($response, $endpoint));
				}
				return $full_simple_collection;
			} else {
				return $this->simplifyCollection($response, $endpoint);
			}
		} else {
			$full_simple_collection = $this->simplifyCollection($response, $endpoint);
			$next_page = $this->getNextPage($response);
			while ($next_page) {
				$response = $this->getCollection($next_page);
				$full_simple_collection = array_merge($full_simple_collection, $this->simplifyCollection($response, $endpoint));
				$next_page = $this->getNextPage($response);
			}
			return $full_simple_collection;
		}
	}

	/**
	 * Traverse Collections
	 *
	 * if you are using a class that extends ActionNetwork,
	 * this method will first test to see if $callback is a defined method
	 * of your class. If not it will be treated as the name of a php function.
	 *
	 * It will be passed the following variables:
	 * $resource : the ActionNetwork resource object
	 * $endpoint : the endpoint passed to traverseCollection or traverseFullCollection
	 * $index : the order of the resource in the list
	 * $total : the total number of resources in the page or collection
	 * $this : if an independent php function, will be passed the ActionNetwork object
	 */
	public function traverseCollection($endpoint, $callback) {
		$response = $this->getCollection($endpoint);
		$this->traverseCollectionPage($endpoint, $response, $callback);
		return $response;
	}

	public function traverseFullCollection($endpoint, $callback) {
		global $all_events;
		$response = $this->getCollection($endpoint);
		$response_web = $this->simplifyCollection($response, 'events');
		$all_events[1] = $response_web;
		//var_dump($response);
		$this->traverseCollectionPage($endpoint, $response, $callback);
		if ( isset($response->total_pages) && ($response->total_pages > 1) ) {

			for ($page=2;$page<=$response->total_pages;$page++) {
				
				$response = $this->getCollection($endpoint, $page);
				$response_web = $this->simplifyCollection($response, 'events');
				//array_push($all_events, $response_web);
				$all_events[$page] = $response_web;
				$this->traverseCollectionPage($endpoint, $response, $callback);

			}
		} else {
			$next_page = $this->getNextPage($response);
			while ($next_page) {
				$response = $this->getCollection($next_page);
				$this->traverseCollectionPage($endpoint, $response, $callback);
				$next_page = $this->getNextPage($response);
			}

			return $full_simple_collection;
		}
		return $response;
	}

public function addToQueue( $resource, $endpoint, $index, $total ) {
		global $all_events;
		echo "hi";
		$all_events->array_push(
			
			array (
				'resource' => serialize($resource),
				'endpoint' => $endpoint,
				'processed' => 0,
			)
		);
		// error_log( "Actionnetwork_Sync::addToQueue called; endpoint: $endpoint, index: $index, total: $total", 0 );
	}

	private function traverseCollectionPage($endpoint, $response, $callback) {
		if (!is_string($callback)) { return; }
		if (method_exists($this, $callback)) {
			$callback_method = 'object_method';
		} else {
			$callback_method = 'function_name';
			if (!function_exists($callback)) { return; }
		}
		$osdi = 'osdi:'.$endpoint;
		$total = $response->total_records;
		$index = ($response->page - 1) * $response->per_page + 1;
		if (isset($response->_embedded->$osdi)) {
			$collection = $response->_embedded->$osdi;
			foreach ($collection as $resource) {
				if ($callback_method == 'object_method') {
					$this->$callback($resource, $endpoint, $index, $total);
				} else {
					$callback($resource, $endpoint, $index, $total, $this);
				}
			}
		}
	}

	// get simple lists (id and title) of petitions, events, fundraising pages, advocacy campaigns, forms and tags

	public function getAllPetitions() {
		return $this->getFullSimpleCollection('petitions');
	}

	public function getAllEvents() {
		return $this->getFullSimpleCollection('events');
	}

	public function getAllFundraisingPages() {
		return $this->getFullSimpleCollection('fundraising_pages');
	}

	public function getAllAdvocacyCampaigns() {
		return $this->getFullSimpleCollection('advocacy_campaigns');
	}

	public function getAllForms() {
		return $this->getFullSimpleCollection('forms');
	}

	public function getAllTags() {
		return $this->getFullSimpleCollection('tags');
	}

	// get embeds for a petition, event, fundraising page, advocacy campaign or form

	public function getEmbed($type, $id, $size = 'standard', $style = 'default') {
		if (!in_array($type, array('petitions', 'events', 'fundraising_pages', 'advocacy_campaigns', 'forms')))
			trigger_error('getEmbed must be passed a type of petitions, events, fundraising_pages, advocacy_campaigns or forms', E_USER_ERROR);
		if (!in_array($size, array('standard', 'full'))) trigger_error('getEmbed must be passed a size of standard or full', E_USER_ERROR);
		if (!in_array($style, array('default', 'layout_only', 'no'))) trigger_error('getEmbed must be passed a style of default, layout_only or no', E_USER_ERROR);
		$embeds = $this->call($type.'/'.$id.'/embed');
		$selector = 'embed_'.$size.'_'.$style.'_styles';
		return $embeds->$selector;
	}



}

