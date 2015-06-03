<?php
class SpecificDatesField extends FormField {
	
	private static $allowed_actions = array(
		'FieldsHTML'
	);

	protected $datesCount;
	protected $starts;
	protected $ends;
	

	public function __construct($name, $datesCount) {
		$this->datesCount = $datesCount;
		parent::__construct($name, '', '');
	}
	
	public function FieldHolder($properties = array()){
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('framework/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript('recurringevents/javascript/SpecificDatesField.js');
		
		return parent::FieldHolder($properties);
	}

	public function Fields() {
		
		$fields = FieldList::create();
		$fields->push(HeaderField::create('DuplicateNote', 'Please enter the start and end dates for the duplicate events', 4));
		for($i = 0; $i < $this->datesCount; $i++){
			$titlePos = $i + 1;
			$start = new DatetimeField("SpecificDates[SpecificStarts][$i]", "Start");
			$start->getDateField()
				->setConfig('showcalendar', true)
				->setAttribute('required', 'required')
				->setAttribute('aria-required', 'true')
				->setAttribute('autocomplete','off');
			
			$start->setTimeField(TimeDropdownField::create("SpecificDates[SpecificStarts][$i][time]" , 'Time'));

			if(!empty($this->starts) && isset($this->starts[$i])){
				$start->setValue($this->starts[$i]);
			}
			
			$end = new DatetimeField("SpecificDates[SpecificEnds][$i]", "End");
			$end->getDateField()
				->setConfig('showcalendar', true)
				->setAttribute('required', 'required')
				->setAttribute('aria-required', 'true')
				->setAttribute('autocomplete','off');
			
			$end->setTimeField(TimeDropdownField::create("SpecificDates[SpecificEnds][$i][time]" , 'Time'));

			if(!empty($this->ends) && isset($this->ends[$i])){
				$end->setValue($this->ends[$i]);
			}
			
			$fields->push(HeaderField::create("Duplicate$i", "Duplicate Event $titlePos", 4));
			$fields->push($start);
			$fields->push($end);
		}
		return $fields;
	}
	
	public function FieldsHTML(SS_HTTPRequest $request){
		$count = (int) $request->postVar('occurrences');
		$this->setCount($count);
		return $this->forTemplate();
	}
	
	public function setStarts($starts){
		$this->starts = $starts;
	}
	
	public function setEnds($ends){
		$this->ends = $ends;
	}
	
	public function setCount($count){
		$this->datesCount = $count;
	}
	
	public function getCount(){
		return $this->datesCount;
	}
	
	
	/**
	 * Ensures that all time and date fields are filled out
	 *
	 * @return bool
	 */
	public function validate($validator) {
		$valid = true;
		
		if(is_array($this->value)){
			
			foreach($this->value as $key => $value){
				//loop through starts and then ends
				foreach($value as $index => $details){
					if(isset($details['date']) && isset($details['time'])){
						if(!$details['date']){
// 							$valid = false;
							break;
						}
					}
				}
			}
		}
		
		if (!$valid) {
			$validator->validationError(
					$this->name,
					'Please enter all start and end dates',
					'required');
		}
		return $valid;
	}
	

}