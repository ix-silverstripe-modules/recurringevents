<?php
/**
 * Bulk action handler for unpublishing records.
 * 
 * @author Guy Watson <guy.watson@internetrix.com.au>
 * @package recurringevents
 */
class GridFieldBulkActionUnpublishHandler extends GridFieldBulkActionHandler
{	
	private static $allowed_actions = array(
		'unpublish'
	);

	private static $url_handlers = array(
		'unpublish' => 'unpublish'
	);
	

	/**
	 * Unpublish the selected records passed from the unpublish bulk action
	 * 
	 * @param SS_HTTPRequest $request
	 * @return SS_HTTPResponse List of published record IDs
	 */
	public function unpublish(SS_HTTPRequest $request)
	{
		$ids = array();
		
		foreach( $this->getRecords() as $record ){				
			if($record->hasExtension('Versioned')){
				array_push($ids, $record->ID);
				if(method_exists($record, 'doUnpublish')){
					$record->doUnpublish();
				}else{
					$record->deleteFromStage('Live');
				}
			}	
		}

		$response = new SS_HTTPResponse(Convert::raw2json(array(
			'done' 		=> true,
			'records' 	=> $ids
		)));
		$response->addHeader('Content-Type', 'text/json');
		$response->addHeader('X-Status', "Unpublished " . count($ids) . " " . $this->getRecords()->first()->plural_name());
		return $response;	
	}
}