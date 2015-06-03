silverstripe-recurringevents
=======================================

Designed to be extend an Event object that is a subsclass of SiteTree

Maintainer Contact
------------------
*  Guy Watson (<guy.watson@internetrix.com.au>)

## Requirements

SilverStripe 3.1.6. (Not tested with any other versions)

## Dependencies

irxeventcalendar module

It is possible to extend your own custom event class. You will however need to do the following

Add this hook at the end of the event class `getCMSFields()` method

$this->extend('updateEventCMSFields', $fields);


### Configuration

Please copy and paste the following to your _config/config.yml or similar. Subsitute `CalendarEvent` with your event class

CalendarEvent:
  extensions:
    - 'RecurringEventsExtension'
    
