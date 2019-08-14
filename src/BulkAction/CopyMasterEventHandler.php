<?php

namespace Internetrix\RecurringEvents\BulkAction;

use Colymba\BulkManager\BulkAction\Handler;
use Colymba\BulkTools\HTTPBulkToolsResponse;
use Exception;
use SilverStripe\Control\HTTPRequest;

/**
 * Bulk action handler for recursive copying of master event content.
 */
class CopyMasterEventHandler extends Handler
{
    /**
     * URL segment used to call this handler
     * If none given, @BulkManager will fallback to the Unqualified class name
     *
     * @var string
     */
    private static $url_segment = 'copymasterevent';

    /**
     * RequestHandler allowed actions.
     *
     * @var array
     */
    private static $allowed_actions = ['copymasterevent'];

    /**
     * RequestHandler url => action map.
     *
     * @var array
     */
    private static $url_handlers = [
        '' => 'copymasterevent',
    ];

    /**
     * Front-end label for this handler's action
     *
     * @var string
     */
    protected $label = 'Copy Master Event Content';

    /**
     * Front-end icon path for this handler's action.
     *
     * @var string
     */
    protected $icon = '';

    /**
     * Extra classes to add to the bulk action button for this handler
     * Can also be used to set the button font-icon e.g. font-icon-trash
     *
     * @var string
     */
    protected $buttonClasses = 'font-icon-rocket';

    /**
     * Whether this handler should be called via an XHR from the front-end
     *
     * @var boolean
     */
    protected $xhr = true;

    /**
     * Set to true is this handler will destroy any data.
     * A warning and confirmation will be shown on the front-end.
     *
     * @var boolean
     */
    protected $destructive = false;

    /**
     * Return i18n localized front-end label
     *
     * @return array
     */
    public function getI18nLabel()
    {
        return $this->getLabel();
    }

    /**
     * Copy master event content to the selected records passed from the copy master bulk action.
     *
     * @param HTTPRequest $request
     *
     * @return HTTPBulkToolsResponse
     */
    public function copymasterevent(HTTPRequest $request)
    {
        $records = $this->getRecords();
        $response = new HTTPBulkToolsResponse(false, $this->gridField);

        try {
            $doneCount = 0;
            $failCount = 0;
            foreach ($records as $record) {
                $done = $record->copyMasterEventFields();
                if ($done) {
                    $doneCount++;
                } else {
                    $failCount++;
                }
            }

            $message = sprintf(
                'Copied master event content to %1$d of %2$d records.',
                $doneCount,
                $doneCount + $failCount
            );
            $response->setMessage($message);
        } catch (Exception $ex) {
            $response->setStatusCode(500);
            $response->setMessage($ex->getMessage());
        }

        return $response;
    }
}
