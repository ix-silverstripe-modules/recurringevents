<?php
class GridFieldRecurringEventsKey implements
	GridField_HTMLProvider{

	protected $fragment;
	protected $searchList;

	/**
	 * @param string $fragment
	 */
	public function __construct($fragment = 'toolbar-header-left') {
		$this->fragment = $fragment;
	}

	/**
	 * @return string
	 */
	public function getFragment() {
		return $this->fragment;
	}

	/**
	 * @param string $fragment
	 * @return GridFieldAddExistingSearchButton $this
	 */
	public function setFragment($fragment) {
		$this->fragment = $fragment;
		return $this;
	}

	public function getHTMLFragments($grid) {
		$data = ArrayList::create(array(
			ArrayData::create(array(
				'Title' => 'Master Event with clones',
				'Colour' => Config::inst()->get('CalendarEvent', 'master_colour')
			)),
			ArrayData::create(array(
				'Title' 	=> 'Cloned Event',
				'Colour' 	=> Config::inst()->get('CalendarEvent', 'duplicate_colour')
			)),
			ArrayData::create(array(
				'Title' 	=> 'Regular Event',
				'Colour' 	=> Config::inst()->get('CalendarEvent', 'regular_colour')
			))
		));

		return array(
			$this->fragment => $data->renderWith('GridFieldRecurringEventsKey')
		);
	}

}
