<?php

namespace Internetrix\RecurringEvents\GridField;

use Internetrix\Events\Pages\CalendarEvent;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class GridFieldRecurringEventsKey implements GridField_HTMLProvider
{
    protected $fragment;
    protected $searchList;

    /**
     * @param string $fragment
     */
    public function __construct($fragment = 'toolbar-header-left')
    {
        $this->fragment = $fragment;
    }

    /**
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @param string $fragment
     * @return GridFieldAddExistingSearchButton $this
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;

        return $this;
    }

    public function getHTMLFragments($grid)
    {
        $data = ArrayList::create([
            ArrayData::create([
                'Title' => 'Master Event with recurrences',
                'Colour' => CalendarEvent::config()->master_colour,
            ]),
            ArrayData::create([
                'Title' => 'Recurring Event',
                'Colour' => CalendarEvent::config()->duplicate_colour,
            ]),
            ArrayData::create([
                'Title' => 'Regular Event',
                'Colour' => CalendarEvent::config()->regular_colour,
            ]),
        ]);

        return [
            $this->fragment => $data->renderWith('Internetrix/RecurringEvents/GridField/GridFieldRecurringEventsKey'),
        ];
    }
}
