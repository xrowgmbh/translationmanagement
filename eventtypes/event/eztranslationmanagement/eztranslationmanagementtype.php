<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/
class eZTranslationManagementType extends eZWorkflowEventType
{
	const WORKFLOW_TYPE_STRING = 'eztranslationmanagement';
	function eZTranslationManagementType()
	{
		$this->eZWorkflowEventType( eZTranslationManagementType::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'kernel/workflow/event', "Translation Management" ) );
		$this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'before' ) ) ) );
	}

	function execute( $process, $event )
	{
	    $parameters = $process->attribute( 'parameter_list' );
	    $errors = array();
	    $http = eZHTTPTool::instance();
	    if ( class_exists( 'eZWebDAVServer', false ) or class_exists( 'eZWebDAVContentServer', false ) )
	    {
	        eZWebDAVServer::appendLogEntry( "Translation Workflow: Continue with EZ_WORKFLOW_TYPE_STATUS_ACCEPTED" );
            return eZWorkflowType::STATUS_ACCEPTED;
	    }
	    if ( eZSys::isShellExecution() )
	    {
	    	eZDebug::writeNotice( "Translation Workflow: Continue with EZ_WORKFLOW_TYPE_STATUS_ACCEPTED" );
	    	return eZWorkflowType::STATUS_ACCEPTED;
	    }
	    # We do not want something to happen is somebody just adds a node with browse
	    if ( $http->hasPostVariable( 'BrowseActionName' ) )
	    {
	    	return eZWorkflowType::STATUS_WORKFLOW_DONE;
	    }
	    if ( $http->hasPostVariable( 'UploadActionName' ) )
	    {
	    	return eZWorkflowType::STATUS_WORKFLOW_DONE;
	    }
	    if ( $GLOBALS['module']->currentView() != "edit"  and $GLOBALS['module']->currentView() != "collect" )
            {
		return eZWorkflowType::STATUS_WORKFLOW_DONE;
            }
		if ( isset( $GLOBALS['eZ_TM_IGNORE_WORKFLOW'] ) and $GLOBALS['eZ_TM_IGNORE_WORKFLOW'] == true )
		{
            return eZWorkflowType::STATUS_ACCEPTED;
		}

		if ( $http->hasPostVariable( 'TMPublish' ) )
		{
			$parameters['eZ_TM_IGNORE_WORKFLOW'] = true;
			$process->setParameters( $parameters );
            return eZWorkflowType::STATUS_ACCEPTED;
		}
        
		$versionID =& $parameters['version'];

		
		$object = eZContentObject::fetch( $parameters['object_id'] );
		$draft = eZContentObjectVersion::fetchVersion( $parameters['version'], $parameters['object_id'] );
        $initialLanguage = $draft->attribute( "initial_language" );
        $initialLanguageLocale = $initialLanguage->attribute( "locale" );
        
        if ( ( $http->hasPostVariable( 'TMTranslate' ) or $http->hasPostVariable( 'TMUpdate' ) ) and !$http->hasPostVariable( 'language_code_include' )  )
        {
            $errors['no_language'] = true;
        }
        
		if ( $http->hasPostVariable( 'TMAddLanguage' ) )
        {
            xrowTranslationManager::addTranslation( $draft, $http->postVariable( 'AddLanguage' ) );
        }
	    if( $http->hasPostVariable( 'deadline_day' ) and $http->hasPostVariable( 'deadline_month' ) and $http->hasPostVariable( 'deadline_year' ) )
	    {
	        $now = new eZDate();
	        $deadline = new eZDate();
	        $deadline->setMDY( $http->postVariable( 'deadline_month' ), $http->postVariable( 'deadline_day' ), $http->postVariable( 'deadline_year' ) );
	        
	        if ( !$deadline->isValid() or !$deadline->isGreaterThan( $now ) )
	           $deadline = null;
	    }
        if ( !isset( $deadline ) )
        {
            $deadline = new eZDate();
            $deadline->adjustDate( 0, 7, 0 );
	    }

		$language_list = xrowTranslationManager::getObjectVersionLanguages( $draft );

		if( $http->hasPostVariable( 'TMTranslate' ) and count( $errors ) == 0 )
		{
		    $process->setAttribute( 'activation_date', time() );

		    $parameters['language_code_include'] = $http->postVariable( 'language_code_include');
            $parameters['translators'] = $http->postVariable( 'translators' );
            $parameters['approvers'] = $http->postVariable( 'approvers' );
            $parameters['url'] = eZSys::hostname() . eZSys::indexDir();
            
		    $parameters['object_ids'] = array( $parameters['object_id'] );
		    $parameters['versions'] = array( $parameters['object_id'] => $parameters['version'] );
		    $parameters['source'] = $initialLanguageLocale;
		    $parameters['deadline'] = $deadline->timeStamp();
		    $process->setParameters( $parameters );
		    $workflow = $process->attribute( 'workflow' );
		                // fetch next event
            $event_pos = $process->attribute( "event_position" );
            $next_event_pos = $event_pos + 1;
            $next_event_id = $workflow->fetchEventIndexed( $next_event_pos );
            $lastEventStatus = eZWorkflowType::STATUS_ACCEPTED;
            if ( $next_event_id !== null )
            {
                eZDebugSetting::writeDebug( 'workflow-process', $event_pos , "workflow  not done");
                $process->advance( $next_event_id, $next_event_pos, $lastEventStatus );
                $workflowEvent = eZWorkflowEvent::fetch( $next_event_id );
            }
		    $process->store();

            // implement later http://ez.no/bugs/view/8733 has to be fixed
            eZDebug::writeDebug( $GLOBALS['LastAccessesURI'], 'eZTranslationManagementType LastAccessesURI');
		    $process->RedirectUrl['redirect_url'] = $GLOBALS['LastAccessesURI'];
		    return eZWorkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
		}
		
		$index =eZSys::indexFile( true );
		$requestUri = eZSys::indexFile( false ) . eZSys::requestUri();
		$replace = "@" . preg_quote( $index ) . "@i";
        $requestUri = preg_replace( array( $replace ), array(''), $requestUri, 1 );
        $vars = array( 'request_uri' => $requestUri, 'parameters' => $parameters, "process" => $process, "draft" => $draft, 'object' => $object, 'deadline' => $deadline, 'errors' => $errors );
        if( $http->hasPostVariable( 'language_code_include') )
        {
            $vars['language_code_include'] = $http->postVariable( 'language_code_include');
        }
        else
        {
        	$vars['language_code_include'] = array();
        }
        $process->Template = array( 'templateName' => 'design:workflow/eventtype/result/event_eztranslationmanagement.tpl',
                                    'templateVars' => $vars,
                                    'path' => array( array( 'url' => false, 'text' => 'Translation Manager' ) )
                                  );

		return eZWorkflowType::STATUS_FETCH_TEMPLATE_REPEAT;
	}

}

eZWorkflowEventType::registerEventType( eZTranslationManagementType::WORKFLOW_TYPE_STRING, "eztranslationmanagementtype" );

?>
