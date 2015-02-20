<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/

// Start module definition
$module = & $Params["Module"];
$Result = array();
// Parse HTTP POST variables
$http = eZHTTPTool::instance();
// Access system variables
$sys = eZSys::instance();
// Init template behaviors 
$tpl = eZTemplate::factory();
// Access ini variables
$ini = eZINI::instance();

$errors = array();

#$Subtree = array();
#$http->setSessionVariable( 'Subtree', $Subtree );
if ( $http->hasPostVariable( 'Subtree' ) )
{
    $Subtree = $http->postVariable( 'Subtree' );
    $http->setSessionVariable( 'Subtree', $Subtree );
}
elseif ( $http->hasSessionVariable( 'Subtree' ) )
{
    $Subtree = $http->sessionVariable( 'Subtree' );
}
else
{
    $Subtree = array();
}

if ( $http->hasSessionVariable( 'Subtree' ) and ! is_array( $http->sessionVariable( 'Subtree' ) ) )
{
    $http->setSessionVariable( 'Subtree', $Subtree );
}
if ( $http->postVariable( 'deadline_day' ) and $http->postVariable( 'deadline_month' ) and $http->postVariable( 'deadline_year' ) )
{
    $now = new eZDate();
    $deadline = new eZDate();
    $deadline->setMDY( $http->postVariable( 'deadline_month' ), $http->postVariable( 'deadline_day' ), $http->postVariable( 'deadline_year' ) );
    
    if ( ! $deadline->isValid() and $deadline->isGreaterThan( $now ) )
        $deadline = null;
}
if ( ! isset( $deadline ) )
{
    $deadline = new eZDate();
    $deadline->adjustDate( 0, 7, 0 );
}

if ( $http->postVariable( 'deadline_day' ) and $http->postVariable( 'deadline_month' ) and $http->postVariable( 'deadline_year' ) )
{
    $now = new eZDate();
    $deadline = new eZDate();
    $deadline->setMDY( $http->postVariable( 'deadline_month' ), $http->postVariable( 'deadline_day' ), $http->postVariable( 'deadline_year' ) );
    
    if ( ! $deadline->isValid() or ! $deadline->isGreaterThan( $now ) )
        $deadline = null;
}
if ( ! $deadline )
{
    $deadline = new eZDate();
    $deadline->adjustDate( 0, 7, 0 );
}
if ( $http->hasPostVariable( 'initial_language' ) )
{
    $Source = $http->postVariable( 'initial_language' );
}
else
{
    $list = eZContentLanguage::prioritizedLanguageCodes();
    $Source = $list[0];
}

if ( $http->hasPostVariable( 'SelectedNodeIDArray' ) )
{
    $nodes = $http->postVariable( 'SelectedNodeIDArray' );
    $selectednodes = array();
    foreach ( $nodes as $node )
    {
        if ( ! isset( $Subtree[$node] ) )
        {
            $selectednodes["_$node"] = array( 
                'node' => $node 
            );
        }
    
    }
    
    if ( count( $Subtree ) > 0 )
    {
        $Subtree = array_merge( $selectednodes, $Subtree );
    }
    else
    {
        $Subtree = $selectednodes;
    }
    eZHTTPTool::setSessionVariable( 'Subtree', $Subtree );
}

if ( $http->hasPostVariable( 'Remove' ) )
{
    $removals = $http->postVariable( 'RemoveNodeArray' );
    foreach ( $Subtree as $key => $value )
    {
        if ( in_array( $key, $removals ) )
        {
            unset( $Subtree[$key] );
        
        }
    }
    $http->setSessionVariable( 'Subtree', $Subtree );
}
if ( $http->hasPostVariable( 'BrowseSubtree' ) )
{
    $return = eZContentBrowse::browse( array( 
        'action_name' => 'TranslationSubtree' , 
        'from_page' => '/tm/collect' , 
        'persistent_data' => array( 
            "Source" => $Source 
        ) 
    ), $module );
}
if ( $Module->isCurrentAction( 'Translate' ) and ! $http->postVariable( 'translators' ) )
{
    $errors['no-translators'] = true;
}
if ( $Module->isCurrentAction( 'Translate' ) and ! $http->postVariable( 'approvers' ) )
{
    $errors['no-approvers'] = true;
}

if ( $Module->isCurrentAction( 'Translate' ) and $http->postVariable( 'translators' ) and $http->postVariable( 'approvers' ) )
{
    set_time_limit( 3 * 60 );
    $versions = array();
    $objectids = xrowTranslationManager::ObjectIDsFromNodeIDArray( $Module->actionParameter( 'Source' ), $Subtree );
    if ( count( $objectids ) >= 1 )
    {
        foreach ( $objectids as $id )
        {
            $obj = eZContentObject::fetch( $id );
            $version = $obj->createNewVersionIn( $Source );
            $version->setAttribute( 'status', eZContentObjectVersion::STATUS_PENDING );
            $version->store();
            $versions[$version->attribute( 'contentobject_id' )] = $version->attribute( 'version' );
        }
        if ( $http->hasPostVariable( 'auto_accept' ) )
        {
            $auto_accept = $http->postVariable( 'auto_accept' );
        }
        else
        {
            $auto_accept = null;
        }
        
        $operationResult = eZOperationHandler::execute( 'tm', 'translate', array( 
            'deadline' => $deadline->timeStamp() , 
            'language_code_include' => $http->postVariable( 'language_code_include' ) , 
            'translators' => $http->postVariable( 'translators' ) , 
            'url' => eZSys::hostname() . eZSys::indexDir() , 
            'approvers' => $http->postVariable( 'approvers' ) , 
            'source' => $Module->actionParameter( 'Source' ) , 
            'versions' => $versions , 
            'versions_key' => serialize( $versions ) , 
            'object_ids' => $objectids , 
            'auto_accept' => $auto_accept 
        ) );
       
        switch ( $operationResult['status'] )
        {
            case eZModuleOperationInfo::STATUS_HALTED:
                {
                    
                    if ( isset( $operationResult['redirect_url'] ) )
                    {
                        $module->redirectTo( $operationResult['redirect_url'] );
                        return;
                    }
                    else 
                        if ( isset( $operationResult['result'] ) )
                        {
                            $Result['content'] = $tpl->fetch( "design:tm/collect_success.tpl" );
                        }
                }
                break;
        }
    
    } // if count >=1
    else
    {
        $errors['no-object'] = true;
    }
}
if ( $Module->isCurrentAction( 'Download' ) )
{
    $objectids = xrowTranslationManager::ObjectIDsFromNodeIDArray( $Module->actionParameter( 'Source' ), $Subtree );
    $tm = new xrowTranslationManager();
    $tm->downloadFile( $objectids, null, $Module->actionParameter( 'Source' ), $Module->actionParameter( 'Target' ) );
}
// Put above vars in tpl
if ( $http->hasPostVariable( 'translators' ) )
{
    $tpl->setVariable( 'translators', $http->postVariable( 'translators' ) );
}
if ( $http->hasPostVariable( 'approvers' ) )
{
    $tpl->setVariable( 'approvers', $http->postVariable( 'approvers' ) );
}
if ( $http->hasPostVariable( 'language_code_include' ) )
{
    $tpl->setVariable( 'language_code_include', $http->postVariable( 'language_code_include' ) );
}
if ( $http->hasPostVariable( 'auto_accept' ) )
{
    $auto_accept = $http->postVariable( 'auto_accept' );
}
else
{
    $auto_accept = (!isset($auto_accept) ) ? null : 'off';
}

$tpl->setVariable( 'auto_accept', $auto_accept );
$tpl->setVariable( 'Subtree', $Subtree );
$tpl->setVariable( 'Source', $Source );
$tpl->setVariable( 'Deadline', $deadline );
$tpl->setVariable( 'errors', $errors );

if ( ! array_key_exists( 'content', $Result ) )
    $Result['content'] = $tpl->fetch( "design:tm/collect.tpl" );
$Result['left_menu'] = xrowTranslationManager::leftMenu();
$Result['path'] = array( 
    array( 
        'url' => $Module->Name . '/menu' , 
        'text' => $Module->Module['name'] 
    ) , 
    array( 
        'url' => false , 
        'text' => ezpI18n::tr( 'extension/tm', 'Translation collection' ) 
    ) 
);

?>