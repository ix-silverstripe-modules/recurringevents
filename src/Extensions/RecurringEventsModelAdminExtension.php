<?php

namespace Internetrix\RecurringEvents\Extensions;

use Colymba\BulkManager\BulkAction\ArchiveHandler;
use Colymba\BulkManager\BulkAction\EditHandler;
use Colymba\BulkManager\BulkAction\PublishHandler;
use Colymba\BulkManager\BulkAction\UnPublishHandler;
use Colymba\BulkManager\BulkManager;
use Internetrix\RecurringEvents\GridField\GridFieldRecurringEventsKey;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridFieldDataColumns;

class RecurringEventsModelAdminExtension extends Extension
{
    public function updateEditForm($form)
    {
        $eventClassName = Config::inst()->get(RecurringEventsExtension::class, 'event_class_name');
        $sanitisedEventClassName = str_replace('\\', '-', $eventClassName);

        if ($this->owner->modelClass == $eventClassName) {
            $gridField = $form->Fields()->fieldByName($sanitisedEventClassName);
            $config = $gridField->getConfig();
            $blacklist = array_merge(Config::inst()->get($eventClassName, 'disallowed_db'), Config::inst()->get($eventClassName, 'bulk_editing_blacklist'));
            $validDBFields = array_diff(array_keys(Config::inst()->get($eventClassName, 'db')), $blacklist);
            $validDBFields = array_combine($validDBFields, $validDBFields);
            $singltonEvent = singleton($eventClassName);
            $fields = $singltonEvent->getCMSFields()->dataFields();
            $validFields = array_intersect_key($fields, $validDBFields);

            if ($singltonEvent->canBulkEdit() || $singltonEvent->canBulkPublish() || $singltonEvent->canBulkDelete()) {
                $config->addComponent($bulkEditManager = new BulkManager(array_keys($validFields), false));

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

            $dataColumns = $config->getComponentByType(GridFieldDataColumns::class);
            $newFieldFormatting = [];

            foreach ($dataColumns->getDisplayFields($gridField) as $key => $value) {
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
            $config->addComponent(new GridFieldRecurringEventsKey());
        }
    }
}
