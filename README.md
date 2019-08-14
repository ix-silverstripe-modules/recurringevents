silverstripe-recurringevents
=======================================

Designed to be extend an Event object that is a subsclass of SiteTree

Maintainer Contact
------------------
*  Guy Watson (<guy.watson@internetrix.com.au>)

## Requirements

* SilverStripe 4.4.0 or above

## Dependencies

* internetrix/silverstripe-events

It is possible to extend your own custom event class. You will however need to do the following

Add this hook at the end of the event class `getCMSFields()` method

$this->extend('updateEventCMSFields', $fields);


### Configuration

Please copy and paste the following to your _config/config.yml or similar. Substitute `Internetrix\Events\Pages\CalendarEvent` with your event class

Internetrix\Events\Pages\CalendarEvent:
  extensions:
    - Internetrix\RecurringEvents\Extensions\RecurringEventsExtension
