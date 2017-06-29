<?php

class RecurringEventsModelAdminExtension extends DataExtension {
	
	function updateEditForm($form) {
		$eventClassName = Config::inst()->get('RecurringEventsExtension','event_class_name');
		if($this->owner->modelClass == $eventClassName) {
			$gridField 			= $form->Fields()->dataFieldByName($eventClassName);
			$config 			= $gridField->getConfig();
			$blacklist 			= array_merge(Config::inst()->get($eventClassName, 'disallowed_db'), Config::inst()->get($eventClassName, 'bulk_editing_blacklist'));
			$validDBFields 		= array_diff(array_keys(Config::inst()->get($eventClassName, 'db')), $blacklist);
			$validDBFields		= array_combine($validDBFields, $validDBFields);
			$singltonEvent		= singleton($eventClassName);
			$fields 	 		= $singltonEvent->getCMSFields()->dataFields();
			$validFields 		= array_intersect_key($fields, $validDBFields);

			if($singltonEvent->canBulkEdit() || $singltonEvent->canBulkPublish() || $singltonEvent->canBulkDelete()){
				$config->addComponent($bulkEditManager = new GridFieldBulkManager(array_keys($validFields), false));
				if($singltonEvent->canBulkEdit()){
					$bulkEditManager
					->addBulkAction('bulkEdit', 'Edit', 'GridFieldBulkActionExtendedEditHandler', array(
							'isAjax' 		=> false,
							'icon' 			=> 'pencil',
							'isDestructive' => false
					));
				}
				if($singltonEvent->canBulkPublish()){
					$bulkEditManager
					->addBulkAction('publish', 'Publish', 'GridFieldBulkActionPublishHandler', array(
							'isAjax' 		=> true,
							'icon' 			=> 'pencil',
							'isDestructive' => false
					))
					->addBulkAction('unpublish', 'Unpublish', 'GridFieldBulkActionUnpublishHandler', array(
							'isAjax' 		=> true,
							'icon' 			=> 'pencil',
							'isDestructive' => false
					));
				}
				if($singltonEvent->canBulkDelete()){
					$bulkEditManager->addBulkAction('versioneddelete', 'Delete', 'GridFieldBulkActionVersionedDeleteHandler', array(
							'isAjax' 		=> true,
							'icon' 			=> 'pencil',
							'isDestructive' => false
					));
				}
			}
			
			$dataColumns		= $config->getComponentByType('GridFieldDataColumns');
			$newFieldFormatting = array();
			
			foreach($dataColumns->getDisplayFields($gridField) as $key => $value){
				$newFieldFormatting[$key] = function($value, $item) use ($eventClassName){
					$bold = false;
					if(!$item->Recurring && !$item->Duplicate){
						$colour = Config::inst()->get($eventClassName, 'regular_colour');
					}else{
						if($item->Duplicate){
							$colour = Config::inst()->get($eventClassName, 'duplicate_colour');
						}else{
							$colour = Config::inst()->get($eventClassName, 'master_colour');
							$bold = true;
						}
					}
					if($bold){
						$value = "<strong>". $value ."</strong>";
					}
					
					return "<span style=' color: ".$colour.";'>" . $value . "</span>";
				};
			}
			
			$dataColumns->setFieldFormatting($newFieldFormatting);
			$config->addComponent(new GridFieldRecurringEventsKey());
			
			/*
			$alerts = array(
				'Duplicate' => array(
					'comparator' => 'equal',
					'patterns' => array(
						'1' => array(
							'status' => 'clone',
							'message' => function($record) {
								$master = $record->MasterEvent();
								if($master && $master->exists()){
									$oneDay = $master->OneDay();
									$format =  $oneDay ? "Cloned from master event - %s ( %s )" : "Cloned from master event - %s ( %s - %s )";
									return sprintf($format, $master->Title, $master->dbObject('Start')->Date(), $master->dbObject('End')->Date());
								}
								return "Can't find master event.";
							}
						), '0' => array(
							'status' => 'master',
							'message' => function($record) {
								$master = $record->RecurringEvents()->Count();
								if($master && $master > 0){
									$oneDay = $master;
									$format =  $oneDay ? "Master event with %s recurrences" : "";
									return sprintf($format, $master);
								}
								return "";
							}
						),
					),
				),
			);
			
			$config->addComponent(new GridFieldMasterHighlighter($alerts));
			*/
		}
	}

}