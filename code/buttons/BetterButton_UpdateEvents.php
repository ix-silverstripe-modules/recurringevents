<?php

/**
 * Defines the button that displays a pop up and lets the user select and save & publish a girdfield of recurring events
 *
 * @author  Guy Watson <guy.watson@internetrix.com.au>
 * @package  recurringevents
 */

class BetterButton_UpdateEvents extends BetterButtonCustomAction {

    /**
     * Builds the button
     */
    public function __construct() {
        parent::__construct('updateRecurringEventsHTML', 'Update Recurring Events');
    }

    /**
     * Adds the JS, sets up necessary HTML attributes
     * @return FormAction
     */
    public function getButtonHTML() {
    	$this->addExtraClass('gridfield-better-buttons-update-events');
        $html = parent::getButtonHTML();
        Requirements::javascript('recurringevents/javascript/gridfield_betterbuttons_updateevents.js');
        
		return $html;
    }

    /**
     * Determines if the button should show
     * @return boolean
     */
    public function shouldDisplay() {
        return !$this->gridFieldRequest->record->Duplicate;
    }
    
    public function getButtonLink() {
    	$link = Controller::join_links(
    		'recurringevents',
    		$this->actionName
    	);
    	return $this->gridFieldRequest->Link($link);
    }
}