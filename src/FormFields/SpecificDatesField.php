<?php

namespace Internetrix\RecurringEvents\FormFields;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\View\Requirements;

class SpecificDatesField extends FormField
{
    private static $allowed_actions = [
        'FieldsHTML',
    ];

    protected $datesCount;
    protected $starts;
    protected $ends;

    public function __construct($name, $datesCount)
    {
        $this->datesCount = $datesCount;
        parent::__construct($name, '', '');
    }

    public function FieldHolder($properties = [])
    {
        Requirements::javascript('internetrix/silverstripe-recurringevents:javascript/SpecificDatesField.js');

        return parent::FieldHolder($properties);
    }

    public function Fields()
    {
        $fields = FieldList::create();
        $fields->push(HeaderField::create('DuplicateNote', 'Please enter the start and end dates for the duplicate events', 4));
        for ($i = 0; $i < $this->datesCount; $i++) {
            $titlePos = $i + 1;
            $start = new DatetimeField("SpecificDates[SpecificStarts][$i]", 'Start');
            $start->setAttribute('required', 'required')
                ->setAttribute('aria-required', 'true')
                ->setAttribute('autocomplete', 'off');

            if (!empty($this->starts) && isset($this->starts[$i])) {
                $start->setValue($this->starts[$i]);
            }

            $end = new DatetimeField("SpecificDates[SpecificEnds][$i]", 'End');
            $end->setAttribute('required', 'required')
                ->setAttribute('aria-required', 'true')
                ->setAttribute('autocomplete', 'off');

            if (!empty($this->ends) && isset($this->ends[$i])) {
                $end->setValue($this->ends[$i]);
            }

            $fields->push(HeaderField::create("Duplicate$i", "Duplicate Event $titlePos", 4));
            $fields->push($start);
            $fields->push($end);
        }

        return $fields;
    }

    public function FieldsHTML(HTTPRequest $request)
    {
        $count = (int) $request->postVar('occurrences');
        $this->setCount($count);

        return $this->forTemplate();
    }

    public function setStarts($starts)
    {
        $this->starts = $starts;
    }

    public function setEnds($ends)
    {
        $this->ends = $ends;
    }

    public function setCount($count)
    {
        $this->datesCount = $count;
    }

    public function getCount()
    {
        return $this->datesCount;
    }

    /**
     * Ensures that all time and date fields are filled out
     *
     * @return bool
     */
    public function validate($validator)
    {
        $valid = true;

        if (is_array($this->value)) {
            foreach ($this->value as $key => $value) {
                //loop through starts and then ends
                foreach ($value as $index => $details) {
                    if (isset($details['date']) && isset($details['time'])) {
                        if (!$details['date']) {
                            //$valid = false;
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
                    'required'
            );
        }

        return $valid;
    }
}
