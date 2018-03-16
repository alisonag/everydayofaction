<?php 
include 'actionnetwork.class.php';

function addToQueue( $resource, $endpoint, $index, $total ) {
		global $all_events;
		$all_events->array_push(
			array (
				'resource' => serialize($resource),
				'endpoint' => $endpoint,
				'processed' => 0,
			)
		);
	}

$api_key = "placeholder: YOUR API KEY HERE"
$event_campaign = "event_campaigns/placeholder-YOUR-EVENT-CAMPAIGN-ID/events"

$account = new ActionNetwork($api_key);

$events_p = $account->traverseFullCollection($event_campaign, 'addToQueue');

file_put_contents ( "events-all.json", json_encode($all_events));

?>