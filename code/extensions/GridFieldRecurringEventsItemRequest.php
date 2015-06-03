<?php

/**
 *
 * @author  Guy Watson <guy.watson@internetrix.com.au>
 * @package  recurringevents
 *
 * */
class GridFieldRecurringEventsItemRequest extends DataExtension {


	/**
	 * @var array Allowed controller actions
	 */
	private static $allowed_actions = array (
        'recurringevents'
	);


    /**
     * Handles all custom action from DataObjects and hands them off to a sub-controller.
     * e.g. /recurringevents/mymethodname
     * 
     * Can't handle the actions here because the url_param '$Action!' gets matched, and we don't
     * get to read anything after /customaction/
     * 
     * @param  SS_HTTPRequest $r
     * @return RecurringEventsCustomActionRequest
     */
    public function recurringevents(SS_HTTPRequest $r) {
        $req = new RecurringEventsCustomActionRequest($this, $this->owner, $this->owner->ItemEditForm());

        return $req->handleRequest($r, DataModel::inst());
    }

}

/**
 * A subcontroller that handles custom actions. The parent controller matches 
 * the url_param '$Action!' and doesn't hand off any trailing params. This subcontoller
 * is aware of them
 *
 * /item/4/customaction/my-dataobject-method Invokes "my-dataobject-method" on the record
 *
 * @author  Guy Watson <guy.watson@internetrix.com.au>
 * @package  recurringevents
 */
class RecurringEventsCustomActionRequest extends RequestHandler {


    /**     
     * @var array
     */
    private static $url_handlers = array (
        '$Action!' => 'handleCustomAction'
    );


    /**     
     * @var array
     */
    private static $allowed_actions = array (
        'handleCustomAction'
    );


    /**
     * The parent extension. There are actually some useful methods in the extension
     * itself, so we need access to that object
     * 
     * @var GridFieldBetterButtonsItemRequest
     */
    protected $parent;


    /**
     * The parent controller
     * @var GridFieldDetailForm_ItemRequest
     */
    protected $controller;


    /**
     * The record we're editing
     * @var DataObject
     */
    protected $record;

    
    /**
     * The Form that is editing the record
     * @var  Form
     */
    protected $form;


    /**
     * Builds the request
     * @param GridFieldRecurringEventsItemRequest $parent     The extension instance
     * @param GridFieldDetailForm_ItemRequest $controller The request that points to the detail form
     */
    public function __construct($parent, $controller, $form) {
        $this->parent 		= $parent;
        $this->controller 	= $controller;
        $this->form 		= $form;
        $this->record 		= $this->controller->record;
        parent::__construct();
    }


    /**
     * Takes the action at /customaction/my-action-name and feeds it to the DataObject.
     * Checks to see if the method is allowed to be invoked first.
     * 
     * @param  SS_HTTPRequest $r
     * @return SS_HTTPResponse
     */
    public function handleCustomAction(SS_HTTPRequest $r) {
        $action = $r->param('Action');
        
        if(!$this->record->isCustomActionAllowed($action)) {
            return $this->httpError(403);
        }

        $result = $this->record->$action($this->controller, $r, $this->form);
        
        if(!is_array($result) || !array_key_exists('Form', $result) || !array_key_exists('DisplayMessage', $result)){
        	user_error("Results data is not what is expected", E_USER_ERROR);
        }
        
        if(array_key_exists('Message', $result)){
        	Controller::curr()->getResponse()->addHeader("X-Pjax","Content");
        	Controller::curr()->getResponse()->addHeader('X-Status', $result['Message']);
        }
        
        return $result['Form'];
        
            
        
        if($formAction->getRedirectType() == BetterButtonCustomAction::GOBACK) {
            return Controller::curr()->redirect(preg_replace('/\?.*/', '', $this->parent->getBackLink()));
        }
        
        return Controller::curr()->redirect(
            Controller::join_links($this->controller->gridField->Link("item"),$this->record->ID,"edit")
        );
    }
}
