<?php

/**
 * @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
 * @package translationmanagement
 */
class eZTranslationCollectionType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'eztranslationcollection';

    function eZTranslationCollectionType()
    {
        $this->eZWorkflowEventType( eZTranslationCollectionType::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'kernel/workflow/event', "Translation Collection" ) );
        $this->setTriggerTypes( array( 
            'content' => array( 
                'publish' => array( 
                    'before' 
                ) 
            ) , 
            'tm' => array( 
                'translate' => array( 
                    'before' 
                ) 
            ) 
        ) );
    }

    function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        eZDebug::writeDebug( 'Start Translation Collection', 'eZTranslationCollectionType::execute()' );
        $index = eZSys::indexFile( true );
        $requestUri = eZSys::indexFile( false ) . eZSys::requestUri();
        $replace = "@" . preg_quote( $index ) . "@i";
        $requestUri = preg_replace( array( 
            $replace 
        ), array( 
            '' 
        ), $requestUri, 1 );
        
        if ( class_exists( 'eZWebDAVServer', false ) or class_exists( 'eZWebDAVContentServer', false ) )
        {
            eZWebDAVServer::appendLogEntry( "Translation Workflow: Continue with EZ_WORKFLOW_TYPE_STATUS_ACCEPTED" );
            return eZWorkflowType::STATUS_ACCEPTED;
        }
        if ( isset( $GLOBALS['eZ_TM_IGNORE_WORKFLOW'] ) and $GLOBALS['eZ_TM_IGNORE_WORKFLOW'] == true )
        {
            return eZWorkflowType::STATUS_ACCEPTED;
        }
        if ( isset( $parameters['eZ_TM_IGNORE_WORKFLOW'] ) and $parameters['eZ_TM_IGNORE_WORKFLOW'] )
        {
            return eZWorkflowType::STATUS_ACCEPTED;
        }
        
        $db = eZDB::instance();
        
        if ( $process->attribute( 'event_state' ) == 0 )
        {
            $db->begin();
            
            $process->setAttribute( 'event_state', xrowTranslationManagement::STATUS_UNTRANSLATED );
            
            $collaborationItem = xrowTranslationManager::startTranslationProcess( $process );
            
            $process->setParameters( $process->ParameterList );
            $process->store();
            
            $db->commit();
            
           xrowTranslationManager::sendMail2( $process );            
            
            $process->Template = array( 
                'templateName' => 'design:workflow/eventtype/result/event_eztranslationcollection_success.tpl' , 
                'templateVars' => array( 
                    'request_uri' => $requestUri , 
                    'parameters' => $parameters , 
                    'event' => $event , 
                    "process" => $process , 
                    "draft" => $draft , 
                    'object' => $object , 
                    'deadline' => $deadline 
                ) , 
                'path' => array( 
                    array( 
                        'url' => false , 
                        'text' => 'Translation Manager' 
                    ) 
                ) 
            );
            
            return eZWorkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
        }
        if ( $process->attribute( 'event_state' ) == xrowTranslationManagement::STATUS_ACCEPTED )
        {
            
            $collaborationItem = xrowTranslationManagement::collaborationItem( $process->attribute( 'id' ) );
            
            $links = eZCollaborationItemGroupLink::fetchObjectList( eZCollaborationItemGroupLink::definition(), null, array( 
                'collaboration_id' => $collaborationItem->attribute( 'id' ) 
            ) );
            
            foreach ( $links as $link )
            {
                $link->remove();
                $group = TranslationCollaborationHandler::getGroup( $link->attribute( 'user_id' ), TranslationCollaborationHandler::MESSAGE_ARCHIVE );
                eZCollaborationItemGroupLink::addItem( $group->attribute( 'id' ), $collaborationItem->attribute( 'id' ), $link->attribute( 'user_id' ) );
            
            }
            
            $collaborationItem->setAttribute( 'data_int3', xrowTranslationManagement::STATUS_ACCEPTED );
            $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_INACTIVE );
            $timestamp = time();
            $collaborationItem->setAttribute( 'modified', $timestamp );
            $collaborationItem->setIsActive( false );
            $collaborationItem->store();
            
            $participantList[] = array( 
                'id' => $collaborationItem->attribute( 'creator_id' ) , 
                'role' => TranslationCollaborationHandler::PARTICIPANT_ROLE_EDITOR 
            );
            
            if ( $parameters['object_id'] )
                return eZWorkflowType::STATUS_ACCEPTED;
            
            xrowTranslationManager::publish( $process );
            
            return eZWorkflowType::STATUS_ACCEPTED;
        }
        if ( $process->attribute( 'event_state' ) == xrowTranslationManagement::STATUS_REJECTED )
        {
            $collaborationItem = xrowTranslationManagement::collaborationItem( $process->attribute( 'id' ) );
            $collaborationItem->setAttribute( 'data_int3', xrowTranslationManagement::STATUS_REJECTED );
            $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_INACTIVE );
            $collaborationItem->store();
            foreach ( $parameters['versions'] as $contentObjectID => $contentObjectVersion )
            {
                $contentObjectVersion = eZContentObjectVersion::fetchVersion( $contentObjectVersion, $contentObjectID );
                if ( ! is_object( $contentObjectVersion ) )
                    continue;
                $contentObjectVersion->setAttribute( "status", eZContentObjectVersion::STATUS_REJECTED );
                $contentObjectVersion->store();
            }
            return eZWorkflowType::STATUS_WORKFLOW_DONE;
        }
        if ( $process->attribute( 'event_state' ) == xrowTranslationManagement::STATUS_UNTRANSLATED )
        {
            #improve here if you need to send email if each language is completed 
            $report = xrowTranslationManagement::fetchStatusReportByProcess( $process );
            $db->begin();           
            if ( count( $report ) > 0 and count( $report[xrowTranslationManagement::STATUS_UNCONFIRMED] ) == 0 and count( $report[xrowTranslationManagement::STATUS_UNTRANSLATED] ) == 0 )
            {
                
                
                $participantList = array();
                
                foreach ( $report[xrowTranslationManagement::STATUS_TRANSLATED] as $item )
                {
                    $item->setAttribute( 'status', xrowTranslationManagement::STATUS_WAITING_FOR_APPROVAL );
                    $item->store();
                    $participantList[$item->attribute( 'approver_id' )] = array( 
                        'id' => $item->attribute( 'approver_id' ) , 
                        'role' => TranslationCollaborationHandler::PARTICIPANT_ROLE_TRANSLATION_APPROVER 
                    );
                }
                TranslationCollaborationHandler::addApprovers( $process, $participantList );
                
                $collaborationItem = xrowTranslationManagement::collaborationItem( $process->attribute( 'id' ) );
                
                $collaborationItem->setAttribute( 'data_int3', xrowTranslationManagement::STATUS_WAITING_FOR_APPROVAL );
                $collaborationItem->store();
                
                $process->setAttribute( 'event_state', xrowTranslationManagement::STATUS_WAITING_FOR_APPROVAL );
                $process->store();
                foreach ( $parameters['approvers'] as $language => $userID )
                {
                    $options = array();
                    $options = array( 
                        'confirmed' => true , 
                        'all' => true , 
                        'approver' => $userID[0] , 
                        'target' => $language 
                    );
                    
                   xrowTranslationManager::sendMail2( $process, $options );
                
                }
                
                $db->commit();
            
            }
            else//perhaps there has to be done a little more difference between the several status
            {
                $collaborationItem = xrowTranslationManagement::collaborationItem( $process->attribute( 'id' ) );
                
                $collaborationItem->setAttribute( 'data_int3', xrowTranslationManagement::STATUS_UNTRANSLATED );
                $collaborationItem->store();
                
                $process->setAttribute( 'event_state', xrowTranslationManagement::STATUS_UNTRANSLATED );
                $process->store();                
            
            }
            $db->commit();
            
            return eZWorkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
        }
        if ( $process->attribute( 'event_state' ) == xrowTranslationManagement::STATUS_WAITING_FOR_APPROVAL )
        {
            $collaborationItem = xrowTranslationManagement::collaborationItem( $process->attribute( 'id' ) );
            $status = $collaborationItem->attribute( 'data_int3' );
            $report = xrowTranslationManagement::fetchStatusReportByProcess( $process );
            $db->begin();
            if ( $status == xrowTranslationManagement::STATUS_WAITING_FOR_EDITOR_APPROVAL )
            {
                $process->setAttribute( 'event_state', xrowTranslationManagement::STATUS_WAITING_FOR_EDITOR_APPROVAL );
                $process->store();
                $collaborationItem->setAttribute( 'data_int3', xrowTranslationManagement::STATUS_WAITING_FOR_EDITOR_APPROVAL );
                $collaborationItem->store();
                
                $options = array( 
                    'waiting' => true , 
                    'all' => true , 
                    'publisher' => $parameters['user_id'] 
                );
                
               xrowTranslationManager::sendMail2( $process, $options );
            
            }
            elseif ( count( $report ) > 0 and count( $report[xrowTranslationManagement::STATUS_WAITING_FOR_APPROVAL] ) == 0 && $status != xrowTranslationManagement::STATUS_WAITING_FOR_EDITOR_APPROVAL )
            {
                
                $process->setAttribute( 'event_state', xrowTranslationManagement::STATUS_ACCEPTED );
                $process->store();
                $collaborationItem->setAttribute( 'data_int3', xrowTranslationManagement::STATUS_ACCEPTED );
                $collaborationItem->store();
                
                $options = array( 
                    'approved' => true , 
                    'all' => true , 
                    'publisher' => $parameters['user_id'] 
                );
                
               xrowTranslationManager::sendMail2( $process, $options );
            
            }
            
            $db->commit();
            return eZWorkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
        }
        
        return eZWorkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
    
    }
}

eZWorkflowEventType::registerEventType( eZTranslationCollectionType::WORKFLOW_TYPE_STRING, "eZTranslationCollectionType" );

?>
