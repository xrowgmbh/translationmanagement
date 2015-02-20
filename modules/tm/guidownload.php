<?php
/**
 * @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
 * @license [EXTENSION_LICENSE]
 * @version [EXTENSION_VERSION]
 * @package translationmanagement
 */

// Start module definition
$Module = $Params["Module"];

if ( $Module->isCurrentAction( 'Download' ) )
{    
    xrowTranslationGUI::download( $Module->actionParameter( 'extension' ), $Module->actionParameter( 'language' ) );
}

return $Module->redirectToView( "gui" );

?>