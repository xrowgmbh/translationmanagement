<?php

/**
 * @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
 * @license [EXTENSION_LICENSE]
 * @version [EXTENSION_VERSION]
 * @package translationmanagement
 */
class TranslationCollaborationHandler extends eZCollaborationItemHandler
{
    const MESSAGE_INBOX = 'Translation Inbox';
    const MESSAGE_ARCHIVE = 'Translation Archive';
    const PARTICIPANT_ROLE_TRANSLATOR = 1030;
    const PARTICIPANT_ROLE_EDITOR = 1031;
    const PARTICIPANT_ROLE_TRANSLATION_APPROVER = 1032;
    const TRANSLATION_IDENTIFIER = 'translation';

    /*!
     Initializes the handler
    */
    function TranslationCollaborationHandler()
    {
        $this->eZCollaborationItemHandler( TranslationCollaborationHandler::TRANSLATION_IDENTIFIER, ezpI18n::tr( 'extension/tm', 'Translation' ), array( 
            'use-messages' => true , 
            'notification-types' => true , 
            'notification-collection-handling' => eZCollaborationItemHandler::NOTIFICATION_COLLECTION_PER_PARTICIPATION_ROLE 
        ) );
    }

    /*!
     \reimp
    */
    function roleName( $collaborationID, $roleID )
    {
        $map = array();
        $map[TranslationCollaborationHandler::PARTICIPANT_ROLE_TRANSLATOR] = ezpI18n::tr( 'extension/tm', 'Translator' );
        $map[TranslationCollaborationHandler::PARTICIPANT_ROLE_EDITOR] = ezpI18n::tr( 'extension/tm', 'Editor' );
        $map[TranslationCollaborationHandler::PARTICIPANT_ROLE_TRANSLATION_APPROVER] = ezpI18n::tr( 'extension/tm', 'Translation Checker' );
        return $map[$roleID];
    }

    /*!
     \reimp
    */
    function title( $collaborationItem )
    {
        return ezpI18n::tr( 'extension/tm', 'Translation' );
    }

    /*!
     \reimp
    */
    function content( $collaborationItem )
    {
        $process_id = $collaborationItem->attribute( "data_int1" );
        $process = eZWorkflowProcess::fetch( $process_id );
        if ( $process )
        {
            $param = $process->attribute( 'parameter_list' );
            $deadline = new eZDate( $param['deadline'] );
            $source = eZContentLanguage::fetchByLocale( $param['source'] );
        }
        $content = array( 
            "process_id" => $process_id , 
            "process" => $process , 
            "source" => $source , 
            "deadline" => $deadline , 
            'languages' => xrowTranslationManagement::fetchAssignedLanguages( $process_id ) , 
            'errors' => array() , 
            "approval_status" => $collaborationItem->attribute( "data_int3" ) 
        );
        if ( isset( $_SESSION['DOM_ERRORS'] ) )
        {
            $content['errors'] = $_SESSION['DOM_ERRORS'];
            unset( $_SESSION['DOM_ERRORS'] );
        }
        return $content;
    }

    function notificationParticipantTemplate( $participantRole )
    {
        if ( $participantRole == TranslationCollaborationHandler::PARTICIPANT_ROLE_TRANSLATOR )
        {
            return 'translator';
        }
        else 
            if ( $participantRole == TranslationCollaborationHandler::PARTICIPANT_ROLE_EDITOR )
            {
                return 'editor';
            }
            else 
                if ( $participantRole == TranslationCollaborationHandler::PARTICIPANT_ROLE_TRANSLATION_APPROVER )
                {
                    return 'approver';
                }
                else
                    return false;
    }

    /*!
     \return the content object version object for the collaboration item \a $collaborationItem
    */
    function contentObjectVersions( $collaborationItem )
    {
        /* Make it abtrakt later
        $result = array();
        foreach ()
        {
            
        }
        $contentObjectID = $collaborationItem->contentAttribute( 'content_object_id' );
        $contentObjectVersion = $collaborationItem->contentAttribute( 'content_object_version' );
         eZContentObjectVersion::fetchVersion( $contentObjectVersion, $contentObjectID );
    */
    }

    /*!
     \reimp
     Updates the last_read for the participant link.
    */
    function readItem( $collaborationItem, $viewMode = false )
    {
        $collaborationItem->setLastRead();
    }

    /*!
     \reimp
     \return the number of messages for the approve item.
    */
    function messageCount( $collaborationItem )
    {
        return eZCollaborationItemMessageLink::fetchItemCount( array( 
            'item_id' => $collaborationItem->attribute( 'id' ) 
        ) );
    }

    /*!
     \reimp
     \return the number of unread messages for the approve item.
    */
    function unreadMessageCount( $collaborationItem )
    {
        $lastRead = 0;
        $status = $collaborationItem->attribute( 'user_status' );
        if ( $status )
            $lastRead = $status->attribute( 'last_read' );
        return eZCollaborationItemMessageLink::fetchItemCount( array( 
            'item_id' => $collaborationItem->attribute( 'id' ) , 
            'conditions' => array( 
                'modified' => array( 
                    '>' , 
                    $lastRead 
                ) 
            ) 
        ) );
    }

    /*!
     \static
     \return the status of the approval collaboration item \a $approvalID.
    */
    function checkApproval( $approvalID )
    {
        $collaborationItem = eZCollaborationItem::fetch( $approvalID );
        if ( $collaborationItem !== null )
        {
            return $collaborationItem->attribute( 'data_int3' );
        }
        return false;
    }

    /*!
     \static
     \return makes sure the approval item is activated for all participants \a $approvalID.
    */
    function activateApproval( $approvalID )
    {
        $collaborationItem = eZCollaborationItem::fetch( $approvalID );
        if ( $collaborationItem !== null )
        {
            //             eZDebug::writeDebug( $collaborationItem, "reactivating approval $approvalID" );
            $collaborationItem->setAttribute( 'data_int3', eZApproveCollaborationHandler::STATUS_WAITING );
            $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_ACTIVE );
            $timestamp = time();
            $collaborationItem->setAttribute( 'modified', $timestamp );
            $collaborationItem->store();
            $participantList = & eZCollaborationItemParticipantLink::fetchParticipantList( array( 
                'item_id' => $approvalID 
            ) );
            for ( $i = 0; $i < count( $participantList ); ++ $i )
            {
                $participantLink = & $participantList[$i];
                $collaborationItem->setIsActive( true, $participantLink->attribute( 'participant_id' ) );
            }
            return true;
        }
        return false;
    }

    static function getGroup( $participantID, $message_box = TranslationCollaborationHandler::MESSAGE_INBOX )
    {
        $conds = array( 
            'parent_group_id' => 0 , 
            'user_id' => $participantID , 
            'title' => $message_box 
        );
        $group = eZCollaborationGroup::fetchObject( eZCollaborationGroup::definition(), null, $conds );
        
        if ( ! is_object( $group ) )
            $group = eZCollaborationGroup::instantiate( $participantID, $message_box );
        return $group;
    }

    /*!
     Creates a new approval collaboration item which will approve the content object \a $contentObjectID
     with version \a $contentObjectVersion.
     The item will be added to the author \a $authorID and the approver \a $approverID.
     \return the collaboration item.
    */
    static function createCollaboration( eZWorkflowProcess $process, $authorID, $approverID )
    {
        $collaborationItem = eZCollaborationItem::create( 'translation', $authorID );
        $collaborationItem->setAttribute( 'data_int1', $process->attribute( 'id' ) );
        $ini = eZINI::instance();
        
        $collaborationItem->setAttribute( "data_text2", eZSys::hostname() . eZSys::indexDir() );
        $collaborationItem->store();
        $collaborationID = $collaborationItem->attribute( 'id' );
        $participantList = array();
        $participantList[] = array( 
            'id' => $authorID , 
            'role' => TranslationCollaborationHandler::PARTICIPANT_ROLE_EDITOR 
        );
        if ( is_array( $approverID ) )
        {
            foreach ( $approverID as $item )
            {
                $participantList[] = array( 
                    'id' => $item , 
                    'role' => TranslationCollaborationHandler::PARTICIPANT_ROLE_TRANSLATOR 
                );
            }
        }
        else
        {
            
            $participantList[] = array( 
                'id' => $item , 
                'role' => TranslationCollaborationHandler::PARTICIPANT_ROLE_TRANSLATOR 
            );
        }
        foreach ( $participantList as $participantItem )
        {
            $participantID = $participantItem['id'];
            $participantRole = $participantItem['role'];
            $link = eZCollaborationItemParticipantLink::create( $collaborationID, $participantID, $participantRole, eZCollaborationItemParticipantLink::TYPE_USER );
            $link->store();
            
            $group = TranslationCollaborationHandler::getGroup( $participantID, TranslationCollaborationHandler::MESSAGE_INBOX );
            
            eZCollaborationItemGroupLink::addItem( $group->attribute( 'id' ), $collaborationID, $participantID );
        }
        $participantList = array_slice( $participantList, 1 );
        // Create the notification
        #TranslationCollaborationHandler::sendMessage( 'translate', $participantList, $collaborationItem );
        #$event = $collaborationItem->createNotificationEvent();
        return $collaborationItem;
    }

    static function addApprovers( $process, $participantList = array() )
    {
        $collaborationItem = xrowTranslationManagement::collaborationItem( $process->attribute( 'id' ) );
        
        foreach ( $participantList as $participantItem )
        {
            
            $participantID = $participantItem['id'];
            $participantRole = $participantItem['role'];
            
            $link = eZCollaborationItemParticipantLink::create( $collaborationItem->attribute( 'id' ), $participantID, $participantRole, eZCollaborationItemParticipantLink::TYPE_USER );
            $link->store();
            
            $group = TranslationCollaborationHandler::getGroup( $participantID, TranslationCollaborationHandler::MESSAGE_INBOX );
            
            eZCollaborationItemGroupLink::addItem( $group->attribute( 'id' ), $collaborationItem->attribute( 'id' ), $participantID );
        }
    }

    /*!
     \reimp
     Adds a new comment, approves the item or denies the item.
    */
    function handleCustomAction( $module, $collaborationItem )
    {
        
        $db = eZDB::instance();
        
        $redirectView = 'item';
        $redirectParameters = array( 
            'full' , 
            $collaborationItem->attribute( 'id' ) 
        );
        $addComment = false;
        $content = $collaborationItem->attribute( 'content' );
        $parameters = $content['process']->parameterList();
        $http = eZHTTPTool::instance();
        
        $db->begin();
        
        if ( $this->isCustomAction( 'Comment' ) )
        {
            $addComment = true;
        }
        elseif ( $this->isCustomAction( 'Confirm' ) )
        {
            $ObjectID = $http->postVariable( 'ObjectID' );
            $VersionID = $http->postVariable( 'VersionID' );
            
            $tm = xrowTranslationManagement::fetch( $ObjectID, $VersionID, $http->postVariable( 'Target' ) );
            $process = eZWorkflowProcess::fetch( $tm->attribute( 'process_id' ) );
            
            if ( $tm->attribute( 'status' ) == xrowTranslationManagement::STATUS_UNCONFIRMED )
            {
                $tm->setAttribute( 'status', xrowTranslationManagement::STATUS_TRANSLATED );
                $tm->store();
            }
            else
            {
                $errors['confirm'] = true;
            }
            if ( $http->hasPostVariable( "RedirectURIAfterPublish" ) )
            {
                $module->redirectTo( $http->PostVariable( "RedirectURIAfterPublish" ) );
            }
        }
        elseif ( $this->isCustomAction( 'ConfirmAll' ) )
        {
            $process = eZWorkflowProcess::fetch( $collaborationItem->attribute( "data_int1" ) );
            $report = xrowTranslationManagement::fetchStatusReportByProcess( $process );
            
            if ( count( $report ) > 0 and count( $report[xrowTranslationManagement::STATUS_UNCONFIRMED] ) > 0 )
            {
                $parameters = $process->parameterList();
                
                foreach ( $parameters['versions'] as $contentObjectID => $contentObjectVersion )
                {
                    $tm = xrowTranslationManagement::fetch( $contentObjectID, $contentObjectVersion, $http->postVariable( 'language' ) );
                    
                    if ( is_object( $tm ) and $tm->attribute( 'status' ) == xrowTranslationManagement::STATUS_UNCONFIRMED )
                    {
                        $tm->setAttribute( 'status', xrowTranslationManagement::STATUS_TRANSLATED );
                        $tm->store();
                    }
                }
            }
        }
        elseif ( $this->isCustomAction( 'Approve' ) )
        {
            $ObjectID = $http->postVariable( 'ObjectID' );
            $VersionID = $http->postVariable( 'VersionID' );
            
            $tm = xrowTranslationManagement::fetch( $ObjectID, $VersionID, $http->postVariable( 'Target' ) );
            $process = eZWorkflowProcess::fetch( $tm->attribute( 'process_id' ) );
            $collaborationItem = xrowTranslationManagement::collaborationItem( $process->attribute( 'id' ) );
            
            $report = xrowTranslationManagement::fetchStatusReportByProcess( $process );
            
            if ( $tm->attribute( 'status' ) == xrowTranslationManagement::STATUS_WAITING_FOR_APPROVAL or count( $report[xrowTranslationManagement::STATUS_WAITING_FOR_APPROVAL] ) > 0 )
            {
                if ( $parameters['auto_accept'] == true )
                {
                    $tm->setAttribute( 'status', xrowTranslationManagement::STATUS_APPROVED );
                
                }
                else
                {
                    $tm->setAttribute( 'status', xrowTranslationManagement::STATUS_WAITING_FOR_EDITOR_APPROVAL );
                    $collaborationItem->setAttribute( 'data_int3', xrowTranslationManagement::STATUS_WAITING_FOR_EDITOR_APPROVAL );
                }
                $tm->store();
                $collaborationItem->store();
            
            }
        }
        elseif ( $this->isCustomAction( 'ApproveAll' ) )
        {
            $process = eZWorkflowProcess::fetch( $collaborationItem->attribute( "data_int1" ) );
            $report = xrowTranslationManagement::fetchStatusReportByProcess( $process );
            
            if ( count( $report ) > 0 and count( $report[xrowTranslationManagement::STATUS_WAITING_FOR_APPROVAL] ) > 0 )
            {
                $parameters = $process->parameterList();
                
                foreach ( $parameters['versions'] as $contentObjectID => $contentObjectVersion )
                {
                    $tm = xrowTranslationManagement::fetch( $contentObjectID, $contentObjectVersion, $http->postVariable( 'language' ) );
                    if ( is_object( $tm ) and $tm->attribute( 'status' ) == xrowTranslationManagement::STATUS_WAITING_FOR_APPROVAL )
                    {
                        if ( $parameters['auto_accept'] == true )
                        {
                            $tm->setAttribute( 'status', xrowTranslationManagement::STATUS_APPROVED );
                        
                        }
                        else
                        {
                            $tm->setAttribute( 'status', xrowTranslationManagement::STATUS_WAITING_FOR_EDITOR_APPROVAL );
                            $collaborationItem->setAttribute( 'data_int3', xrowTranslationManagement::STATUS_WAITING_FOR_EDITOR_APPROVAL );
                        }
                        $tm->store();
                        $collaborationItem->store();
                    }
                }
            }
        }
        elseif ( $this->isCustomAction( 'Download' ) )
        {
            $tm = new xrowTranslationManager( $options );
            $tm->downloadFile( $parameters['object_ids'], null, $parameters['source'], $http->postVariable( 'target' ) );
        
        }
        elseif ( $this->isCustomAction( 'Upload' ) )
        {
            set_time_limit( 12 * 60 );
            $httpFileName = "file";
            if ( eZHTTPFile::canFetch( $httpFileName ) )
            {
                $httpFile = eZHTTPFile::fetch( $httpFileName );
                if ( $httpFile )
                {
                    if ( $httpFile->attribute( "mime_type" ) == "text/xml" )
                    {
                        $tm = new xrowTranslationManager();
                        try
                        {
                            $tm->storeFile( $httpFile->attribute( "filename" ) );
                        }
                        catch ( Exception $e )
                        {
                            $errors[] = (string) $e;
                        }
                    
                    }
                    else
                    {
                        $errors[] = "Mime type is not text/xml.";
                    }
                }
                else
                {
                    $errors[] = "File not found.";
                }
            }
            else
            {
                $errors[] = "File not found.";
            }
            if ( $errors )
            {
                $redirectParameters['errors'] = $errors;
            }
        }
        elseif ( $this->isCustomAction( 'Reopen' ) )
        {
            $ObjectID = $http->postVariable( 'ObjectID' );
            $VersionID = $http->postVariable( 'VersionID' );
            
            $tm = xrowTranslationManagement::fetch( $ObjectID, $VersionID, $http->postVariable( 'Target' ) );
            $process = eZWorkflowProcess::fetch( $tm->attribute( 'process_id' ) );
            
            $tm->setAttribute( 'status', xrowTranslationManagement::STATUS_UNTRANSLATED );
            $tm->store();
            $process->setAttribute( 'event_state', xrowTranslationManagement::STATUS_UNTRANSLATED ); //xrowTranslationManagement::STATUS_UNTRANSLATED
            $process->store();
            
            $options = array( 
                'unapproved' => true 
            );
            
            xrowTranslationManager::sendMail2( $process, $options );
            
            if ( $http->hasPostVariable( "RedirectURIAfterPublish" ) )
            {
                $module->redirectTo( $http->PostVariable( "RedirectURIAfterPublish" ) );
            }
        }
        else
        {
            if ( $this->isCustomAction( 'Accept' ) or $this->isCustomAction( 'Deny' ) or $this->isCustomAction( 'Defer' ) )
            {
                $status = eZApproveCollaborationHandler::STATUS_DENIED;
                
                if ( $this->isCustomAction( 'Accept' ) )
                {
                    
                    $process = eZWorkflowProcess::fetch( $collaborationItem->attribute( "data_int1" ) );
                    $report = xrowTranslationManagement::fetchStatusReportByProcess( $process );
                    
                    if ( count( $report[xrowTranslationManagement::STATUS_UNTRANSLATED] ) > 0 )//evtl. noch STATUS_UNCONFIRMEND?!
                    {
                        
                        foreach ( $report[xrowTranslationManagement::STATUS_UNTRANSLATED] as $xrowTMObject )
                        {
                            $version = eZContentObjectVersion::fetchVersion( $xrowTMObject->attribute( 'contentobject_version' ), $xrowTMObject->attribute( 'contentobject_id' ), true );
                            $version->removeTranslation( $xrowTMObject->attribute( 'language' ) );
                        }
                    }
                    
                    $status = xrowTranslationManagement::STATUS_ACCEPTED;
                    $content['process']->setAttribute( 'event_state', xrowTranslationManagement::STATUS_ACCEPTED );
                    $content['process']->store();
                }
                else
                {
                    if ( $this->isCustomAction( 'Defer' ) or $this->isCustomAction( 'Deny' ) )
                    {
                        $content['process']->setAttribute( 'event_state', xrowTranslationManagement::STATUS_REJECTED );
                        $content['process']->store();
                        $status = xrowTranslationManagement::STATUS_REJECTED;
                    
                    }
                }
                $links = eZCollaborationItemGroupLink::fetchObjectList( eZCollaborationItemGroupLink::definition(), null, array( 
                    'collaboration_id' => $collaborationItem->attribute( 'id' ) 
                ) );
                
                foreach ( $links as $link )
                {
                    $group = TranslationCollaborationHandler::getGroup( $link->attribute( 'user_id' ), TranslationCollaborationHandler::MESSAGE_ARCHIVE );
                    eZCollaborationItemGroupLink::addItem( $group->attribute( 'id' ), $collaborationItem->attribute( 'id' ), $link->attribute( 'user_id' ) );
                    $link->remove();
                }
                
                $collaborationItem->setAttribute( 'data_int3', $status );
                $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_INACTIVE );
                $timestamp = time();
                $collaborationItem->setAttribute( 'modified', $timestamp );
                $collaborationItem->setIsActive( false );
                
                $redirectView = 'view';
                $redirectParameters = array( 
                    'summary' 
                );
                $addComment = true;
            }
        }
        if ( $addComment )
        {
            $messageText = $this->customInput( 'ApproveComment' );
            if ( trim( $messageText ) != '' )
            {
                $user = eZUser::currentUser();
                $co = $user->contentobject();
                $ini = eZINI::instance();
                $message = eZCollaborationSimpleMessage::create( 'translation_comment', $messageText );
                $message->setAttribute( "data_text2", $co->name() );
                $message->store();
                
                eZCollaborationItemMessageLink::addMessage( $collaborationItem, $message, eZApproveCollaborationHandler::MESSAGE_TYPE_APPROVE );
                
                $participantList = eZCollaborationItemParticipantLink::fetchParticipantList( array( 
                    'item_id' => $collaborationItem->attribute( 'id' ) , 
                    'participant_type' => eZCollaborationItemParticipantLink::TYPE_USER , 
                    'as_object' => false 
                ) );
                
                TranslationCollaborationHandler::sendMessage( "comment", $participantList, $collaborationItem, $message );
            }
        }
        $collaborationItem->sync();
        $db->commit();
        return $module->redirectToView( $redirectView, $redirectParameters );
    }

    static function sendMessage( $type, $participantList, $collaborationItem = null, $message = null )
    {
        $userIDList = array();
        $participantMap = array();
        /*foreach ( $participantList as $participant )
        {
            $userIDList[] = $participant['participant_id'];
        }

        $ruleList = eZCollaborationNotificationRule::fetchItemTypeList( 'translation', $userIDList, false );
        $userIDList = array();
        foreach ( $ruleList as $rule )
        {
            $userIDList[] = $rule['user_id'];
        }
        $userList = array();*/
        $tpl = eZTemplate::factory();
        $mailCollections = array();
        foreach ( $participantList as $participant )
        {
            #if ( !in_array( $participant['participant_id'], $userIDList) )
            #    continue;
            $event = eZNotificationEvent::create( 'comment', array( 
                'collaboration_id' => $collaborationItem->attribute( 'id' ) , 
                'collaboration_identifier' => 'translation_comment' 
            ) );
            $event->store();
            
            $tpl->resetVariables();
            $tpl->setVariable( 'collaboration_item', $collaborationItem );
            $tpl->setVariable( 'message', $message );
            $content = $collaborationItem->attribute( 'content' );
            $parameters = $content['process']->parameterList();
            $tpl->setVariable( 'parameters', $parameters );
            $user = eZUser::fetch( $participant['participant_id'] );
            $tpl->setVariable( 'user', $user );
            $result = $tpl->fetch( 'design:notification/handler/ezcollaboration/view/translation/' . $type . '.tpl' );
            $subject = $tpl->variable( 'subject' );
            if ( ! isset( $mailCollections[$participant['participant_role']] ) )
            {
                $collection = eZNotificationCollection::create( $event->attribute( 'id' ), eZCollaborationNotificationHandler::NOTIFICATION_HANDLER_ID, 'ezcmail' );
                /*$collection = eZNotificationCollection::create( $event->attribute( 'id' ),
                                                                eZCollaborationNotificationHandler::NOTIFICATION_HANDLER_ID,
                                                                eZCollaborationNotificationHandler::TRANSPORT );*/
                $collection->setAttribute( 'data_subject', $subject );
                $collection->setAttribute( 'data_text', $result );
                $collection->store();
                $mailCollections[$participant['participant_role']] = $collection;
            }
            
            $mailCollections[$participant['participant_role']]->addItem( $user->Email );
        }
    }

    function forwardCollaborationSimpleMessage( &$message, &$collaborationItem )
    {
        $event = eZNotificationEvent::create( 'comment', array( 
            'collaboration_id' => $collaborationItem->attribute( 'id' ) , 
            'collaboration_identifier' => "translation_comment" 
        ) );
        
        $event->setAttribute( "data_int4", $message->attribute( 'id' ) );
        $event->store();
        
        $participantList = eZCollaborationItemParticipantLink::fetchParticipantList( array( 
            'item_id' => $collaborationItem->attribute( 'id' ) , 
            'participant_type' => eZCollaborationItemParticipantLink::TYPE_USER , 
            'as_object' => false 
        ) );
        
        $userIDList = array();
        $participantMap = array();
        foreach ( $participantList as $participant )
        {
            $userIDList[] = $participant['participant_id'];
            $participantMap[$participant['participant_id']] = $participant;
        }
        
        $ruleList = eZCollaborationNotificationRule::fetchItemTypeList( "translation", $userIDList, false );
        $userIDList = array();
        foreach ( $ruleList as $rule )
        {
            $userIDList[] = $rule['user_id'];
        }
        $userList = array();
        if ( count( $userIDList ) > 0 )
        {
            $db = eZDB::instance();
            $userIDListText = implode( "', '", $userIDList );
            $userIDListText = "'$userIDListText'";
            $userList = $db->arrayQuery( "SELECT contentobject_id, email FROM ezuser WHERE contentobject_id IN ( $userIDListText )" );
        }
        else
        {
            return eZNotificationEventHandler::EVENT_SKIPPED;
        }
        $tpl = eZTemplate::factory();
        $tpl->resetVariables();
        $tpl->setVariable( 'collaboration_item', $collaborationItem );
        $tpl->setVariable( 'message', $message );
        $result = $tpl->fetch( 'design:notification/handler/ezcollaboration/view/translation/comment.tpl' );
        $subject = $tpl->variable( 'subject' );
        if ( $tpl->hasVariable( 'message_id' ) )
            $parameters['message_id'] = $tpl->variable( 'message_id' );
        if ( $tpl->hasVariable( 'references' ) )
            $parameters['references'] = $tpl->variable( 'references' );
        if ( $tpl->hasVariable( 'reply_to' ) )
            $parameters['reply_to'] = $tpl->variable( 'reply_to' );
        if ( $tpl->hasVariable( 'from' ) )
            $parameters['from'] = $tpl->variable( 'from' );
        
        $collection = eZNotificationCollection::create( $event->attribute( 'id' ), eZCollaborationNotificationHandler::NOTIFICATION_HANDLER_ID, eZCollaborationNotificationHandler::TRANSPORT );
        
        $collection->setAttribute( 'data_subject', $subject );
        $collection->setAttribute( 'data_text', $result );
        $collection->store();
        
        foreach ( $userList as $subscriber )
        {
            $collection->addItem( $subscriber['email'] );
        }
    }

    function setAttribute( $name, $value )
    {
        if ( $name == 'status' )
        {
            return parent::setAttribute( 'data_int3', $value );
        }
        return parent::setAttribute( $name, $value );
    }
}

?>
