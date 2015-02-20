<?php

/**
 * @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
 * @license [EXTENSION_LICENSE]
 * @version [EXTENSION_VERSION]
 * @package translationmanagement
 */

class eztmfunctioncollection
{

    function fetchParticipantMap( $itemID, $offset, $limit )
    {
        include_once ( 'kernel/classes/ezcollaborationitemparticipantlink.php' );
        $itemParameters = array( 
            'item_id' => $itemID , 
            'offset' => $offset , 
            'limit' => $limit 
        );
        
        $children = xrowTranslationManager::fetchParticipantList( $itemParameters );
        if ( $children === null )
        {
            $resultArray = array( 
                'error' => array( 
                    'error_type' => 'kernel' , 
                    'error_code' => eZError::KERNEL_NOT_FOUND 
                ) 
            );
        }
        else
        {
            $resultArray = array( 
                'result' => &$children 
            );
        }
        return $resultArray;
    }

    function collaborationItem( $contentobject_id, $version )
    {
        $return = xrowTranslationManager::getCollaboration( $contentobject_id, $version );
        if ( is_object( $return ) )
            return array( 
                'result' => $return 
            );
        else
            return array( 
                'result' => false 
            );
    }

    function fetchProcessByProcessID( $process_id )
    {
        return array( 
            'result' => eZWorkflowProcess::fetch( $process_id ) 
        );
    } /*
    function fetchUserObjectsByProcessID( $process_id )
    {
        $process = eZWorkflowProcess::fetch( $process_id );
        $user = eZUser::currentUserID();
        $list = $process->parameterList();
        $conds = array();
        xrowTranslationManagement::fetchObjectList( xrowTranslationManagement::definition(), null, $conds );
        return array( 'result' => eZContentObject::fetchIDArray( $list['object_ids'] ) ); 
    }*/

    function fetchObjectsByProcessID( $process_id, $sort, $sort_method )
    {
        $process = eZWorkflowProcess::fetch( $process_id );
        if ( ! is_object( $process ) )
            return array( 
                'result' => false 
            );
        $list = $process->parameterList();
        $objects = eZContentObject::fetchIDArray( $list['object_ids'] );
        
        $firstkey = array_keys( $objects );
        if ( $sort and $objects[$firstkey[0]]->hasAttribute( $sort ) )
        {
            if ( $sort_method = "asc" )
                $compare = create_function( '$a,$b', 'return strcmp( $a->attribute( "' . $sort . '" ), $b->attribute( "' . $sort . '" ) );' );
            else
                $compare = create_function( '$a,$b', 'return ( strcmp( $a->attribute( "' . $sort . '" ), $b->attribute( "' . $sort . '" ) ) * -1 );' );
            
            usort( $objects, $compare );
        }
        return array( 
            'result' => $objects 
        );
    }

    function fetchTMObjectsByProcessID( $process_id )
    {
        $process = eZWorkflowProcess::fetch( $process_id );
        $list = $process->parameterList();
        return array( 
            'result' => eZContentObject::fetchIDArray( $list['object_ids'] ) 
        );
    }

    function status( $contentobject_id, $version, $language, $process_id )
    {
        if ( ! is_numeric( $contentobject_id ) and ! is_numeric( $version ) )
            return array( 
                'result' => false 
            );
        if ( ! $language )
            $language = null;
        if ( ! $process_id )
            $process_id = null;
        return array( 
            'result' => xrowTranslationManagement::fetch( $contentobject_id, $version, $language, $process_id ) 
        );
    }

    function workObjectStatus( $process_id, $contentobject_id, $contentobject_version )
    {
        $item_id = eztmfunctioncollection::collaborationItem( $contentobject_id, $contentobject_version );
        
        $conds = array( 
            "process_id" => $process_id , 
            "contentobject_id" => $contentobject_id , 
            'contentobject_version' => $contentobject_version 
        );
        
        $tmList = eZPersistentObject::fetchObjectList( xrowTranslationManagement::definition(), null, $conds );        
        $current_participant = new eZCollaborationFunctionCollection();
        
        $current_participant = $current_participant->fetchParticipant( $item_id['result']->attribute( 'id' ), eZUser::currentUserID() );
        
        if ( $current_participant['result']->attribute( 'participant_role' ) == TranslationCollaborationHandler::PARTICIPANT_ROLE_TRANSLATOR )
            $notDone = array( 
                xrowTranslationManagement::STATUS_UNTRANSLATED , 
                xrowTranslationManagement::STATUS_UNCONFIRMED 
            );
        
        elseif ( $current_participant['result']->attribute( 'participant_role' ) == TranslationCollaborationHandler::PARTICIPANT_ROLE_TRANSLATION_APPROVER )
            $notDone = array( 
                xrowTranslationManagement::STATUS_UNTRANSLATED , 
                xrowTranslationManagement::STATUS_UNCONFIRMED , 
                xrowTranslationManagement::STATUS_WAITING_FOR_APPROVAL 
            );
        
        elseif ( $current_participant['result']->attribute( 'participant_role' ) == TranslationCollaborationHandler::PARTICIPANT_ROLE_EDITOR )
            
            $notDone = array( 
                xrowTranslationManagement::STATUS_UNTRANSLATED , 
                xrowTranslationManagement::STATUS_UNCONFIRMED , 
                xrowTranslationManagement::STATUS_WAITING_FOR_APPROVAL , 
                xrowTranslationManagement::STATUS_WAITING_FOR_EDITOR_APPROVAL 
            );
        
        foreach ( $tmList as $tm )
        {
            if ( in_array( $tm->status, $notDone ) )
            {
                return array( 
                    'result' => false 
                );
            }
        }
        return array( 
            'result' => true 
        );
    }

    function users()
    {
        $objectids = array();
        $tmini = ezini::instance( 'translationmanager.ini' );
        $treeParameters = array( 
            'Offset' => 0 , 
            'ClassFilterType' => 'include' , 
            'ClassFilterArray' => array( 
                'user'
            ), 
            'OnlyTranslated' => false , 
            'IgnoreVisibility' => true , 
            'MainNodeOnly' => false 
        );
        $children2 = array();
        
        $node = eZContentObjectTreeNode::fetch( $tmini->variable( 'TranslationSettings', 'TranslatorNode' ) );
        $children = $node->subTree( $treeParameters );
        
        foreach ( $children as $node )
        {
            if ( ! in_array( $node->attribute( 'contentobject_id' ), $objectids ) )
            {
                $objectids[] = $node->attribute( 'contentobject_id' );
                $children2[] = $node;
            }
        }
        
        return array( 
            'result' => $children2 
        );
    }

    function canTranslate( $language, $id )
    {
        
        if ( is_numeric( $id ) and $id > 0 )
            $user = eZUser::fetch( $id );
        if ( ! is_object( $user ) )
            $user = eZUser::currentUser();
        $roles2 = xrowTranslationManager::getTranslationRolesByLanguage( $language );
        $roles = $user->attribute( 'role_id_list' );
        
        foreach ( $roles as $role_id )
        {
            if ( in_array( $role_id, $roles2 ) )
                return array( 
                    'result' => true 
                );
        }
        return array( 
            'result' => false 
        );
    }

    function canApprove( $language, $id )
    {
        if ( is_numeric( $id ) and $id > 0 )
            $user = eZUser::fetch( $id );
        if ( ! is_object( $user ) )
            $user = eZUser::currentUser();
        $roles2 = xrowTranslationManager::getApproveRolesByLanguage( $language );
        $roles = $user->attribute( 'role_id_list' );
        foreach ( $roles as $role_id )
        {
            if ( in_array( $role_id, $roles2 ) )
                return array( 
                    'result' => true 
                );
        }
        return array( 
            'result' => false 
        );
    }

    function availableLanguages( $version )
    {
        if ( ! is_object( $version ) )
            return array( 
                'result' => false 
            );
        return array( 
            'result' => xrowTranslationManager::getObjectVersionLanguages( $version ) 
        );
    }

    function getRolesByLanguage( $lang, $type )
    {
        if ( ! $lang )
            return array( 
                'result' => false 
            );
        if ( $type == 'approve' )
            return array( 
                'result' => xrowTranslationManager::getApproveRolesByLanguage( $lang ) 
            );
        else
            return array( 
                'result' => xrowTranslationManager::getTranslationRolesByLanguage( $lang ) 
            );
    }

    function &objectStatus( $contentobject_id )
    {
        if ( ! is_numeric( $contentobject_id ) )
            return array( 
                'result' => false 
            );
        $draft = xrowTranslationManagement::fetchLastPendingDraft( $contentobject_id );
        if ( ! is_object( $draft ) )
            return array( 
                'result' => false 
            );
        xrowTranslationManagement::fetchList( $contentobject_id, $draft->attribute( 'id' ) );
        return array( 
            'result' => xrowTranslationManagement::fetch( $contentobject_id, $version, $language ) 
        );
    }
}

?>
