<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/
class xrowTranslationManagement extends eZPersistentObject
{
	const STATUS_UNKOWN = 0;
	const STATUS_UNTRANSLATED = 1;
	const STATUS_TRANSLATED = 2;
	const STATUS_UNTRANSLATEABLE = 3;
	const STATUS_UNCONFIRMED = 4;
	const STATUS_INITIAL = 5;
	const STATUS_WAITING_FOR_APPROVAL = 6;
	const STATUS_APPROVED = 7;
	const STATUS_ACCEPTED = 8;
	const STATUS_REJECTED = 9;
	const STATUS_WAITING_FOR_EDITOR_APPROVAL = 10;
	const COLLABORATION_TYPE = 'translation';

	function xrowTranslationManagement( $row )
    {
       $this->eZPersistentObject( $row );
    }

    static function definition()
    {
        return array( "fields" => array( "contentobject_id" => array( 'name' => 'contentobject_id',
                                                        'datatype' => 'integer',
                                                        'default' => 0,
                                                        'required' => true ),
                                         "contentobject_version" => array( 'name' => "contentobject_version",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "language" => array(     'name' => "language",
                                                              'datatype' => 'string',
                                                              'default' => null,
                                                              'required' => true ),
                                         "translated_from" => array(  'name' => "translated_from",
                                                              'datatype' => 'string',
                                                              'default' => null,
                                                              'required' => true ),
                                         "status" => array(  'name' => "status",
                                                              'datatype' => 'integer',
                                                              'default' => xrowTranslationManagement::STATUS_UNKOWN,
                                                              'required' => false ),
                                         "deadline" => array(  'name' => "deadline",
                                                              'datatype' => 'integer',
                                                              'default' => 0,
                                                              'required' => false ),
                                         "user_id" => array(  'name' => "user_id",
                                                              'datatype' => 'integer',
                                                              'default' => 0,
                                                              'required' => false ),
                                         "data_text" => array(  'name' => "data_text",
                                                              'datatype' => 'string',
                                                              'default' => null,
                                                              'required' => false ),
                                         "process_id" => array(  'name' => "process_id",
                                                              'datatype' => 'integer',
                                                              'default' => null,
                                                              'required' => false ),
                                         "approver_id" => array(  'name' => "approver_id",
                                                              'datatype' => 'integer',
                                                              'default' => 0,
                                                              'required' => false )
                                                               ),
                      "keys" => array( "contentobject_id", "contentobject_version", "language" ),
                      "function_attributes" => array( 
                        "status_name" => "statusName",
                        "target_language" => "targetLanguage",
                        "source_language" => "sourceLanguage",
                        'user' => 'user',
                        'approver' => 'approver',
                        'collaboration_item' => 'collaborationItem',
                        'can_translate' => 'canTranslate',
                        'can_approve' => 'canApprove' ),
                      "class_name" => "xrowTranslationManagement",
                      "sort" => array( "contentobject_id" => "asc" ),
                      "name" => "ezxtranslationmanagement" );
    }
    function targetLanguage()
    {
    	return eZContentLanguage::fetchByLocale( $this->language );
    }
    function sourceLanguage()
    {
    	return eZContentLanguage::fetchByLocale( $this->translated_from );
    }
    function canApprove()
    {
        $id = eZUser::currentUserID();
        $collab = self::collaborationItem( $this->attribute( 'process_id' ) );
        if( is_numeric( $id ) and ( $this->approver_id == $id or  $collab->CreatorID == $id ) )
        {
            return true;
        }
        else
            false;
    }
    function canTranslate( )
    {
        $user = eZUser::currentUser();
        $accessResult = $user->hasAccessTo( 'tm' , 'translate' );

        if ( $accessResult['accessWord'] == 'yes' ) 
            return 1;
        if ( $accessResult['accessWord'] == 'limited' )
        {
            $policies  =& $accessResult['policies'];
            $access = 'denied';
            foreach ( array_keys( $policies ) as $pkey  )
            {
                $limitationArray =& $policies[ $pkey ];
                if ( isset( $limitationArray['Language' ] ) )
                {
                    if ( in_array( $this->attribute( 'language' ), $limitationArray['Language' ] ) )
                        return 1;
                }
                if ( isset( $limitationArray['User_Subtree' ] ) )
                {
                        /* TODO Really check upon the subtree limitation 
                        
                        example
                        array(2) {
  ["accessWord"]=>
  string(7) "limited"
  ["policies"]=>
  array(2) {
    ["p_687_32"]=>
    array(1) {
      ["User_Subtree"]=>
      array(1) {
        [0]=>
        string(5) "/1/2/"
      }
    }
  }
}
                        */
                        return 1;
                }
            }
        }
        return 0;

    }
    static function removePendingDrafts( $object_id, $version_id )
    {
        $obj = eZContentObject::fetch( $object_id );
               
        $draftVersions = $obj->versions( true, array( 'conditions' => array( 'status' => eZContentObjectVersion::STATUS_PENDING) ) );        
        
        if ( count( $draftVersions ) > 0 )
        {
            foreach( $draftVersions as $currentDraft )
            {
                $currentDraft->remove();
            }
        }
    }        
    
    static function fetchLastPendingDraft( $object_id )
    {
        if ( !is_numeric( $object_id ) and !( $object_id > 0 ) )
            $object_id = $this->contentobject_id;
        $obj = eZContentObject::fetch( $object_id );
        $draftVersions = $obj->versions( true, array( 'conditions' => array( 'status' => array( array( eZContentObjectVersion::STATUS_PENDING, eZContentObjectVersion::STATUS_DRAFT ) ) ) ) );
        
        if ( count( $draftVersions ) > 0 )
        {
            $mostRecentDraft =& $draftVersions[0];
            foreach( $draftVersions as $currentDraft )
            {
                if( $currentDraft->attribute( 'modified' ) > $mostRecentDraft->attribute( 'modified' ) )
                {
                    $mostRecentDraft =& $currentDraft;
                }
            }
            return $mostRecentDraft;
        }
        return false;
    }
    static function startTranslation( $process_id, eZContentObjectVersion $version, $sourceLanguage, $language, $deadline = null, $translator_id = null, $approver_id = null )
    {
                $row = array(
                        "contentobject_id" => $version->attribute('contentobject_id'),
                        "contentobject_version" => $version->attribute('version'),
                        "language" => $language,
                        "status" => xrowTranslationManagement::STATUS_UNTRANSLATED,
                        "translated_from" => $sourceLanguage,
                        "deadline" => $deadline ? $deadline->timestamp() : null,
                        "user_id" => $translator_id,
                        "approver_id" => $approver_id,
                        "data_text" =>  '',
                        "process_id" => $process_id );
                $item = new xrowTranslationManagement( $row );
                $item->store();
    }
    function user()
    {
        return eZUser::fetch( $this->user_id );
    }
    function approver()
    {
        return eZUser::fetch( $this->approver_id );
    }
    function statusName()
    {
                switch ( $this->status )
                {
                    case xrowTranslationManagement::STATUS_UNTRANSLATED:
                        $return = "Untranslated";
                    break;
                    case xrowTranslationManagement::STATUS_TRANSLATED:
                        $return = "Translated";
                    break;
                    case xrowTranslationManagement::STATUS_UNTRANSLATEABLE:
                        $return = "Untranslateable";
                    break;
                    case xrowTranslationManagement::STATUS_UNCONFIRMED:
                        $return = "Unconfirmed";
                    break;
                    case xrowTranslationManagement::STATUS_INITIAL:
                        $return = "Initial";
                    break;
                    case xrowTranslationManagement::STATUS_WAITING_FOR_APPROVAL:
                        $return = "Waiting for approval";
                    break;
                    case xrowTranslationManagement::STATUS_APPROVED:
                        $return = "Approved";
                    break;
                    case xrowTranslationManagement::STATUS_ACCEPTED:
                        $return = "Accepted";
                    break;
                    case xrowTranslationManagement::STATUS_REJECTED:
                        $return = "Rejected";
                    break;
                    case xrowTranslationManagement::STATUS_WAITING_FOR_EDITOR_APPROVAL:
                        $return = "Waiting for publishing";
                    break;                    
                    default:
                        $return = "Status unkown";
                }
                return $return;
    }

    function hasAttribute( $attr )
    {
        return ( $attr == "status_name2" or
                 eZPersistentObject::hasAttribute( $attr ) );
    }
    function attribute( $attr, $noFunction = false )
    {
        switch ( $attr )
        {
            case "status_name2":
            {

            }
            break;
            default:
                return eZPersistentObject::attribute( $attr );
        }
    }
    static function fetch( $contentobject_id, $contentobject_version, $language = null, $process_id = null, $asObject = true )
    {
	    $conds = array( "contentobject_id" => $contentobject_id, 'contentobject_version' => $contentobject_version );
        if ( $language !== null )
		{
            $conds['language'] = $language;
        }
		if ( $process_id  !== null )
		{
            $conds['process_id '] = $process_id ;
        }
        return eZPersistentObject::fetchObject( xrowTranslationManagement::definition(),
                                                null,
                                                $conds,
                                                $asObject );
    }
    static function fetchListPerObject( $contentobject_id, $contentobject_version, $asObject = true )
    {
        $conds = array();
        if ( is_numeric( $contentobject_id ) )
            $conds["contentobject_id"] = $contentobject_id;
        if ( is_numeric( $contentobject_id ) )
            $conds["contentobject_version"] = $contentobject_version;
        return eZPersistentObject::fetchObjectList( xrowTranslationManagement::definition(),
                                                    null, $conds, null, null,
                                                    $asObject );
    }
    static function fetchList( $conds = null, $grouping = false )
    {
        return eZPersistentObject::fetchObjectList( xrowTranslationManagement::definition(),
                                                    null, $conds, null, null,
                                                    true, $grouping );
    }
    static function fetchAssignedLanguages( $process_id )
    {
        $return = array();
        $conds= array(
        'process_id' => $process_id
        );

        $user_id = eZUser::currentUserID();
        $list = eZPersistentObject::fetchObjectList( xrowTranslationManagement::definition(),
                                                    null, $conds, null, null, true, array( 'language' ) );

        foreach ( $list as $tm )
        {

            if ( ( $tm->attribute( 'approver_id' ) ==  $user_id ) or ( $tm->attribute( 'user_id' ) == $user_id ) )
            {
                
                $return[$tm->attribute( 'language' )] = eZContentLanguage::fetchByLocale( $tm->attribute( 'language' ) );
            }
        }
        return $return;
    }
    static function fetchStatusReportByProcess( $process, $language = false )
    {
        $process_id = $process->attribute( 'id' );
        $list = $process->parameterList();
        if ( $language === false )
        {    $conds= array(
                'process_id' => $process_id
            );
        }
        else
        {
            $conds= array(
                'process_id' => $process_id,
                'language' => $language
            );
        }
        $data = array(
        		self::STATUS_UNKOWN => array(),
        		self::STATUS_UNTRANSLATED => array(),
        		self::STATUS_TRANSLATED => array(),
        		self::STATUS_UNTRANSLATEABLE => array(),
        		self::STATUS_UNCONFIRMED => array(),
        		self::STATUS_INITIAL => array(),
        		self::STATUS_WAITING_FOR_APPROVAL => array(),
        		self::STATUS_APPROVED => array(),
        		self::STATUS_WAITING_FOR_EDITOR_APPROVAL => array(),
        		self::STATUS_ACCEPTED => array(),
        		self::STATUS_REJECTED => array()
        );
        $listItems = xrowTranslationManagement::fetchList( $conds );
        foreach ( $listItems as $item )
        {
            $data[$item->status][] = $item;
        }
        return $data; 
    }
    function process( $process_id = null )
    {
        if ( !$process_id )
            $process_id = $this->attribute( 'process_id' );
        return eZWorkflowProcess::fetch( $process_id );
    }
    static function collaborationItem( $process_id = null )
    {
        if ( !$process_id )
        {
        	return false;
        }
        $cond = array( 'data_int1' => $process_id );
        return eZCollaborationItem::fetchObject( eZCollaborationItem::definition(), null, $cond );
    }
}

?>