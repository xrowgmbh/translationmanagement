<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/

// Start module definition
$module = $Params["Module"];
$Result = array();
// Parse HTTP POST variables
$http = eZHTTPTool::instance();
// Access system variables
$sys = eZSys::instance();
// Init template behaviors 
$tpl = eZTemplate::factory();
$output = '';
$errors = array();
$messages = array();

if ( $Module->isCurrentAction( 'UpdateTranslations' ) )
{
    $updateextensions = array();
    if ( $http->hasPostVariable( 'extension' ) and $http->postVariable( 'extension' ) == '' )
    {
        $extensions = xrowTranslationGUI::getAllTranslationExtensions();
        foreach ( $extensions as $extension )
        {
            $updateextensions[] = $extension['id'];
        }
    }
    else
    {
        $updateextensions = array( $http->postVariable( 'extension' ) );
    }
    if ( $http->hasPostVariable( 'dropobsolete' ) )
    {
        xrowTranslationGUI::ezlupdate( $updateextensions, true, $output );
    }
    else
    {
        xrowTranslationGUI::ezlupdate( $updateextensions, false, $output );
    }
}
$tpl->setVariable('output', $output );
if ( $Module->isCurrentAction( 'Upload' ) )
{
    $httpFileName = "file";
    try
    {
		if ( eZHTTPFile::canFetch( $httpFileName ) )
		{
			$httpFile = eZHTTPFile::fetch( $httpFileName );
				if ( $httpFile )
				{
					if ( $httpFile->attribute( "mime_type" ) == "text/xml" )
					{
						xrowTranslationGUI::upload( $httpFile->attribute( "filename" ) );
						$messages['upload-success']=true;
					}
					else
					{
						throw new Exception ( 'This file format is not accepted.' );
					}
				}
				else
				{
					throw new Exception ( 'An error occured during upload.' );
				}
		}
		else
		{
			throw new Exception ( 'An error occured during upload.' );
		}
    }
    catch (Exception $e)
    {
       $error = $e->getMessage();
       $tpl->setVariable('error', $error );

    } 
}
$extensions = xrowTranslationGUI::getAllTranslationExtensions();
$extension_info = array();
foreach ( $extensions as $extension )
{
    $extension_info[$extension['id']] = xrowTranslationGUI::getAllTranslationsByExtension( $extension['id'] );
}

$untranslateableExtensions = xrowTranslationGUI::getUntranslatableExtensions();

if(!empty($untranslateableExtensions))
{
	$tpl->setVariable('untranslateableExtensions', $untranslateableExtensions );
}

$extensionWOTranslationDir = xrowTranslationGUI::checkDownloadDir($extensions);

if(!empty($extensionWOTranslationDir))
{
	$tpl->setVariable('extensionWOTranslationDir', $extensionWOTranslationDir );
}

$tpl->setVariable('extensions', xrowTranslationGUI::getAllTranslationExtensions() );
$tpl->setVariable('extension_info', $extension_info );
$tpl->setVariable('languages', xrowTranslationGUI::languageList() );
$tpl->setVariable('messages', $messages );
$tpl->setVariable('tsbinary', xrowTranslationGUI::hasTSBinary() );

$Result['content'] = $tpl->fetch("design:tm/gui.tpl");
$Result['left_menu'] = xrowTranslationManager::leftMenu();
$Result['path'] = array(
array('url' => $Module->Name.'/menu',
			      'text' => $Module->Module['name'] ),
array('url' => false,
			      'text' => ezpI18n::tr('extension/tm', 'Interface string translation')
			      ));

?>