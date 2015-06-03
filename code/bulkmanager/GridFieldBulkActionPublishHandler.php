<?php
/**
 * Bulk action handler for publishing records.
 * 
 * @author Guy Watson <guy.watson@internetrix.com.au>
 * @package recurringevents
 */
class GridFieldBulkActionPublishHandler extends GridFieldBulkActionHandler
{	

	private static $allowed_actions = array(
		'publish'
	);

	private static $url_handlers = array(
		'publish' => 'publish'
	);

	/**
	 * Publish the selected records passed from the publish bulk action
	 * 
	 * @param SS_HTTPRequest $request
	 * @return SS_HTTPResponse List of deleted records ID
	 */
	public function publish(SS_HTTPRequest $request)
	{
		$ids = array();
		
		foreach ( $this->getRecords() as $record )
		{						
			array_push($ids, $record->ID);
			
			if(method_exists($record, 'doPublish')){
				$record->doPublish();
			}else{
				$record->publish("Stage", "Live");
			}
		}

		$response = new SS_HTTPResponse(Convert::raw2json(array(
			'done' 		=> true,
			'records' 	=> $ids
		)));
		$response->addHeader('Content-Type', 'text/json');
		$response->addHeader('X-Status', "Published " . count($ids) . " " . $this->getRecords()->first()->plural_name());
		return $response;	
	}
}