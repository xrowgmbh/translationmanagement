<?php
/**
 * @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
 * @license [EXTENSION_LICENSE]
 * @version [EXTENSION_VERSION]
 * @package translationmanagement
 */
class xrowTranslationManager
{
    const NOT_TRANSLATABLE_TAG_NAME = 'noxl';
    const DOM_ROOT_NAME = 'TranslationManagement';
    const DOM_QUALIFIED_NAME = '-//xrow GmbH//DTD TranslationManagement TRADOS V 1.0//EN';
    public $options = array();

    function xrowTranslationManager( $options = null )
    {
        if ( is_array( $options ) )
            $this->options = $options;
    }

    static function versionCheck()
    {
        if ( version_compare( eZPublishSDK::version(), '4.0', 'ge' ) === true )
        {
            return true;
        }
        return false;
    }

    static function fetchParticipantList( $parameters = array() )
    {
        $parameters = array_merge( array( 
            'as_object' => true , 
            'item_id' => false , 
            'offset' => false , 
            'limit' => false , 
            'sort_by' => false 
        ), $parameters );
        $itemID = $parameters['item_id'];
        $asObject = $parameters['as_object'];
        $offset = $parameters['offset'];
        $limit = $parameters['limit'];
        $linkList = null;
        if ( ! $offset and ! $limit )
        {
            $linkList = & $GLOBALS['eZCollaborationItemParticipantLinkListCache'];
            if ( isset( $linkList ) )
                return $linkList;
        }
        $limitArray = null;
        if ( $offset and $limit )
            $limitArray = array( 
                'offset' => $offset , 
                'length' => $limit 
            );
        $linkList = eZPersistentObject::fetchObjectList( eZCollaborationItemParticipantLink::definition(), null, array( 
            "collaboration_id" => $itemID 
        ), null, $limitArray, $asObject );
        
        for ( $i = 0; $i < count( $linkList ); ++ $i )
        {
            $linkItem = & $linkList[$i];
            if ( $asObject )
                $participantID = $linkItem->attribute( 'participant_id' );
            else
                $participantID = & $linkItem['participant_id'];
            if ( $asObject )
                $participantRole = $linkItem->attribute( 'participant_role' );
            else
                $participantRole = & $linkItem['participant_role'];
            if ( ! isset( $GLOBALS["eZCollaborationItemParticipantRoleLinkCache"][$itemID][$participantRole][$participantID] ) )
            {
                $GLOBALS["eZCollaborationItemParticipantRoleLinkCache"][$itemID][$participantRole]['role'] = eZCollaborationItemParticipantLink::roleName( $itemID, $participantRole );
                $GLOBALS["eZCollaborationItemParticipantRoleLinkCache"][$itemID][$participantRole]['items'][$participantID] = & $linkList[$i];
            }
        }
        return $GLOBALS["eZCollaborationItemParticipantRoleLinkCache"][$itemID];
    }

    static function cleanup()
    {
        $db = eZDB::instance();
        $db->query( "UNLOCK TABLES" );
        $db->query( "TRUNCATE ezapprove_items" );
        $db->query( "TRUNCATE ezcollab_group" );
        $db->query( "TRUNCATE ezcollab_item" );
        $db->query( "TRUNCATE ezcollab_item_group_link" );
        $db->query( "TRUNCATE ezcollab_item_message_link" );
        $db->query( "TRUNCATE ezcollab_item_participant_link" );
        $db->query( "TRUNCATE ezcollab_item_status" );
        $db->query( "TRUNCATE ezcollab_profile" );
        $db->query( "TRUNCATE ezcollab_simple_message" );
        $db->query( "TRUNCATE ezworkflow_process" );
        $db->query( "TRUNCATE ezxtranslationmanagement" );
        
        $db->begin();
        $list = eZContentObjectVersion::fetchObjectList( eZContentObjectVersion::definition(), null, array( 
            'status' => EZ_VERSION_STATUS_PENDING 
        ) );
        
        if ( count( $list ) > 0 )
        {
            foreach ( $list as $item )
            {
                $assignments = $item->nodeAssignments();
                foreach ( $assignments as $assignment )
                {
                    $assignment->remove();
                }
                $item->remove();
            }
        }
        
        $db->commit();
    }

    static function leftMenu()
    {
        return "design:parts/translation/menu.tpl";
    }

    static function ObjectIDsFromNodeIDArray( $SourceLanguage, $NodeIDArray )
    {
        
        $children = array();
        $objectids = array();
        if ( is_array( $NodeIDArray ) )
        {
            foreach ( $NodeIDArray as $key => $data )
            {
                if ( ! isset( $data['without_root'] ) or $data['without_root'] == false )
                {
                    $children[] = eZContentObjectTreeNode::fetch( $data['node'] );
                }
                if ( isset( $data['with_children'] ) )
                {
                    $treeParameters = array( 
                        'Offset' => 0 , 
                        'OnlyTranslated' => true , 
                        'Language' => $SourceLanguage , 
                        'IgnoreVisibility' => true , 
                        'MainNodeOnly' => true 
                    );
                    if ( isset( $data['without_hidden'] ) and $data['without_hidden'] )
                    {
                        $treeParameters['IgnoreVisibility'] = false;
                    }
                    $node = eZContentObjectTreeNode::fetch( $data['node'] );
                    $children = array_merge( $children, $node->subTree( $treeParameters ) );
                }
            }
        }
        foreach ( $children as $node )
        {
            $objectids[] = $node->attribute( 'contentobject_id' );
        }
        return array_unique( $objectids );
    }

    static function startTranslationProcess( eZWorkflowprocess $process )
    {
        $db = eZDB::instance();
        $db->begin();
        $parameters = $process->attribute( 'parameter_list' );
        $user = eZUser::currentUser();
        $source = $parameters['source'];
        $translators = $parameters['translators'];
        $approvers = $parameters['approvers'];
        $deadline = new eZDate( $parameters['deadline'] );
        $language_list = eZContentLanguage::fetchLocaleList();
        $language_code_include = $parameters['language_code_include'];
        foreach ( $parameters['object_ids'] as $ObjectID )
        {
            $draft = eZContentObjectVersion::fetchVersion( $parameters['versions'][$ObjectID], $ObjectID );
            if ( ! is_object( $draft ) )
                continue;
            
            $object_language_list = xrowTranslationManager::getObjectVersionLanguages( $draft );
            if ( ! array_key_exists( $source, $object_language_list ) )
                continue;
            
            foreach ( $language_list as $language )
            {
                if ( ! in_array( $language, $language_code_include ) )
                {
                    continue;
                }
                if ( $language == $source )
                {
                    continue;
                }
                // if Collection then always add language if it doesn't exist.
                if ( ! array_key_exists( $language, $object_language_list ) and ! $parameters['object_id'] )
                {
                    
                    xrowTranslationManager::addTranslation( $draft, $language );
                
                }
                else 
                    if ( ! array_key_exists( $language, $object_language_list ) and $parameters['object_id'] )
                    {
                        continue;
                    }
                if ( is_array( $translators[$language] ) and count( $translators[$language] ) > 0 )
                {
                    $translator = $translators[$language][0];
                }
                else
                {
                    $translator = $process->attribute( 'user_id' );
                }
                $translatorsForHandler[] = $translator;
                if ( is_array( $approvers[$language] ) and count( $approvers[$language] ) > 0 )
                {
                    $approver = $approvers[$language][0];
                }
                else
                {
                    $approver = $process->attribute( 'user_id' );
                }
                xrowTranslationManagement::startTranslation( $process->attribute( 'id' ), $draft, $source, $language, $deadline, $translator, $approver );
            }
        }
        
        $translatorsForHandler = array_unique( $translatorsForHandler );
        if ( count( $translatorsForHandler ) > 0 )
        {
            $collaborationItem = TranslationCollaborationHandler::createCollaboration( $process, $process->attribute( 'user_id' ), $translatorsForHandler );
            $collaborationItem->setAttribute( 'data_int3', xrowTranslationManagement::STATUS_UNTRANSLATED );
            $collaborationItem->store();
        }
        $db->commit();
        return $collaborationItem;
    }

    static function getCollaboration( $contentobject_id, $version_id )
    {
        $db = eZDB::instance();
        
        $result = $db->arrayQuery( "SELECT c.* 
            FROM ezcollab_item c, ezxtranslationmanagement tm
            WHERE  tm.contentobject_id='" . (int) $contentobject_id . "' 
                AND tm.contentobject_version = '" . (int) $version_id . "' 
                AND c.data_int1=tm.process_id 
                AND c.type_identifier='" . xrowTranslationManagement::COLLABORATION_TYPE . "'" );
        return eZCollaborationItem::fetch( $result[0]['id'] );
    }

    /*	This function uses a copy of the content/publish operation and executes this operation without triggers.
     * 
     */
    function publish( eZWorkflowProcess $process )
    {
        $GLOBALS['eZ_TM_IGNORE_WORKFLOW'] = true;
        $db = eZDB::instance();
        $db->begin();
        $parameters = $process->parameterList();
        
        foreach ( $parameters['versions'] as $ObjectID => $VersionID )
        {
            if ( ! is_object( eZContentObjectVersion::fetchVersion( $VersionID, $ObjectID ) ) )
            {
                eZDebug::writeError( "Problem with object $ObjectID: No such version $VersionID", "TM" );
                continue;
            }
            $operationResult = eZOperationHandler::execute( 'tm', 'publish', array( 
                'object_id' => $ObjectID , 
                'version' => $VersionID 
            ) );
        }
        $db->commit();
        $GLOBALS['eZ_TM_IGNORE_WORKFLOW'] = false;
    }

    static function addTranslation( eZContentObjectVersion $contentObjectVersion, $language )
    {
        $db = eZDB::instance();
        $db->begin();
        $attributeArray = $contentObjectVersion->contentObjectAttributes( $language );
        if ( count( $attributeArray ) == 0 )
        {
            $hasTranslation = eZContentLanguage::fetchByLocale( $language );
            
            if ( ! $hasTranslation )
            {
                // if there is no needed translation in system then add it
                $locale = eZLocale::instance( $language );
                $translationName = $locale->internationalLanguageName();
                $translationLocale = $locale->localeCode();
                
                if ( $locale->isValid() )
                {
                    eZContentLanguage::addLanguage( $locale->localeCode(), $locale->internationalLanguageName() );
                    $hasTranslation = true;
                }
                else
                    $hasTranslation = false;
            }
            
            if ( $hasTranslation )
            {
                // Add translated attributes for the translation
                $originalContentAttributes = $contentObjectVersion->contentObjectAttributes( $contentObjectVersion->initialLanguageCode() );
                foreach ( array_keys( $originalContentAttributes ) as $contentAttributeKey )
                {
                    $originalContentAttribute = & $originalContentAttributes[$contentAttributeKey];
                    $contentAttribute = $originalContentAttribute->translateTo( $language );
                    $contentAttribute->sync();
                    $attributeArray[] = & $contentAttribute;
                }
            }
        }
        $contentObjectVersion->updateLanguageMask( false, false );
        $contentObjectVersion->store();
        $db->commit();
    }

    static function getTranslationRolesByLanguage( $language )
    {
        $db = eZDB::instance();
        
        $result = $db->arrayQuery( "SELECT DISTINCT * FROM ezpolicy e WHERE e.module_name = 'tm' AND e.function_name = 'translate' and e.id not in ( SELECT DISTINCT policy_id FROM ezpolicy_limitation );" );
        foreach ( $result as $row )
        {
            $roles[] = $row['role_id'];
        }
        $result = $db->arrayQuery( "SELECT DISTINCT er.*
FROM ezpolicy e, ezpolicy_limitation el, ezpolicy_limitation_value elv, ezrole er
WHERE e.module_name = 'tm' AND e.function_name = 'translate' AND el.policy_id = e.id AND elv.limitation_id = el.id 
AND ( elv.value = '" . $db->escapeString( $language ) . "' or elv.value = '' ) 
AND er.id = e.role_id;
" );
        foreach ( $result as $row )
        {
            $roles[] = $row['id'];
        }
        $result = $db->arrayQuery( "SELECT DISTINCT er.*
FROM ezpolicy e, ezpolicy_limitation el, ezpolicy_limitation_value elv, ezrole er
WHERE ( ( e.function_name = '*' AND e.module_name = 'tm' ) or e.module_name = '*' )  AND er.id = e.role_id" );
        foreach ( $result as $row )
        {
            $roles[] = $row['id'];
        }
        
        $roles = array_unique( $roles );
        return $roles;
    }

    static function getApproveRolesByLanguage( $language )
    {
        $db = eZDB::instance();
        $result = $db->arrayQuery( "SELECT DISTINCT * FROM ezpolicy e WHERE e.module_name = 'tm' AND e.function_name = 'approve' and e.id not in ( SELECT DISTINCT policy_id FROM ezpolicy_limitation );" );
        foreach ( $result as $row )
        {
            $roles[] = $row['role_id'];
        }
        $result = $db->arrayQuery( "SELECT DISTINCT er.*
FROM ezpolicy e, ezpolicy_limitation el, ezpolicy_limitation_value elv, ezrole er
WHERE e.module_name = 'tm' AND e.function_name = 'approve' AND el.policy_id = e.id AND elv.limitation_id = el.id AND elv.value = '" . $db->escapeString( $language ) . "' AND er.id = e.role_id;
" );
        foreach ( $result as $row )
        {
            $roles[] = $row['id'];
        }
        $result = $db->arrayQuery( "SELECT DISTINCT er.*
FROM ezpolicy e, ezpolicy_limitation el, ezpolicy_limitation_value elv, ezrole er
WHERE ( ( e.function_name = '*' AND e.module_name = 'tm' ) or e.module_name = '*' )  AND er.id = e.role_id" );
        foreach ( $result as $row )
        {
            $roles[] = $row['id'];
        }
        
        $roles = array_unique( $roles );
        return $roles;
    }

    static function getObjectVersionLanguages( eZContentObjectVersion $version )
    {
        $languages = array();
        $object = $version->attribute( "contentobject" );
        $languages = array_merge( $languages, eZContentLanguage::languagesByMask( $object->attribute( "language_mask" ) ) );
        $languages = array_merge( $languages, eZContentLanguage::languagesByMask( $version->attribute( "language_mask" ) ) );
        return $languages;
    }

    static function fileInfo( $file )
    {
        $return = array();
        try
        {
            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->load( $file );
        }
        catch ( Exception $e )
        {
            eZDebug::writeError( $e->getMessage(), __METHOD__ );
            return false;
        }
        if ( $dom->documentElement->tagName != xrowTranslationManager::DOM_ROOT_NAME )
        {
            return false;
        }
        for ( $i = 0; $i < $dom->documentElement->childNodes->length; $i ++ )
        {
            $info = array( 
                'object_id' => $dom->documentElement->childNodes->item( $i )->getAttribute( 'ezremote:object' ) , 
                'version' => $dom->documentElement->childNodes->item( $i )->getAttribute( 'ezremote:version' ) 
            );
            $return[] = $info;
        }
        return $return;
    }

    /*
     * Will store Translation XML file. 
     * @return true
     * @throws Exception
     */
    static function storeFile( $file, $autoConfirm = false )
    {
      
        set_error_handler( function ( $errno, $errstr, $errfile, $errline, $errcontext )
        {
            if ( isset( $_SESSION['DOM_ERRORS'] ) )
            {
                var_dump( $_SESSION['DOM_ERRORS'] );
            }
            eZDebug::writeWarning( $errstr, __CLASS__ . "::storeFile() " . __METHOD__ );
            if ( preg_match( '/DOMDocument::load\(\)\s\[.*\]:\s(.*)/', $errstr, $m ) === 1 )
            {
                $_SESSION['DOM_ERRORS'][] = $m[1];
            }
        } );
        
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $test = $dom->load( $file );
        
        restore_error_handler();
        
        if ( $test === false )
        {
            eZDebug::writeError( 'XML Parsing Error. See your log files.', __METHOD__ );
            throw new Exception( 'XML Parsing Error. See your log files.' );
        }
        if ( $dom->documentElement->tagName != xrowTranslationManager::DOM_ROOT_NAME )
        {
            eZDebug::writeError( 'Expecting document of type ' . xrowTranslationManager::DOM_ROOT_NAME . ". Found " . $dom->documentElement->tagName . ".", __METHOD__ );
            throw new Exception( 'Expecting document of type ' . xrowTranslationManager::DOM_ROOT_NAME . ". Found " . $dom->documentElement->tagName . "." );
        }
        $return = true;
        
        $process = eZWorkflowProcess::fetch( $dom->documentElement->getAttribute( 'process' ) );
        
        if ( ! ( $process instanceof eZWorkflowProcess ) )
        {
            eZDebug::writeError( 'Workflowprocess no longer available. File might belong to a different Process.', __METHOD__ );
            throw new Exception( 'Workflowprocess no longer available. File might belong to a different Process.' );
        }
        $parameterList = $process->parameterList();
        $params = array( 
            'name' => 'tm_' . $process_id 
        );
        if ( isset( $parameterList['simple-file-list'] ) )
        {
            $params['simple-file-list'] = $parameterList['simple-file-list'];
        }
        
        $package = new xrowTMPackage( $params );
        for ( $i = 0; $i < $dom->documentElement->childNodes->length; $i ++ )
        {
            $ObjectID = $dom->documentElement->childNodes->item( $i )->getAttribute( 'ezremote:object' );
            $VersionID = $dom->documentElement->childNodes->item( $i )->getAttribute( 'ezremote:version' );
            
            $co = eZContentObject::fetch( $ObjectID );
            
            for ( $j = 0; $j < $dom->documentElement->childNodes->item( $i )->firstChild->childNodes->length; $j ++ )
            {
                if ( $dom->documentElement->childNodes->item( $i )->firstChild->childNodes->item( $j )->tagName == xrowTranslationManager::NOT_TRANSLATABLE_TAG_NAME )
                {
                    $dom->documentElement->childNodes->item( $i )->firstChild->replaceChild( $dom->documentElement->childNodes->item( $i )->firstChild->childNodes->item( $j )->firstChild, $dom->documentElement->childNodes->item( $i )->firstChild->childNodes->item( $j ) );
                }
            }
            
            $options = array();
            $nodeList = array();
            
            $options['language_array'][] = $dom->documentElement->childNodes->item( $i )->firstChild->getAttribute( 'language' );
            $options['language_map'][$dom->documentElement->childNodes->item( $i )->firstChild->getAttribute( 'language' )] = $dom->documentElement->childNodes->item( $i )->firstChild->getAttribute( 'language' );
            
            $tm = xrowTranslationManagement::fetch( $ObjectID, $VersionID, $dom->documentElement->childNodes->item( $i )->firstChild->getAttribute( 'destination_language' ), $process->ID );
            if ( ! $tm )
            {
                throw new Exception( "Translation workflow process and object version mismatch. Object ID $ObjectID Version $VersionID Process " . $process->ID );
            }
            if ( ! in_array( $tm->status, array( 
                xrowTranslationManagement::STATUS_UNTRANSLATED , 
                xrowTranslationManagement::STATUS_TRANSLATED , 
                xrowTranslationManagement::STATUS_UNCONFIRMED 
            ) ) )
            {
                throw new Exception( "Can`t upload file at current stage of the worklfow process. Status " . $tm->attribute( 'status_name' ) . "." );
            }
            $dom->documentElement->childNodes->item( $i )->firstChild->setAttribute( 'language', $dom->documentElement->childNodes->item( $i )->firstChild->getAttribute( 'destination_language' ) );
            $dom->documentElement->childNodes->item( $i )->firstChild->removeAttribute( 'destination_language' );
            $tm = xrowTranslationManagement::fetch( $ObjectID, $VersionID, $dom->documentElement->childNodes->item( $i )->firstChild->getAttribute( 'language' ) );
            
            if ( ! is_object( $tm ) )
            {
                eZDebug::writeError( 'No such tm object for object id ' . $ObjectID . ' and version ' . $VersionID . ' in lanugage ' . $dom->documentElement->childNodes->item( $i )->firstChild->getAttribute( 'language' ), 'xrowTranslationManager::storeFile()' );
                throw new Exception( 'No such tm object for object id ' . $ObjectID . ' and version ' . $VersionID . ' in lanugage ' . $dom->documentElement->childNodes->item( $i )->firstChild->getAttribute( 'language' ) );
            }
            
            if ( $tm->attribute( 'status' ) == xrowTranslationManagement::STATUS_UNTRANSLATED )
            {
                if ( $autoConfirm )
                {
                    $tm->setAttribute( 'status', xrowTranslationManagement::STATUS_TRANSLATED );
                }
                else
                {
                    $tm->setAttribute( 'status', xrowTranslationManagement::STATUS_UNCONFIRMED );
                }
                $tm->store();
            }
            
            $options['language_array'][] = $dom->documentElement->childNodes->item( $i )->firstChild->getAttribute( 'language' );
            $options['language_map'][$dom->documentElement->childNodes->item( $i )->firstChild->getAttribute( 'language' )] = $dom->documentElement->childNodes->item( $i )->firstChild->getAttribute( 'language' );
            
            $version = xrowTranslationManager::unserialize( $dom->documentElement->childNodes->item( $i ), $co, $VersionID, null, null, null, $nodeList, $options, $package );
            
            if ( is_object( $version ) )
            {
                $version->postUnserialize( $package );
            }
            if ( ! is_object( $version ) and $return == true )
            {
                $return = false;
            }
        }
        
        $package->remove();
        return $return;
    }

    /**
     * Once up on a time there was a eZContentObjectVersion::unserialize and it got modified.
     *
     * @param unknown_type $domNode
     * @param unknown_type $contentObject
     * @param unknown_type $Version
     * @param unknown_type $sectionID
     * @param unknown_type $activeVersion
     * @param unknown_type $firstVersion
     * @param unknown_type $nodeList
     * @param unknown_type $options
     * @param unknown_type $package
     * @param unknown_type $handlerType
     * @return unknown
     */
    
    static function unserialize( $domNode, eZContentObject $contentObject, $Version, $sectionID, $activeVersion, $firstVersion, &$nodeList, &$options, &$package, $handlerType = 'ezcontentobject' )
    {
        if ( $contentObject == null )
        {
            return false;
        }
        #eZContentObjectVersion::unserialize()
        $oldVersion = $domNode->getAttribute( 'ezremote:version' );
        
        $languageNodeArray = $domNode->getElementsByTagName( 'object-translation' );
        
        $initialLanguage = false;
        $importedLanguages = $options['language_array'];
        $currentLanguages = array();
        foreach ( $languageNodeArray as $languageNode )
        {
            $language = eZContentObjectVersion::mapLanguage( $languageNode->getAttribute( 'language' ), $options );
            if ( in_array( $language, $importedLanguages ) )
            {
                $currentLanguages[] = $language;
            }
        }
        
        if ( ! $initialLanguage )
        {
            $initialLanguage = $importedLanguages[0];
        }
        $contentObjectVersion = $contentObject->version( $Version );
        if ( ! is_object( $contentObjectVersion ) )
        {
            eZDebug::writeError( 'No such version for object ID #' . $contentObject->ID, 'xrowTranslationManager::unserialize()' );
            return false;
        }
        
        $created = eZDateUtils::textToDate( $domNode->getAttribute( 'ezremote:created' ) );
        $modified = eZDateUtils::textToDate( $domNode->getAttribute( 'ezremote:modified' ) );
        $contentObjectVersion->setAttribute( 'created', $created );
        $contentObjectVersion->setAttribute( 'modified', $modified );
        
        $contentObjectVersion->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
        $contentObjectVersion->store();
        
        $db = eZDB::instance();
        $db->begin();
        foreach ( $languageNodeArray as $languageNode )
        {
            $language = eZContentObjectVersion::mapLanguage( $languageNode->getAttribute( 'language' ), $options );
            // Only import allowed languages.
            if ( ! in_array( $language, $importedLanguages ) )
            {
                continue;
            }
            
            $attributeArray = $contentObjectVersion->contentObjectAttributes( $language );
            if ( count( $attributeArray ) == 0 )
            {
                $hasTranslation = eZContentLanguage::fetchByLocale( $language );
                
                if ( ! $hasTranslation )
                {
                    // if there is no needed translation in system then add it
                    $locale = eZLocale::instance( $language );
                    
                    if ( $locale->isValid() )
                    {
                        eZContentLanguage::addLanguage( $locale->localeCode(), $locale->internationalLanguageName() );
                        $hasTranslation = true;
                    }
                    else
                        $hasTranslation = false;
                }
                
                if ( $hasTranslation )
                {
                    // Add translated attributes for the translation
                    $originalContentAttributes = $contentObjectVersion->contentObjectAttributes( $initialLanguage );
                    foreach ( $originalContentAttributes as $originalContentAttribute )
                    {
                        $contentAttribute = $originalContentAttribute->translateTo( $language );
                        $contentAttribute->sync();
                        $attributeArray[] = $contentAttribute;
                    }
                }
            }
            
            $xpath = new DOMXPath( $domNode->ownerDocument );
            $xpath->registerNamespace( 'ezobject', 'http://ez.no/object/' );
            $xpath->registerNamespace( 'ezremote', 'http://ez.no/ezobject' );
            
            foreach ( $attributeArray as $attribute )
            {
                $attributeIdentifier = $attribute->attribute( 'contentclass_attribute_identifier' );
                $xpathQuery = "ezobject:attribute[@ezremote:identifier='$attributeIdentifier']";
                $attributeDomNodes = $xpath->query( $xpathQuery, $languageNode );
                $attributeDomNode = $attributeDomNodes->item( 0 );
                if ( ! $attributeDomNode )
                {
                    continue;
                }
                $attribute->unserialize( $package, $attributeDomNode );
                $attribute->store();
            }
        
        }
        
        $nodeAssignmentNodeList = $domNode->getElementsByTagName( 'node-assignment-list' )->item( 0 );
        $nodeAssignmentNodeArray = $nodeAssignmentNodeList->getElementsByTagName( 'node-assignment' );
        foreach ( $nodeAssignmentNodeArray as $nodeAssignmentNode )
        {
            $result = eZContentObjectTreeNode::unserialize( $nodeAssignmentNode, $contentObject, $contentObjectVersion->attribute( 'version' ), ( $oldVersion == $activeVersion ? 1 : 0 ), $nodeList, $options, $handlerType );
            if ( $result === false )
            {
                $db->commit();
                $retValue = false;
                return $retValue;
            }
        }
        
        $contentObjectVersion->store();
        $db->commit();
        
        return $contentObjectVersion;
    }

    static function findPublicURL()
    {
        $ini = eZINI::instance( "translationmanager.ini" );
        if ( $ini->variable( "SiteSettings", "PublicURL" ) )
            return $ini->variable( "SiteSettings", "PublicURL" );
        return 'http://' . $_SERVER['HTTP_HOST'];
    }

    static function baseURL()
    {
        return xrowTranslationManager::findPublicURL() . eZSys::indexFile();
    }

    static function sendMail2( eZWorkflowProcess $process, $options = null )
    {
        $trans_ini = eZINI::instance( "translationmanager.ini" );
        $admin_siteaccess = $GLOBALS['eZCurrentAccess']['name'];
        if ( $trans_ini->variable( "TranslationSettings", "UseHTTPsFormNotificationMailLinks" ) == "false" )
        {
            $protocol = "http://";
        }
        else
        {
            $protocol = "https://";
        }
        $parameters = $process->parameterList();
        $ini = eZINI::instance( "site.ini" );
        $translation_items = (string) "";
        $translator_id_array = array();
        $attachmentparams = array();
        $mailConnection = array();
        $mailSender = array();
        $mailSender['name'] = $ini->variable( "SiteSettings", "SiteName" );
        $mailSender['email'] = $ini->variable( "MailSettings", "EmailSender" );
        $mailConnection['Transport'] = $ini->variable( "MailSettings", "Transport" );
        $mailConnection['Server'] = $ini->variable( "MailSettings", "TransportServer" );
        $mailConnection['Port'] = $ini->variable( "MailSettings", "TransportPort" );
        $mailConnection['User'] = $ini->variable( "MailSettings", "TransportUser" );
        $mailConnection['Password'] = $ini->variable( "MailSettings", "TransportPassword" );
        
        $id = ( $options['all'] === false ) ? $options['object_id'] : $parameters['object_ids'];
        
        foreach ( $id as $key => $value )
        {
            $objectID[] = $value;
        }
        if ( isset( $options['confirmed'] ) )
        {
            $params[$options['target']] = array( 
                'recipient' => $options['approver'] , 
                'objectID' => $objectID , 
                'version' => null , 
                'source' => $parameters['source'] , 
                'lang' => $options['target'] , 
                'process' => $process->attribute( 'id' ) , 
                'options' => array( 
                    'saveFile' => false , 
                    'confirmed' => true 
                ) 
            );
        }
        elseif ( isset( $options['approved'] ) )
        {
            $params[$options['target']] = array( 
                'recipient' => $options['publisher'] , 
                'objectID' => $objectID , 
                'version' => null , 
                'process' => $process->attribute( 'id' ) , 
                'options' => array( 
                    'saveFile' => false , 
                    'approved' => true 
                ) 
            );
        
        }
        elseif ( isset( $options['waiting'] ) )
        {
            $params[$options['target']] = array( 
                'recipient' => $options['publisher'] , 
                'objectID' => $objectID , 
                'version' => null , 
                'process' => $process->attribute( 'id' ) , 
                'options' => array( 
                    'saveFile' => false , 
                    'waiting' => true 
                ) 
            );
        
        }
        else
        {
            $unapproved = isset( $options['unapproved'] ) ? true : false;
            
            foreach ( $parameters['translators'] as $key => $value )
            {
                $params[$key] = array( 
                    'recipient' => $value['0'] , 
                    'objectID' => $objectID , 
                    'version' => $parameters['versions'] , 
                    'source' => $parameters['source'] , 
                    'lang' => $key , 
                    'process' => $process->attribute( 'id' ) , 
                    'options' => array( 
                        'saveFile' => true , 
                        'unapproved' => $unapproved 
                    ) 
                );
            }
        
        }
                
        if ( ! isset( $options ) || $options['unapproved'] === true )
        {
            $attachment = array();
            $attachment = self::getAttachment( $params );            
        }
        if ( strtoupper($mailConnection['Transport']) == "SENDMAIL" )
        {
            $transport = new ezcMailMtaTransport();
        }
        elseif ( strtoupper($mailConnection['Transport']) == "SMTP" )
        {
            $options = new ezcMailSmtpTransportOptions();
            $options->connectionType = ezcMailSmtpTransport::CONNECTION_PLAIN;                        
            $transport = new ezcMailSmtpTransport( $mailConnection['Server'], $mailConnection['User'], $mailConnection['Password'], $mailConnection['Port'], $options );            
        }
        elseif ( $mailConnection['Transport'] == "file" )
        {
       		eZDebug::writeError( "Translation Manager: File Transport not supported yet." );       	
        }        
        else
        {            
        	eZDebug::writeError( "Translation Manager: Wrong [MailSettings] in site.ini." );        	
        }
        $delete = false;
        
        foreach ( $params as $key => $value )
        {
            
            $recipient = array();
            $recipient['email'] = eZUser::fetch( $params[$key]['recipient'] )->Email;
            $recipient['name'] = eZContentObject::fetch( $params[$key]['recipient'] )->Name;
            $mailContents = array();
            $mailContents['recipient'] = $recipient['name'];
            $mailContents['sender'] = $mailSender['name'];
            $mailContents['url'] = $ini->variable( "SiteSettings", "SiteURL" );
            $mailContents['link'] = "/collaboration/item/full/" . xrowTranslationManagement::collaborationItem( $process->attribute( 'id' ) )->attribute( 'id' );
            $mailContents['deadline'] = $parameters['deadline'];
            $mailContents['sourcelang'] = $params[$key]['source'];
            $mailContents['tolang'] = $params[$key]['lang'];
            $mailContents['objectnames'] = eZContentObject::fetchIDArray( $params[$key]['objectID'] );
            
            if ( $params[$key]['options']['confirmed'] === true )
            {
                $mailContents['subject'] = ezpI18n::tr( 'extension/translationmanager/mail', 'Translation confirmed, please approve' ) . " " . $mailSender['name'] . " - " . date( 'Y-m-d', $parameters['deadline'] ) . " (" . $mailContents['sourcelang'] . " " . ezpI18n::tr( 'extension/translationmanager/mail', 'to' ) . " " . $mailContents['tolang'] . ")";
            }
            elseif ( $params[$key]['options']['approved'] === true )
            {
                $mailContents['subject'] = $mailSender['name'] . " - " . ezpI18n::tr( 'extension/translationmanager/mail', 'Translation has been approved' );
            }
            elseif ( $params[$key]['options']['waiting'] === true )
            {
                $mailContents['subject'] = $mailSender['name'] . " - " . ezpI18n::tr( 'extension/translationmanager/mail', 'Translation has been approved and is waiting for publishing' );
            }
            else
            {
                $mailContents['subject'] = ezpI18n::tr( 'extension/translationmanager/mail', 'Translation request from' ) . " " . $mailSender['name'] . " - " . date( 'Y-m-d', $parameters['deadline'] ) . " (" . $mailContents['sourcelang'] . " " . ezpI18n::tr( 'extension/translationmanager/mail', 'to' ) . " " . $mailContents['tolang'] . ")";
            }
            
            $tpl = eZTemplate::factory();
            $tpl->setVariable( 'protocol', $protocol );
            $tpl->setVariable( 'admin_siteaccess', $admin_siteaccess );
            $tpl->setVariable( 'mail', $mailContents );
            $templateResult = $tpl->fetch( 'design:tm/mail.tpl' );
            
            $mail = new ezcMailComposer();
            $mail->from = new ezcMailAddress( $mailSender['email'], $mailSender['name'] );
            $mail->addTo( new ezcMailAddress( $recipient['email'], $recipient['name'] ) );
            $mail->subject = $mailContents['subject'];
            $mail->htmlText = $templateResult;
            if ( $params[$key]['options']['saveFile'] === true )
            {
                $mail->addFileAttachment( $attachment[$key]['0'] . $attachment[$key]['1'] );
                $delete = true;
            
            }
            $mail->build();
            
            if ( $mailConnection['Transport'] != "file" )
            {
                try
                {
                    $transport->send( $mail );
                }
                catch ( ezcMailException $e )
                {
                    eZDebug::writeError( $e->getMessage(), $mailConnection['Transport'] . '::'.__METHOD__ );
                    return false;
                }
            }
            unset( $mail, $recipient, $mailContents );
        }
        
        if ( $delete === true )
            ( unlink( $attachment[$key]['0'] . $attachment[$key]['1'] ) == false ) ? eZDebug::writeError( 'The File ' . $attachment[$key]['0'] . $attachment[$key]['1'] . ' could not be deleted', 'TM' ) : rmdir( $attachment[$key]['0'] );
    
    }

    static function getAttachment( $attachmentparams )
    {
        foreach ( $attachmentparams as $key => $value )
        {
            $attachments[$key] = xrowTranslationManager::downloadFile( $attachmentparams[$key]['objectID'], $attachmentparams[$key]['version'], $attachmentparams[$key]['source'], $attachmentparams[$key]['lang'], $attachmentparams[$key]['process'], $attachmentparams[$key]['options'] );
        }
        
        return $attachments;
    
    }

    /*
    $ObjectID can be (int) or (array)
    */
    static function downloadFile( $ObjectID, $versions, $from, $to, $process_id = null, $options = null )
    {
        $sys = eZSys::instance();
        $base = xrowTranslationManager::findPublicURL() . eZSys::indexFile();
        
        if ( $process_id !== null )
        {
            $process = eZWorkflowProcess::fetch( $process_id );
            $parameterList = $process->parameterList();
            $params = array( 
                'name' => 'tm_' . $process_id 
            );
            if ( isset( $parameterList['simple-file-list'] ) )
            {
                $params['simple-file-list'] = $parameterList['simple-file-list'];
            }
        }
        else
        {
            $params = array( 
                'name' => 'tm_quote_' . time() 
            );
        }
        
        $package = new xrowTMPackage( $params );
        if ( ! is_array( $ObjectID ) )
        {
            $ObjectIDs = array( 
                $ObjectID 
            );
        }
        else
            $ObjectIDs = $ObjectID;
        
     // Creates an instance of the DOMImplementation class
        

        $imp = new DOMImplementation();
        
        // Creates a DOMDocumentType instance
        

        $dtd = $imp->createDocumentType( xrowTranslationManager::DOM_ROOT_NAME, xrowTranslationManager::DOM_QUALIFIED_NAME, xrowTranslationManager::findPublicURL() . '/extension/translationmanagement/schemas/translationmanagement.dtd' );
        
        // Creates a DOMDocument instance
        $doc = $imp->createDocument( "", "", $dtd );
        
        // Set other properties
        $doc->encoding = 'UTF-8';
        $doc->formatOutput = true;
        
        $pi = $doc->createProcessingInstruction( "xml-stylesheet", 'version="2.0" type="text/xsl" href="' . xrowTranslationManager::findPublicURL() . '/extension/translationmanagement/schemas/translationmanagement.xslt"' );
        $doc->appendChild( $pi );
        $root = $doc->createElement( xrowTranslationManager::DOM_ROOT_NAME );
        $root->setAttribute( 'xmlns:ezobject', 'http://ez.no/object/' );
        $root->setAttribute( 'xmlns:ezremote', 'http://ez.no/object' );
        $root->setAttribute( 'xmlns:custom', 'http://ez.no/object/' );
        $root->setAttribute( 'xmlns:image', 'http://ez.no/namespaces/ezpublish3/image/' );
        $root->setAttribute( 'xmlns:tmp', 'http://ez.no/namespaces/ezpublish3/temporary/' );
        if ( $process_id )
        {
            $root->setAttribute( 'process', $process_id );
        }
        $doc->appendChild( $root );
        
        foreach ( $ObjectIDs as $ObjectID )
        {
            $Object = eZContentObject::fetch( $ObjectID );
            if ( ! is_object( $Object ) )
            {
                eZDebug::writeError( "ObjectID " . $ObjectID . " has no/broken content object.", "TM" );
                continue;
            }
            
            if ( $versions === null )
            {
                $version = $Object->version( $Object->attribute( "current_version" ) );
            
            }
            else 
                if ( is_array( $versions ) and $versions[$ObjectID] )
                {
                    $version = $Object->version( $versions[$ObjectID] );
                
                }
                else 
                    if ( is_numeric( $versions ) )
                    {
                        $version = $Object->version( $versions );
                    }
            if ( ! is_object( $version ) )
            {
                eZDebug::writeError( "ObjectID " . $ObjectID . " has no/broken current version.", __METHOD__ );
                continue;
            }
            $contentattributes = $Object->contentObjectAttributes( true, false, $from );
            
            $versionID = $version->attribute( 'version' );
            if ( count( $Object->contentObjectAttributes( true, $versionID, $from ) ) == 0 )
            {
                if ( $contentattributes[0]->Version )
                    $version = $Object->version( $contentattributes[0]->Version );
            }
            
            $lang = ( $options['unapproved'] === true ) ? $to : $from;
            
            $options['language_array'] = array( 
                $lang 
            );
            
            $node = $version->serialize( $package, $options );
            
            $node->setAttribute( 'ezremote:object', $ObjectID );
            $node->setAttribute( 'ezremote:version', $versionID );
            
            $node->firstChild->setAttribute( 'language', $from );
            $node->firstChild->setAttribute( 'destination_language', $to );
            
            for ( $i = 0; $i < $node->firstChild->childNodes->length; $i ++ )
            {
                $ca = eZContentObjectAttribute::fetch( $node->firstChild->childNodes->item( $i )->getAttribute( 'ezremote:id' ), $version->Version );
                
                if ( $ca->DataTypeString == 'eztext' )
                {
                    /** @var $domElement DOMElement */
                    $domElement = $node->firstChild->childNodes->item( $i );
                    
                    $cDataNode = $node->ownerDocument->createCDATASection( $domElement->firstChild->textContent );
                    if ( $domElement->firstChild->hasChildNodes() )
                    {
                        $domElement->firstChild->replaceChild( $cDataNode, $domElement->firstChild->firstChild );
                    }
                }
                
                if ( ! $ca->contentClassAttributeCanTranslate() || in_array( $ca->DataTypeString, eZINI::Instance( 'translationmanager.ini' )->variable( 'TranslationSettings', 'UntranslateableDatatypes' ) ) )
                {
                    $notx = $node->ownerDocument->createElement( xrowTranslationManager::NOT_TRANSLATABLE_TAG_NAME );
                    $notx->appendChild( $node->ownerDocument->importNode( clone $node->firstChild->childNodes->item( $i ), true ) );
                    $node->firstChild->replaceChild( $notx, $node->firstChild->childNodes->item( $i ) );
                }
            }
            $importedObjectNode = $doc->importNode( $node, true );
            $root->appendChild( $importedObjectNode );
            unset( $node );
        }
        
        if ( $options['saveFile'] === true )
        {
            $languagepart = '';
            if ( $to )
                $languagepart = $from . '-' . $to;
            else
                $languagepart = $from;
            
            if ( count( $ObjectIDs ) == 1 )
            {
                $filename = '' . $version->attribute( 'name' ) . '-' . $languagepart . '.xml';
                $filename = str_replace(array('/'), '_', $filename);
            }
            else
            {
                $date = new eZDateTime();
                $search[] = "/";
                $search[] = " ";
                $search[] = ":";
                $replace[] = "-";
                $replace[] = "-";
                $replace[] = "-";
                $datestring = str_replace( $search, $replace, $date->toString( true ) );
                $filename = 'Collection-' . $datestring . '-' . $languagepart . '.xml';
            }
            
            $pathname = eZSys::storageDirectory() . '/' . $process_id . '/';            
            mkdir( $pathname );
            
            $load = $doc->save( $pathname . $filename );
                   
            $attachment = array( 
                $pathname , 
                $filename 
            );
            return $attachment;
        }
        
        $load = $doc->saveXML();
        
        # Store simple files in db for reference.
        if ( $process instanceof eZWorkflowProcess and isset( $package->Parameters['simple-file-list'] ) and is_array( $package->Parameters['simple-file-list'] ) and count( $package->Parameters['simple-file-list'] ) > 0 )
        {
            if ( ! isset( $parameterList['simple-file-list'] ) or ! is_array( $parameterList['simple-file-list'] ) )
            {
                $parameterList['simple-file-list'] = array();
            }
            
            $parameterList['simple-file-list'] = array_merge( $parameterList['simple-file-list'], $package->Parameters['simple-file-list'] );
            $process->setParameters( $parameterList );
            
            $process->store();
        }
        
        $package->remove();
        ob_clean();
        header( "Content-Type: text/xml; charset=\"UTF-8\"" );
        header( "Content-Length: " . strlen( $load ) );
        if ( $to )
            $languagepart = $from . '-' . $to;
        else
            $languagepart = $from;
        if ( count( $ObjectIDs ) == 1 )
        {
            header( 'Content-Disposition: attachment; filename="' . $version->attribute( 'name' ) . '-' . $languagepart . '.xml"' );
        }
        else
        {
            $date = new eZDateTime();
            $search[] = "/";
            $search[] = " ";
            $search[] = ":";
            $replace[] = "-";
            $replace[] = "-";
            $replace[] = "-";
            $datestring = str_replace( $search, $replace, $date->toString( true ) );
            header( 'Content-Disposition: attachment; filename="Collection-' . $datestring . '-' . $languagepart . '.xml"' );
        }
        
        header( 'Content-Transfer-Encoding: binary' );
        header( 'Accept-Ranges: bytes' );
        
        while ( @ob_end_flush() );
        
        echo $load;
        
        eZExecution::cleanExit();
    }
}

?>