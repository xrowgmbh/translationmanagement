<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/

$module = $Params["Module"];
$http = eZHTTPTool::instance();

$dir = eZExtension::baseDirectory() . '/translationmanagement/share/' . $Params['Type'] . '/';

if ( !is_dir( $dir ) )
{
    eZDebug::writeError('Missing Type ' . $Params['Type'], "Translation Management" );
    return $Module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
}
$file = eZSys::cacheDirectory() . '/' . $Params['Type'] . '.zip';
if ( file_exists( $file ) )
{
    unlink( $file );
}
$archive = ezcArchive::open( $file, ezcArchive::ZIP );
$archive->truncate(); 
appendRecursive( $archive, $dir, $dir ); 
$archive->close();  

eZFile::download( $file );

function findRecursiveCallback( ezcBaseFileFindContext $context, $sourceDir, $fileName, $fileInfo )
{
 $path = "{$sourceDir}/{$fileName}";
 if ( is_dir( $path ) )
 {
 $path .= '/';
 }
 $context->archive->append( array( $path ), $context->prefix );
}

function appendRecursive( $archive, $sourceDir, $prefix )
{
 $context = new xrowArchiveContext();
 $context->archive = $archive;
 $context->prefix = $prefix;
 ezcBaseFile::walkRecursive( $sourceDir, array(), array(), 'findRecursiveCallback', $context );
} 
?>