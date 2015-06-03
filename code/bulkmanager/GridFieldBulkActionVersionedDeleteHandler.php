<?php
/**
 * Bulk action handler for deleting versioned records.
 * 
 * @author Guy Watson <guy.watson@internetrix.com.au>
 * @package recurringevents
 */
class GridFieldBulkActionVersionedDeleteHandler extends GridFieldBulkActionHandler
{	
	private static $allowed_actions = array(
		'versioneddelete'
	);

	private static $url_handlers = array(
		'versioneddelete' => 'versioneddelete'
	);
	

	/**
	 * @param SS_HTTPRequest $request
	 * @return SS_HTTPResponse List of published record IDs
	 */
	public function versioneddelete(SS_HTTPRequest $request)
	{
		$ids 	= array();
		$plural = $this->getRecords()->first()->plural_name();
		
		foreach ( $this->getRecords() as $record ){		
			array_push($ids, $record->ID);
			if($record->hasExtension('Versioned')){
				if(method_exists($record, 'doUnpublish')){
					$record->doUnpublish();
				}else{
					$record->deleteFromStage('Live');
				}
			}
			$record->delete();
		}

		$response = new SS_HTTPResponse(Convert::raw2json(array(
			'done' 		=> true,
			'records' 	=> $ids
		)));

		$response->addHeader('Content-Type', 'text/json');
		$response->addHeader('X-Status', "Deleted " . count($ids) . " " . $plural);
		return $response;	
	}
}