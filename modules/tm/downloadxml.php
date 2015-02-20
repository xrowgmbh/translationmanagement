<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/

$Module = $Params['Module'];

$tpl = eZTemplate::factory();
$http = eZHTTPTool::instance();


$messages = array();
$errors = array();

if ( !$Module->hasActionParameter( 'target' ) )
{
    eZDebug::writeError('Missing parameter target language.', "Translation management" );
    return $Module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
}
$process = eZWorkflowProcess::fetch( $Module->actionParameter( 'process' ) );
if ( $process instanceof eZWorkflowProcess )
{
    $parameters = $process->parameterList();
    $object_ids = $parameters['object_ids'];
    $versions = $parameters['versions'];
    $source = $parameters['source'];
    $process_id = $process->ID;
}
elseif( $Params['UserParameters']['object_id'] )
{
    $object_ids = array( $Params['UserParameters']['object_id'] );
    $versions = array( $Params['UserParameters']['object_id'] => $Params['UserParameters']['version'] );
    $source = $Module->actionParameter( 'source' );
}
else
{
	eZDebug::writeError('Missing parameter.', "Translation management" );
    return $Module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
}
if ( $Module->isCurrentAction( 'Download' ) )
{
    $options = array( "trimxml" => false );
    $tm = new xrowTranslationManager( $options );   
    $tm->downloadFile( $object_ids, $versions, $source, $Module->actionParameter( 'target' ), $process_id  );
}


$tpl->setVariable( 'messages' , $messages );
$tpl->setVariable( 'errors' , $errors );
$Result = array();
$Result['content'] = $tpl->fetch( "design:tm/manager.tpl" );
$Result['path'] = array( array( 'url' => false,
                        'text' => 'Translation Manager' ) );
?>