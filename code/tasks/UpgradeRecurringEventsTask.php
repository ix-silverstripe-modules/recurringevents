<?php

class UpgradeRecurringEventsTask extends BuildTask {
	
	protected $title = "UpgradeRecurringEventsTask"; 
	protected $description = "Upgrade IRX recurring events from 2.4 to 3";
	
	function isEnabled(){
		return true;
	}
	
	function run($request){
		$counta = 0;
		$query = new SQLQuery("*","CalendarEvent");
		if( $data = $query->execute() ){
			$events = CalendarEvent::get();
			foreach( $data as $d ){
				if( isset($d['RecurringNumber']) && isset($d['LegacyID']) && $d['LegacyID'] == 0 ){
					$event = $events->find('ID', $d['ID']);
					$event->Occurrences = $d['RecurringNumber'];
					$event->ListingImageID = $d['EventImageID'];
					$event->Contact = $d['ContactName'];
					$event->Phone = $d['ContactCall'];
					$event->LegacyLocation = $d['DisplayAddress'];
					$event->LegacyID = 1;
					$event->write();
					$counta++;
				}
			}
		}
		echo "Upgraded $counta events.";
	}
}