<?php

namespace Internetrix\RecurringEvents\Extensions;

use Colymba\BulkManager\BulkAction\ArchiveHandler;
use Colymba\BulkManager\BulkAction\EditHandler;
use Colymba\BulkManager\BulkAction\PublishHandler;
use Colymba\BulkManager\BulkAction\UnPublishHandler;
use Colymba\BulkManager\BulkManager;
use Internetrix\RecurringEvents\GridField\GridFieldRecurringEventsKey;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\ORM\DataExtension;

class RecurringEventsPageExtension extends DataExtension
{
    public function updateEventsPageCMSFields($fields)
    {
        $eventsField = $fields->fieldByName('Root.ChildPages.ChildPages');

        if ($eventsField) {
            $eventClassName = Config::inst()->get(RecurringEventsExtension::class, 'event_class_name');

            $eventsFieldConfig = $eventsField->getConfig();

            $blacklist = array_merge(Config::inst()->get($eventClassName, 'disallowed_db'), Config::inst()->get($eventClassName, 'bulk_editing_blacklist'));
            $validDBFields = array_diff(array_keys(Config::inst()->get($eventClassName, 'db')), $blacklist);
            $validDBFields = array_combine($validDBFields, $validDBFields);
            $singltonEvent = singleton($eventClassName);
            $fields = $singltonEvent->getCMSFields()->dataFields();
            $validFields = array_intersect_key($fields, $validDBFields);

            if ($singltonEvent->canBulkEdit() || $singltonEvent->canBulkPublish() || $singltonEvent->canBulkDelete()) {
                $eventsFieldConfig->addComponent($bulkEditManager = new BulkManager(array_keys($validFields), false));

                if ($singltonEvent->canBulkEdit()) {
                    $bulkEditManager
                    ->addBulkAction(EditHandler::class);
                }
                if ($singltonEvent->canBulkPublish()) {
                    $bulkEditManager
                    ->addBulkAction(PublishHandler::class)
                    ->addBulkAction(UnPublishHandler::class);
                }
                if ($singltonEvent->canBulkDelete()) {
                    $bulkEditManager->addBulkAction(ArchiveHandler::class);
                }
            }

            $dataColumns = $eventsFieldConfig->getComponentByType(GridFieldDataColumns::class);
            $newFieldFormatting = [];

            foreach ($dataColumns->getDisplayFields($eventsField) as $key => $value) {
                $newFieldFormatting[$key] = function ($value, $item) use ($eventClassName) {
                    $bold = false;
                    if (!$item->Recurring && !$item->Duplicate) {
                        $colour = Config::inst()->get($eventClassName, 'regular_colour');
                    } else {
                        if ($item->Duplicate) {
                            $colour = Config::inst()->get($eventClassName, 'duplicate_colour');
                        } else {
                            $colour = Config::inst()->get($eventClassName, 'master_colour');
                            $bold = true;
                        }
                    }
                    if ($bold) {
                        $value = '<strong>' . $value . '</strong>';
                    }

                    return "<span style=' color: " . $colour . ";'>" . $value . '</span>';
                };
            }

            $dataColumns->setFieldFormatting($newFieldFormatting);
            $eventsFieldConfig->addComponent(new GridFieldRecurringEventsKey());
        }
    }
}
