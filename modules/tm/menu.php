<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/

$Module = $Params['Module'];
$tpl = eZTemplate::factory();
$Result = array();
$Result['left_menu'] = xrowTranslationManager::leftMenu();
$Result['content'] = $tpl->fetch( "design:tm/menu.tpl" );
$Result['path'] = array( array( 'url' => false,
                        'text' => 'Menu' ) );
?>