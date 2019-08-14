<?php

namespace Internetrix\RecurringEvents\Extensions;

use Colymba\BulkManager\BulkManager;
use Internetrix\Events\Pages\CalendarEvent;
use Internetrix\RecurringEvents\FormFields\SpecificDatesField;
use Internetrix\RecurringEvents\BulkAction\CopyMasterEventHandler;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TimeField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\RelationList;

/**
 * Designed to be extend an Event object that is a subsclass of SiteTree
 *
 *
 * @author guy.watson@internetrix.com.au
 * @package recurringevents
 */
class RecurringEventsExtension extends DataExtension
{
    private static $db = [
        'Recurring' => 'Boolean',
        'Occurrences' => 'Int',
        'RecurringFrequency' => 'Enum("Weekly,Fortnightly,Monthly,Annually,Custom,ChooseDates", "Weekly")',
        'CustomRecurringNumber' => 'Int',
        'CustomRecurringFrequency' => 'Enum("Days,Weeks,Months,Years", "Weeks")',
        'Duplicate' => 'Boolean',
        'SpecificStarts' => 'Text',
        'SpecificEnds' => 'Text',
    ];

    private static $has_one = [
        'MasterEvent' => CalendarEvent::class,
    ];
    private static $has_many = [
        'RecurringEvents' => CalendarEvent::class,
    ];

    private static $event_class_name = CalendarEvent::class;
    private static $master_colour = '#0074C6';
    private static $duplicate_colour = '#72ADD7';
    private static $regular_colour = '#666666';

    /*
     * The following fields will be merged with $disallowed_db to create a list aod fields that
     * cannot be edited when bulk editing
     */

    private static $bulk_editing_blacklist = [
        'LegacyID',
        'LegacyLocation',
        'LegacyFileName',
        'LegacyCategoryID',
        'SubmitterFirstName',
        'SubmitterSurname',
        'SubmitterEmail',
        'SubmitterPhoneNumber',
        'SpecificStarts',
        'MapEmbed',
        'SpecificEnds',
        'Lat',
        'Lng',
        'HeaderImageSource',
        'SlidesSource',
        'PageBannersSource',
        'ShowShareIcons',
        'DocumentLinksSource',
        'RelatedLinksSource',
        'ContactBannerSource',
        'HeaderImageSource',
        'ShowInSearch',
        'PageBannersSource',
        'ContactName',
        'ContactPhone',
        'ContactEmail',
        'ContactAddress',
        'SolrKeywords',
        'MetaDescription',
        'ExtraMeta',
        'ContactAddress',
        'PublishOnDate',
        'UnPublishOnDate',
    ];

    /*
     * When copying content from the master event to the duplicate events we do not want to copy the following db fields
     */
    private static $disallowed_db = [
        'Start',
        'End',
        'Duplicate',
        'Recurring',
        'MasterEventID',
        'RecurringFrequency',
        'CustomRecurringFrequency',
        'Occurrences',
        'CustomRecurringNumber',
        'URLSegment',
        'Sort',
        'HasBrokenFile',
        'HasBrokenLink',
        'Version',
    ];

    /*
     * When copying content from the master event to the duplicate events we do not want to copy the following has_one fields
     */
    private static $disallowed_has_one = [
        'MasterEvent',
        'Subsite',
    ];

    /*
     * If any of the following fields has changed, the duplicate events need to be regenerated
     */
    private static $duplicate_dependant_fields = [
        'RecurringFrequency',
        'CustomRecurringNumber',
        'CustomRecurringFrequency',
    ];

    private static $update_recurring_events_summary_fields = [
        'Title' => 'Title',
        'Status' => 'Status',
        'Start.Nice' => 'Start',
        'End.Nice' => 'End',
        'DisplayCategories' => 'Categories',
    ];

    public function updateEventCMSFields($fields)
    {
        if ($this->owner->Duplicate) {
            $fields->removeByName('RecurringEvents');
            $master = DBField::create_field('HTMLText', '<a href="' . $this->owner->MasterEvent()->CMSEditLink() . '">Edit Master Event</a>');
            $fields->addFieldsToTab('Root.RecurringEvent', [
                $mf = ReadonlyField::create('EditEvent', 'This event is a recurrance.', $master),
            ]);
            $mf->dontEscape = true;
        } else {
            $fields->addFieldToTab('Root.RecurringEvents', CheckboxField::create('Recurring', 'Is this a recurring event?'));
            $fields->addFieldToTab(
                'Root.RecurringEvents',
                NumericField::create('Occurrences', 'How many times will this event recur?')
                ->setAttribute('autocomplete', 'off')
                ->displayIf('Recurring')->isChecked()->end()
            );
            $fields->addFieldToTab(
                'Root.RecurringEvents',
                DropdownField::create('RecurringFrequency', 'How often will this event recur?', $this->owner->NiceEnumValues('RecurringFrequency'))
                ->setAttribute('autocomplete', 'off')
                ->displayIf('Recurring')->isChecked()->end()
            );
            $fields->addFieldToTab(
                'Root.RecurringEvents',
                NumericField::create('CustomRecurringNumber', 'Repeat every ...')
                ->displayIf('Recurring')->isChecked()->andIf('RecurringFrequency')->isEqualTo('Custom')->end()
            );
            $fields->addFieldToTab(
                'Root.RecurringEvents',
                DropdownField::create('CustomRecurringFrequency', '', $this->owner->dbObject('CustomRecurringFrequency')->enumValues())
                ->addExtraClass('withmargin')
                ->displayIf('Recurring')->isChecked()->andIf('RecurringFrequency')->isEqualTo('Custom')->end()
            );

            $fields->addFieldToTab(
                'Root.RecurringEvents', \UncleCheese\DisplayLogic\Forms\Wrapper::create(
                    $sdFields = SpecificDatesField::create('SpecificDates', $this->owner->Occurrences)
                )->displayIf('Recurring')->isChecked()->andIf('RecurringFrequency')->isEqualTo('ChooseDates')->end()
            );

            $starts = unserialize($this->owner->SpecificStarts);
            $ends = unserialize($this->owner->SpecificEnds);
            if ($starts) {
                $sdFields->setStarts($starts);
            }
            if ($ends) {
                $sdFields->setEnds($ends);
            }

            if ($this->RecurringCount()) {
                $fields->addFieldToTab('Root.RecurringEvents', \UncleCheese\DisplayLogic\Forms\Wrapper::create(
                    $recurringEventsField = GridField::create(
                        'RecurringEvents',
                        'Recurring Events',
                        $this->owner->RecurringEvents(),
                        $recurringEventsFieldConfig = GridFieldConfig_Base::create()
                    )
                )->displayIf('Recurring')->isChecked()->end());

                $recurringEventsFieldConfig->addComponent($recurringEventsFieldBulkManager = new BulkManager(null, false));
                $recurringEventsFieldBulkManager->addBulkAction(CopyMasterEventHandler::class);
            }
        }

        return $fields;
    }

    public function onAfterWrite()
    {
        if (!$this->owner->Duplicate) {
            $recurringEvents = $this->owner->RecurringEvents();

            if ($this->owner->Recurring && $this->owner->Occurrences > 0) {
                $regenerate = $recurringEvents->Count() != $this->owner->Occurrences;
                $changedFields = $this->owner->getChangedFields();

                // Check if any of the duplicate_dependant_fields have changed. If so we need to regenerate the slave events
                // Need to do it this way because Versioned::publish called the forceChange method and all fields are marked as changed
                if (!$regenerate) {
                    foreach ($this->owner->config()->get('duplicate_dependant_fields') as $ddField) {
                        if (isset($changedFields[$ddField]['before']) && isset($changedFields[$ddField]['after'])) {
                            if ($changedFields[$ddField]['before'] != $changedFields[$ddField]['after']) {
                                $regenerate = true;

                                break;
                            }
                        }
                    }
                }

                if ($regenerate) {
                    if ($recurringEvents->Count()) {
                        foreach ($recurringEvents as $event) {
                            $event->doUnpublish();
                            $event->delete();
                        }
                    }
                    for ($i = 1; $i < $this->owner->Occurrences + 1; $i++) {
                        //we need to save a tmp variable `Iteration` on the owner object so the onBeforeDuplicate method knows what iteration it is at
                        $this->owner->Iteration = $i;

                        //the duplicate method calls onBeforeDuplicate. This is where we update its properties and where write is performed
                        $newEvent = $this->owner->duplicate(true);
                        $newEvent->publish('Stage', 'Live');
                    }
                } else {
                    //lets update all the duplicates
                    if ($recurringEvents->Count()) {
                        if ($this->owner->RecurringFrequency == 'ChooseDates') {
                            $specificStarts = unserialize($this->owner->SpecificStarts);
                            $specificEnds = unserialize($this->owner->SpecificEnds);
                            $i = 0;
                            foreach ($recurringEvents as $event) {
                                $event->Start = $specificStarts[$i] ?? $event->Start;
                                $event->End = $specificEnds[$i] ?? $event->End ;
                                $i++;
                                $event->write();
                            }
                        }
                    }
                }
            } elseif (!$this->owner->Recurring && $recurringEvents->Count()) {
                foreach ($recurringEvents as $event) {
                    $event->doUnpublish();
                    $event->delete();
                }
            }
        }
    }

    public function onBeforeDuplicate($original)
    {
        $clone = $this->owner;

        $i = $original->Iteration;

        if ($clone->RecurringFrequency == 'Weekly') {
            $clone->Start = strtotime(date('Y-m-d H:i:s', strtotime($original->Start)) . " +$i week");
            $clone->End = strtotime(date('Y-m-d H:i:s', strtotime($original->End)) . " +$i week");
        } elseif ($clone->RecurringFrequency == 'Fortnightly') {
            $clone->Start = strtotime(date('Y-m-d H:i:s', strtotime($original->Start)) . ' +' . $i * 2 . ' week');
            $clone->End = strtotime(date('Y-m-d H:i:s', strtotime($original->End)) . ' +' . $i * 2 . ' week');
        } elseif ($clone->RecurringFrequency == 'Monthly') {
            $clone->Start = strtotime(date('Y-m-d H:i:s', strtotime($original->Start)) . " +$i month");
            $clone->End = strtotime(date('Y-m-d H:i:s', strtotime($original->End)) . " +$i month");
        } elseif ($clone->RecurringFrequency == 'Custom') {
            $clone->Start = strtotime(date('Y-m-d H:i:s', strtotime($original->Start)) . ' +' . $i * $original->CustomRecurringNumber . ' ' . $original->CustomRecurringFrequency);
            $clone->End = strtotime(date('Y-m-d H:i:s', strtotime($original->End)) . ' +' . $i * $original->CustomRecurringNumber . ' ' . $original->CustomRecurringFrequency);
        } elseif ($clone->RecurringFrequency == 'Annually') {
            $clone->Start = strtotime(date('Y-m-d H:i:s', strtotime($original->Start)) . " +$i year");
            $clone->End = strtotime(date('Y-m-d H:i:s', strtotime($original->End)) . " +$i year");
        } else {
            $specificStarts = unserialize($original->SpecificStarts);
            $specificEnds = unserialize($original->SpecificEnds);
            $clone->Start = $specificStarts[$i - 1] ?? $original->Start;
            $clone->End = $specificEnds[$i - 1] ?? $original->End ;
        }

        $clone->Duplicate = true;
        $clone->Recurring = false;
        $clone->Occurrences = 0;
        $clone->MasterEventID = $original->ID;

        return $clone;
    }

    public function onAfterPublish(&$original)
    {
        if (!$this->owner->Duplicate) {
            $recurringEvents = $this->owner->RecurringEvents();
            if ($recurringEvents && $recurringEvents->Count()) {
                //first of all we need to delete the current live records that is not in the current relationshiplist
                $validIDs = $recurringEvents->getIdList();
                $this->deleteOldLiveRecords($validIDs);

                foreach ($recurringEvents as $recurringEvent) {
                    $recurringEvent->doPublish();
                }
            } else {
                $this->deleteOldLiveRecords();
            }
        }
    }

    public function onAfterUnpublish()
    {
        if (!$this->owner->Duplicate) {
            $recurringEvents = $this->owner->RecurringEvents();
            if ($recurringEvents && $recurringEvents->Count()) {
                foreach ($recurringEvents as $recurringEvent) {
                    $recurringEvent->deleteFromStage('Live');
                    $recurringEvent->write();
                }
            }
        }
    }

    public function onBeforeDelete()
    {
        parent::onBeforeDelete();

        if (!$this->owner->Duplicate) {
            $recurringEvents = $this->owner->RecurringEvents();
            if ($recurringEvents->Count()) {
                foreach ($recurringEvents as $event) {
                    $event->doUnpublish();
                    $event->delete();
                }
            }
        }
    }

    public function canBulkEdit()
    {
        $can = true;
        $this->owner->extend('extendedCanBulkEdit', $can);

        return $can;
    }

    public function canBulkPublish()
    {
        $can = true;
        $this->owner->extend('extendedCanBulkPublish', $can);

        return $can;
    }

    public function canBulkDelete()
    {
        $can = true;
        $this->owner->extend('extendedCanBulkDelete', $can);

        return $can;
    }

    public function copyMasterEventFields()
    {
        if (!$this->owner || !$this->owner->exists() || !$this->owner->MasterEvent() || !$this->owner->MasterEvent()->exists()) {
            return false;
        }

        $masterEvent = $this->owner->MasterEvent();
        $validDBFields = array_diff(array_keys($masterEvent->config()->get('db')), $masterEvent->config()->get('disallowed_db'));
        $validHasOneFields = array_diff(array_keys($masterEvent->config()->get('has_one')), $masterEvent->config()->get('disallowed_has_one'));

        foreach ($validDBFields as $validDBField) {
            $this->owner->$validDBField = $masterEvent->$validDBField;
        }

        //first remove the many many relationships
        if ($this->owner->manyMany()) {
            foreach ($this->owner->manyMany() as $name => $type) {
                $relations = $this->owner->$name();
                if ($relations) {
                    $relations->removeAll();
                }
            }
        }

        $this->owner = $this->owner->visibleDuplicateManyManyRelations($masterEvent, $this->owner);

        foreach ($validHasOneFields as $validHasOneField) {
            $this->owner->$validHasOneField = $masterEvent->$validHasOneField;
        }

        $this->owner->MasterEventID = $masterEvent->ID;
        $this->owner->write();
        $this->owner->publish('Stage', 'Live');

        return $this->owner;
    }

    public function RecurringCount()
    {
        return $this->owner->RecurringEvents()->Count();
    }

    /*
     * validIDs should be an array of IDs
     */
    public function deleteOldLiveRecords($validIDs = [])
    {
        $className = $this->owner->config()->get('event_class_name');
        $tableName = $className::getSchema()->tableName($className);

        $records = $className::get()->filter([
            'Duplicate' => 1,
            'MasterEventID' => $this->owner->ID,
        ]);

        if ($validIDs) {
            $records = $records->exclude([
                'ID' => $validIDs,
            ]);
        }

        if ($records && $records->Count()) {
            foreach ($records as $record) {
                $record->delete();
            }
        }
    }

    /**
     * Copied from DataObject.php because it was protected
     * but renamed
     */
    public function visibleDuplicateManyManyRelations($sourceObject, $destinationObject)
    {
        if (!$destinationObject || $destinationObject->ID < 1) {
            user_error("Can't duplicate relations for an object that has not been written to the database", E_USER_ERROR);
        }

        //duplicate complex relations
        // DO NOT copy has_many relations, because copying the relation would result in us changing the has_one
        // relation on the other side of this relation to point at the copy and no longer the original (being a
        // has_one, it can only point at one thing at a time). So, all relations except has_many can and are copied
        if ($sourceObject->hasOne()) {
            foreach ($sourceObject->hasOne() as $name => $type) {
                $this->owner->visibleDuplicateRelations($sourceObject, $destinationObject, $name);
            }
        }
        if ($sourceObject->manyMany()) {
            foreach ($sourceObject->manyMany() as $name => $type) {
                //many_many include belongs_many_many
                $this->owner->visibleDuplicateRelations($sourceObject, $destinationObject, $name);
            }
        }

        return $destinationObject;
    }

    /**
     * Copied from DataObject.php because it was private
     * but renamed
     */
    public function visibleDuplicateRelations($sourceObject, $destinationObject, $name)
    {
        $relations = $sourceObject->$name();
        if ($relations) {
            if ($relations instanceof RelationList) {   //many-to-something relation
                if ($relations->Count() > 0) {  //with more than one thing it is related to
                    foreach ($relations as $relation) {
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

    public function saveSpecificDates($values)
    {
        if (is_array($values) && !empty($values) && $this->owner->Recurring && $this->owner->RecurringFrequency == 'ChooseDates') {
            $starts = [];
            $ends = [];
            $dateField = DateField::create('tmp[date]', false);
            $timeField = TimeField::create('tmp[time]', false);

            foreach ($values as $key => $value) {
                if ($key == 'SpecificStarts') {
                    for ($i = 0; $i < $this->owner->Occurrences; $i++) {
                        $details = $value[$i];
                        if (isset($details['date']) && isset($details['time'])) {
                            $dateField->setValue($details['date']);
                            $timeField->setValue($details['time']);
                            $tmpDate = $dateField->dataValue();
                            $tmpTime = $timeField->dataValue();
                            if ($tmpDate && !$tmpTime) {
                                $tmpTime = '00:00:00';
                            }

                            if ($tmpDate && $tmpTime) {
                                $starts[$i] = $tmpDate . ' ' . $tmpTime;
                            }
                        }
                    }
                } else {
                    for ($i = 0; $i < $this->owner->Occurrences; $i++) {
                        $details = $value[$i];
                        if (isset($details['date']) && isset($details['time'])) {
                            $dateField->setValue($details['date']);
                            $timeField->setValue($details['time']);
                            $tmpDate = $dateField->dataValue();
                            $tmpTime = $timeField->dataValue();
                            if ($tmpDate && !$tmpTime) {
                                $tmpTime = '00:00:00';
                            }

                            if ($tmpDate && $tmpTime) {
                                $ends[$i] = $tmpDate . ' ' . $tmpTime;
                            }
                        }
                    }
                }
                $this->owner->SpecificStarts = serialize($starts);
                $this->owner->SpecificEnds = serialize($ends);
            }
        }

        return $this->owner;
    }

    /****************************************Utility Functions************************************/
    public function NiceEnumValues($enum)
    {
        $types = $this->owner->dbObject($enum)->enumValues();
        if ($types) {
            foreach ($types as $key => $value) {
                $types[$key] = FormField::name_to_label($value);
            }
        }

        return $types;
    }
    /*********************************************************************************************/
}
