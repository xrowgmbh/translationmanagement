<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/

$Module = $Params["Module"];

$tpl = eZTemplate::factory();

if( xrowTranslationManager::versionCheck() )
{
    $tpl->setVariable('version', true );
}
else
{
	$tpl->setVariable('version', false );
}

if ( $Module->isCurrentAction( 'Clean' ) )
{
    xrowTranslationManager::cleanup();
    $tpl->setVariable('clean', true );

}
else 
{
	$tpl->setVariable('clean', false );
}


$Result = array();
$Result['content'] = $tpl->fetch("design:tm/admin.tpl");
$Result['left_menu'] = xrowTranslationManager::leftMenu();
$Result['path'] = array(
array('url' => $Module->Name.'/menu',
			      'text' => $Module->Module['name'] ),
array('url' => false,
			      'text' => ezpI18n::tr('extension/tm', 'Translation management administration')
			      ));

?>