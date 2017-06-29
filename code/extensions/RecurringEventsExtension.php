<?php
/**
 *
 * Designed to be extend an Event object that is a subsclass of SiteTree
 *
 *
 * @author guy.watson@internetrix.com.au
 * @package recurringevents
 *
 */
class RecurringEventsExtension extends DataExtension {
	
	private static $db = array(
		'Recurring' 		 		 		=> 'Boolean',
		'Occurrences' 	 		 			=> 'Int',
		'RecurringFrequency' 		 		=> 'enum("Weekly,Fortnightly,Monthly,Annually,Custom,ChooseDates", "Weekly")',
		'CustomRecurringNumber' 	 		=> 'Int',
		'CustomRecurringFrequency' 	 		=> 'enum("Days,Weeks,Months,Years", "Weeks")',
		'Duplicate'			 		 		=> 'Boolean',
		'SpecificStarts'					=> 'Text',
		'SpecificEnds'						=> 'Text'
	);
	
	private static $has_one = array(
		'MasterEvent' => 'CalendarEvent'
	);
	private static $has_many = array(
		'RecurringEvents' => 'CalendarEvent'
	);
	
	private static $event_class_name 	= 'CalendarEvent';
	private static $master_colour 	 	= '#0074C6';
	private static $duplicate_colour 	= '#72ADD7';
	private static $regular_colour 		= '#666666';
	
	private static $better_buttons_actions = array (
		'updateRecurringEventsHTML',
		'UpdateRecurringEventsForm',
		'doSaveOrPublishSelected'
	);
	
	/*
	 * The following fields will be merged with $disallowed_db to create a list aod fields that 
	 * cannot be edited when bulk editing
	 */
	
	private static $bulk_editing_blacklist = array(
		'LegacyID', 'LegacyLocation', 'LegacyFileName', 'LegacyCategoryID', 'SubmitterFirstName',
		'SubmitterSurname', 'SubmitterEmail', 'SubmitterPhoneNumber', 'SpecificStarts', 'MapEmbed',
		'SpecificEnds', 'Lat', 'Lng', 'HeaderImageSource', 'SlidesSource', 'PageBannersSource', 'ShowShareIcons',
		'DocumentLinksSource', 'RelatedLinksSource', 'ContactBannerSource', 'HeaderImageSource', 
		'ShowInSearch', 'PageBannersSource', 'ContactName', 'ContactPhone', 'ContactEmail', 'ContactAddress',
		'SolrKeywords', 'MetaDescription', 'ExtraMeta', 'ContactAddress', 'PublishOnDate', 'UnPublishOnDate'
	);
	
	/*
	 * When copying content from the master event to the duplicate events we do not want to copy the following db fields 
	 */
	private static $disallowed_db = array(
		'Start', 'End', 'Duplicate', 'Recurring', 'MasterEventID',
		'RecurringFrequency', 'CustomRecurringFrequency', 'Occurrences', 'CustomRecurringNumber',
		'URLSegment','Sort','HasBrokenFile','HasBrokenLink','Version'
	);
	
	/*
	 * When copying content from the master event to the duplicate events we do not want to copy the following has_one fields 
	 */
	private static $disallowed_has_one = array(
		'MasterEvent','Subsite'
	);

	/*
	 * If any of the following fields has changed, the duplicate events need to be regenerated
	 */
	private static $duplicate_dependant_fields = array(
		'RecurringFrequency','CustomRecurringNumber', 'CustomRecurringFrequency'
	);
	
	private static $update_recurring_events_summary_fields = array(
		'Title'=>'Title',
		"Status"=>'Status',
		'Start.Nice'=>'Start',
		'End.Nice'=>'End',
		'DisplayCategories'=>'Categories'
	);
	
	public function updateEventCMSFields($fields) {
		
		if($this->owner->Duplicate){
			$fields->removeByName('RecurringEvents');
			$master = DBField::create_field('HTMLText', '<a href="/admin/pages/edit/show/'.$this->owner->MasterEventID.'">Edit Master Event</a>' );
			$fields->addFieldsToTab('Root.RecurringEvent',array(
				$mf = ReadonlyField::create('EditEvent','This event is a recurrance.', $master)
			));
			$mf->dontEscape = true;
		} else {
			$fields->addFieldToTab('Root.RecurringEvents', CheckboxField::create('Recurring', 'Is this a recurring event?'));
			$fields->addFieldToTab('Root.RecurringEvents', NumericField::create('Occurrences', 'How many times will this event recur?')
				->setAttribute('autocomplete','off')
				->displayIf('Recurring')->isChecked()->end()
			);
			$fields->addFieldToTab('Root.RecurringEvents', DropdownField::create('RecurringFrequency', 'How often will this event recur?', $this->owner->NiceEnumValues('RecurringFrequency'))
				->setAttribute('autocomplete','off')
				->displayIf('Recurring')->isChecked()->end()
			);
			$fields->addFieldToTab('Root.RecurringEvents', NumericField::create('CustomRecurringNumber', 'Repeat every ...')
				->displayIf('Recurring')->isChecked()->andIf('RecurringFrequency')->isEqualTo('Custom')->end()
			);
			$fields->addFieldToTab('Root.RecurringEvents', DropdownField::create('CustomRecurringFrequency', '', $this->owner->dbObject('CustomRecurringFrequency')->enumValues())
				->addExtraClass('withmargin')
				->displayIf('Recurring')->isChecked()->andIf('RecurringFrequency')->isEqualTo('Custom')->end()
			);
			
			$fields->addFieldToTab('Root.RecurringEvents', $sdFields = SpecificDatesField::create('SpecificDates', $this->owner->Occurrences)
				->displayIf('Recurring')->isChecked()->andIf('RecurringFrequency')->isEqualTo('ChooseDates')->end()
			);
			
			$starts = unserialize($this->owner->SpecificStarts);
			$ends 	= unserialize($this->owner->SpecificEnds);
			if($starts){
				$sdFields->setStarts($starts);
			}
			if($ends){
				$sdFields->setEnds($ends);
			}
			
			if($this->RecurringCount()){
				$fields->addFieldToTab('Root.RecurringEvents', DisplayLogicWrapper::create(
					GridField::create('
						RecurringEvents', 
						'Recurring Events', 
						$this->owner->RecurringEvents(),
						GridFieldConfig_Base::create()
					)
				)->displayIf('Recurring')->isChecked()->end());
			}
		}

		return $fields;
	}
	
	function onAfterWrite() {
		
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage('Stage');
	
		if(!$this->owner->Duplicate){
			$recurringEvents = $this->owner->RecurringEvents();
			
			if($this->owner->Recurring && $this->owner->Occurrences > 0 ){
				
				$regenerate 	= $recurringEvents->Count() != $this->owner->Occurrences;
				$changedFields	= $this->owner->getChangedFields();
				
				//Check if any of the duplicate_dependant_fields have changed. If so we need to regenerate the slave events
				//Need to do it this way because Versioned::publish called the forceChange method and all fields are marked as changed
				if(!$regenerate){
					foreach($this->owner->config()->get('duplicate_dependant_fields') as $ddField){
						if(isset($changedFields[$ddField]['before']) && isset($changedFields[$ddField]['after'])){
							if($changedFields[$ddField]['before'] != $changedFields[$ddField]['after']){
								$regenerate = true;
								break;
							}
						}
					}
				}
				
				if($regenerate){

					if($recurringEvents->Count()){
						foreach($recurringEvents as $event){
							$event->doUnpublish();
							$event->delete();
						}
					}
					for($i = 1; $i < $this->owner->Occurrences + 1; $i++){
						//we need to save a tmp variable `Iteration` on the owner object so the onBeforeDuplicate method knows what iteration it is at
						$this->owner->Iteration = $i;
						
						//the duplicate method calls onBeforeDuplicate. This is where we update its properties and where write is performed
						$do = $this->owner->duplicate(true);
					}
				}else{
					//lets update all the duplicates
					if($recurringEvents->Count()){
						
						if($this->owner->RecurringFrequency == 'ChooseDates'){
							$specificStarts = unserialize($this->owner->SpecificStarts);
							$specificEnds 	= unserialize($this->owner->SpecificEnds);
							$i = 0;
							foreach($recurringEvents as $event){
								$event->Start 	= isset($specificStarts[$i]) ? $specificStarts[$i] : $event->Start;
								$event->End 	= isset($specificEnds[$i]) ? $specificEnds[$i] : $event->End ;
								$i++;
								$event->write();
							}
						}
						
						//Leave this here in case we change it so saving the master event saves all the duplicate events
// 						foreach($recurringEvents as $event){
// 							//just an extra safety check
// 							if($event->Duplicate){
								
// 								$validDBFields 		= array_diff(array_keys($this->owner->config()->get('db')), $this->owner->config()->get('disallowed_db'));
// 								$validHasOneFields 	= array_diff(array_keys($this->owner->config()->get('has_one')), $this->owner->config()->get('disallowed_has_one'));
								
// 								foreach($validDBFields as $validDBField){
// 									$event->$validDBField = $this->owner->$validDBField;
// 								}
								
// 								//first remove the many many relationships
// 								if ($event->many_many()) foreach($event->many_many() as $name => $type) {
// 									$relations = $event->$name();
// 									if ($relations) {
// 										$relations->removeAll();
// 									}
// 								}
								
// 								$event = $this->owner->visibleDuplicateManyManyRelations($this->owner, $event);
								
// 								foreach($validHasOneFields as $validHasOneField){
// 									$event->$validHasOneField = $this->owner->$validHasOneField;
// 								}
								
// 								if($this->owner->RecurringFrequency == 'ChooseDates'){
// 									$event->Start 	= isset($specificStarts[$i]) ? $specificStarts[$i] : $event->Start;
// 									$event->End 	= isset($specificEnds[$i]) ? $specificEnds[$i] : $event->End ;
// 									$i++;
// 								}
								
// 								$event->MasterEventID = $this->owner->ID;
// 								$event->write();
// 							}
// 						}
					}
				}
			}elseif(!$this->owner->Recurring && $recurringEvents->Count()){
				foreach($recurringEvents as $event){
					$event->doUnpublish();
					$event->delete();
				}
			}
		}
		
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage($oldMode);
	}
	
	/*
	 * 
	 * This is the most annoying extension. Our event object extends SiteTree which extends DataObject. SiteTree::duplicate() calls DataObject::duplicate()
	 * Both methods call onBeforeDuplicate, however 
	 * SiteTree calls
	 *   $this->invokeWithExtensions('onBeforeDuplicate', $page);
	 * and DataObject calls
	 *   $clone->invokeWithExtensions('onBeforeDuplicate', $this, $doWrite);
	 *   
	 * This means that the extension hook onBeforeDuplicate is called twice but with two different arguements. SiteTree passes in the clone while
	 * DataObject passes in the original
	 * 
	 * 
	 */
	function onBeforeDuplicate($originalOrClone){
		$original 	= null;
		$clone 		= null;
		
		//if the object has no ID then it is the clone.
		if($originalOrClone->ID == 0){
			$clone 		= $originalOrClone;
			$original 	= $this->owner;
			$i 			= $original->Iteration;
		}else{
			$clone 		= $this->owner;
			$original 	= $originalOrClone;
			//If we have passed in the $clone then this is the second time this function has been called 
			//and we can return the clone
			return $clone;
		}
		
		if($clone->RecurringFrequency == 'Weekly'){
			$clone->Start = strtotime(date("Y-m-d H:i:s", strtotime($original->Start)) . " +$i week");
			$clone->End   = strtotime(date("Y-m-d H:i:s", strtotime($original->End)) . " +$i week");
		}elseif($clone->RecurringFrequency == 'Fortnightly'){
			$clone->Start = strtotime(date("Y-m-d H:i:s", strtotime($original->Start)) . " +" . $i * 2  . " week");
			$clone->End   = strtotime(date("Y-m-d H:i:s", strtotime($original->End)) . " +" . $i * 2  . " week");
		}elseif($clone->RecurringFrequency == 'Monthly'){
			$clone->Start = strtotime(date("Y-m-d H:i:s", strtotime($original->Start)) . " +$i month");
			$clone->End   = strtotime(date("Y-m-d H:i:s", strtotime($original->End)) . " +$i month");
		}elseif($clone->RecurringFrequency == 'Custom'){
			$clone->Start = strtotime(date("Y-m-d H:i:s", strtotime($original->Start)) . " +" . $i * $original->CustomRecurringNumber  . " " . $original->CustomRecurringFrequency);
			$clone->End   = strtotime(date("Y-m-d H:i:s", strtotime($original->End)) . " +" . $i * $original->CustomRecurringNumber  . " " . $original->CustomRecurringFrequency);
		}elseif($clone->RecurringFrequency == 'Annually'){
			$clone->Start = strtotime(date("Y-m-d H:i:s", strtotime($original->Start)) . " +$i year");
			$clone->End   = strtotime(date("Y-m-d H:i:s", strtotime($original->End)) . " +$i year");
		}else{
			$specificStarts = unserialize($original->SpecificStarts);
			$specificEnds 	= unserialize($original->SpecificEnds);
			$clone->Start 	= isset($specificStarts[$i-1]) ? $specificStarts[$i-1] : $original->Start;
			$clone->End 	= isset($specificEnds[$i-1]) ? $specificEnds[$i-1] : $original->End ;
		}
		
		$clone->Duplicate 		= true;
		$clone->Recurring 		= false;
		$clone->MasterEventID 	= $original->ID;

		return $clone;
	}
	
	function onAfterPublish(&$original) {
		if(!$this->owner->Duplicate){
			
			$recurringEvents = $this->owner->RecurringEvents();
			if($recurringEvents && $recurringEvents->Count()){
				//first of all we need to delete the current live records that is not in the current relationshiplist
				$validIDs = $recurringEvents->getIdList();
				$this->deleteOldLiveRecords(implode(",", $validIDs));

				foreach($recurringEvents as $recurringEvent){
					$recurringEvent->doPublish();
				}
			}else{
				$this->deleteOldLiveRecords();
			}
		}
		
	}
	
	function onAfterUnpublish() {
		if(!$this->owner->Duplicate){
			$recurringEvents = $this->owner->RecurringEvents();
			if($recurringEvents && $recurringEvents->Count()){
				foreach($recurringEvents as $recurringEvent){
					$recurringEvent->deleteFromStage('Live');
					$recurringEvent->write();
				}
			}
		}
	}
	
	public function onBeforeDelete(){
		parent::onBeforeDelete();
	
		if(!$this->owner->Duplicate){
			$recurringEvents = $this->owner->RecurringEvents();
			if($recurringEvents->Count()){
				foreach($recurringEvents as $event){
					$event->doUnpublish();
					$event->delete();
				}
			}
		}
	}
	
	public function canBulkEdit(){
		$can = true;
		$this->owner->extend('extendedCanBulkEdit', $can);
		return $can;
	}
	
	public function canBulkPublish(){
		$can = true;
		$this->owner->extend('extendedCanBulkPublish', $can);
		return $can;
	}
	
	public function canBulkDelete(){
		$can = true;
		$this->owner->extend('extendedCanBulkDelete', $can);
		return $can;
	}
	
	public function copySourceToDestination($source, $destination){
	
		$validDBFields 		= array_diff(array_keys($source->config()->get('db')), $source->config()->get('disallowed_db'));
		$validHasOneFields 	= array_diff(array_keys($source->config()->get('has_one')), $source->config()->get('disallowed_has_one'));
	
		foreach($validDBFields as $validDBField){
			$destination->$validDBField = $source->$validDBField;
		}
		
		//first remove the many many relationships
		if ($destination->many_many()) foreach($destination->many_many() as $name => $type) {
			$relations = $destination->$name();
			if ($relations) {
				$relations->removeAll();
			}
		}
	
		$destination = $source->visibleDuplicateManyManyRelations($source, $destination);
	
		foreach($validHasOneFields as $validHasOneField){
			$destination->$validHasOneField = $source->$validHasOneField;
		}
	
		$destination->MasterEventID = $this->owner->ID;
		$destination->write();
	}
	public function RecurringCount(){
		return $this->owner->RecurringEvents()->Count();
	}
	
	/*
	 * validIDs should be a string
	 */
	public function deleteOldLiveRecords($validIDs = null){
		$filter    = array();
		$filter[]  = "\"Duplicate\" = 1";
		$filter[]  = "\"MasterEventID\" = " . $this->owner->ID;
		$className = $this->owner->config()->get('event_class_name');
		
		if($validIDs != null){
			$filter[] = "\"" . $className . "_Live\".\"ID\" NOT IN(" . $validIDs . ")";
		}
		
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage('Live');
		
		$dos = DataObject::get($className, implode(" AND ", $filter));
		
		if($dos && $dos->Count()){
			foreach($dos as $d){
				$d->delete();
			}
		}
		
		Versioned::reading_stage($oldMode);
	}
	
	/**
	 * Copied from DataObject.php because it was protected
	 * but renamed
	 */
	public function visibleDuplicateManyManyRelations($sourceObject, $destinationObject) {
		if (!$destinationObject || $destinationObject->ID < 1) {
			user_error("Can't duplicate relations for an object that has not been written to the database", E_USER_ERROR);
		}
	
		//duplicate complex relations
		// DO NOT copy has_many relations, because copying the relation would result in us changing the has_one
		// relation on the other side of this relation to point at the copy and no longer the original (being a
		// has_one, it can only point at one thing at a time). So, all relations except has_many can and are copied
		if ($sourceObject->has_one()) foreach($sourceObject->has_one() as $name => $type) {
			$this->owner->visibleDuplicateRelations($sourceObject, $destinationObject, $name);
		}
		if ($sourceObject->many_many()) foreach($sourceObject->many_many() as $name => $type) {
			//many_many include belongs_many_many
			$this->owner->visibleDuplicateRelations($sourceObject, $destinationObject, $name);
		}
	
		return $destinationObject;
	}
	
	/**
	 * Copied from DataObject.php because it was private
	 * but renamed
	 */
	public function visibleDuplicateRelations($sourceObject, $destinationObject, $name) {
		$relations = $sourceObject->$name();
		if ($relations) {
			if ($relations instanceOf RelationList) {   //many-to-something relation
				if ($relations->Count() > 0) {  //with more than one thing it is related to
					foreach($relations as $relation) {
						$destinationObject->$name()->add($relation);
					}
				}
			} else {    //one-to-one relation
				$destinationObject->{"{$name}ID"} = $relations->ID;
			}
		}
	}
	
	/*
	 * Takes an array of date and time arrays. Converts the date and time array to a string
	 * 
	 */
	
	public function saveSpecificDates($values){
		if(is_array($values) && !empty($values)){
			$starts 	= array();
			$ends 		= array();
			$dateField 	= DateField::create('tmp[date]', false);
			$timeField 	= TimeField::create('tmp[time]', false);
			
			foreach($values as $key => $value){
				if($key == 'SpecificStarts'){
					for($i = 0; $i < $this->owner->Occurrences; $i++){
						$details = $value[$i];
						if(isset($details['date']) && isset($details['time'])){
							$dateField->setValue($details['date']);
							$timeField->setValue($details['time']);
							$tmpDate = $dateField->dataValue();
							$tmpTime = $timeField->dataValue();
							if($tmpDate && !$tmpTime){
								$tmpTime = "00:00:00";
							}
								
							if($tmpDate && $tmpTime){
								$starts[$i] = $tmpDate . " " . $tmpTime;
							}
						}
					}
				}else{
					for($i = 0; $i < $this->owner->Occurrences; $i++){
						$details = $value[$i];
						if(isset($details['date']) && isset($details['time'])){
							$dateField->setValue($details['date']);
							$timeField->setValue($details['time']);
							$tmpDate = $dateField->dataValue();
							$tmpTime = $timeField->dataValue();
							if($tmpDate && !$tmpTime){
								$tmpTime = "00:00:00";
							}
								
							if($tmpDate && $tmpTime){
								$ends[$i] = $tmpDate . " " . $tmpTime;
							}
						}
					}
				}
				$this->owner->SpecificStarts 	= serialize($starts);
				$this->owner->SpecificEnds 		= serialize($ends);
			}
		}
		return $this->owner;
	}
	
	public function updateBetterButtonsActions(FieldList $actions){
	
		if(!$this->owner->Duplicate && $this->owner->Recurring){
			$actions->push(
				BetterButton_UpdateEvents::create()
			);
		}
	}
	
	public function UpdateRecurringEventsForm($controller = null, $request = null, $pForm = null){
		$summaryFields = $this->owner->config()->get('update_recurring_events_summary_fields');
		if($summaryFields && array_key_exists(0, $summaryFields)) {
			$summaryFields = array_combine(array_values($summaryFields), array_values($summaryFields));
		}
		
		$config = GridFieldConfig_Base::create()
			->addComponent(new GridFieldSelectColumns())
			->removeComponentsByType('GridFieldFilterHeader');
		$config->getComponentByType('GridFieldDataColumns')->setDisplayFields($summaryFields);
		
		$gridfield = GridField::create(
			'RecurringEvents', 
			'Recurring Events', 
			$this->owner->RecurringEvents(),
			$config
		);

		$fields  		= FieldList::create(array($gridfield));
		$actions 		= FieldList::create();
		$actions->push(
			FormAction::create('doSaveSelected', 'Save master details to selected')
				->setUseButtonTag('true')
				->addExtraClass('ss-ui-action-constructive')
				->setAttribute('data-icon','accept')
				->addExtraClass('doSaveSelected')
				->setDisabled(true)
		);
		if((method_exists($this->owner, 'canPublish') && $this->owner->canPublish())  || (!method_exists($this->owner, 'canPublish') && $this->owner->canEdit()) ){
			$actions->push(
				FormAction::create('doPublishSelected', 'Save &amp; Publish master details to selected')
					->setUseButtonTag('true')
					->addExtraClass('ss-ui-action-constructive')
					->setAttribute('data-icon','accept')
					->addExtraClass('doPublishSelected')
					->setDisabled(true)
			);
		}
		
		$form = Form::create($this->owner, 'UpdateRecurringEventsForm', $fields, $actions);
		
		$this->owner->extend('updateUpdateRecurringEventsForm', $form);

		return $form;
	}
	
	public function updateRecurringEventsHTML($controller = null, $request = null, $pForm = null){
		$form = $this->UpdateRecurringEventsForm($controller, $request, $pForm);
		$form->setFormAction($controller->Link('recurringevents/doSaveOrPublishSelected'));
		$result = array(
			'Form' 			 => $form->forTemplate(),
			'DisplayMessage' => false
		);
		
		$this->owner->extend('updateUpdateRecurringEventsHTML', $result);
		
		return $result;
	}
	
	/**
	 * Either updates the current link or creates a new one
	 * Returns field template to update the interface
	 * @return String
	 **/
	public function doSaveOrPublishSelected($controller = null, $request = null, $pForm = null){
		$postVars = $request->postVars();
		$ids 	  = array();
		$publish  = false;
		
		foreach($postVars as $key => $value){
			if(stripos($key, "Select_") !== FALSE){
				$ids[] = filter_var($key, FILTER_SANITIZE_NUMBER_INT);
			}elseif(stripos($key, "Publish") !== FALSE){
				$publish = true;
			}
		}
		
		if(!empty($ids)){
			$className = $this->owner->config()->get('event_class_name');
			foreach ($ids as $id){
				$event = $className::get()->byID($id);
				if(!$event){
					user_error("Passed the ID '$id' of an event that could not be found", E_USER_ERROR);
				}
				//writes the destination ($event)
				$this->owner->copySourceToDestination($this->owner, $event);
				
				if($publish){
					if(method_exists($event, 'doPublish')){
						$event->doPublish();
					}else{
						$event->publish("Stage", "Live");
					}
				}
			}
		}
		
		$form = $this->UpdateRecurringEventsForm($controller, $request, $pForm);
		$form->setFormAction($controller->Link('recurringevents/doSaveOrPublishSelected'));
		$message = "Selected events were successfully saved";
		$message = $publish ? $message . " and published": $message;
		
		$result = array(
			'Form' 			 => $form->forTemplate(),
			'DisplayMessage' => false,
			'Message'		 => $message
		);
		
		return $result;
	}
	
	/****************************************Utility Functions************************************/
	public function NiceEnumValues($enum){
		$types = $this->owner->dbObject($enum)->enumValues();
		if($types){
			foreach($types as $key=>$value){
				$types[$key] = FormField::name_to_label($value);
			}
		}
		return $types;
	}
	/*********************************************************************************************/
	
}