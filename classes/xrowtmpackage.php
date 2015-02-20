<?php

/**
 * @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
 * @license [EXTENSION_LICENSE]
 * @version [EXTENSION_VERSION]
 * @package translationmanagement
 */
class xrowTMPackage extends eZPackage
{

    function __construct( $parameters = array(), $repositoryPath = false )
    {
        $this->setParameters( $parameters );
        if ( ! $repositoryPath )
            $repositoryPath = eZPackage::repositoryPath();
        $this->RepositoryPath = $repositoryPath;
        $this->RepositoryInformation = null;
    }

    function simpleFilePath( $fileKey )
    {
        if ( ! isset( $this->Parameters['simple-file-list'] ) )
        {
            eZDebug::writeError( "Missing key: " . $fileKey, __METHOD__ );
            return false;
        }
        if ( ! isset( $this->Parameters['simple-file-list'][$fileKey] ) )
        {
            eZDebug::writeError( "Missing key: " . $fileKey, __METHOD__ );
            return false;
        }
        $filepath = $this->Parameters['simple-file-list'][$fileKey]['original-path'];

        if ( eZClusterFileHandler::instance( $filepath ) instanceof eZDFSFileHandler )
        {
            if ( eZINI::instance( 'file.ini' )->hasVariable( 'eZDFSClusteringSettings', 'MountPointPath' ) )
            {
                $mountPointPath = eZINI::instance( 'file.ini' )->variable( 'eZDFSClusteringSettings', 'MountPointPath' );
                
                if ( ! $mountPointPath = realpath( $mountPointPath ) )
                    throw new eZDFSFileHandlerNFSMountPointNotFoundException( $mountPointPath );
                
                if ( substr( $mountPointPath, - 1 ) != '/' )
                    $mountPointPath = "$mountPointPath/";
                
                $filepath = $mountPointPath . $filepath;
            }
        }
        eZDebug::writeDebug( "Key: " . $fileKey . " File: " . $filepath, __METHOD__ );
        return $filepath;
    }

    function appendSimpleFile( $key, $filepath )
    {
        if ( ! isset( $this->Parameters['simple-file-list'] ) )
        {
            $this->Parameters['simple-file-list'] = array();
        }
        eZDebug::writeDebug( "Key: " . $key . " File: " . $filepath, __METHOD__ );
        
        $this->Parameters['simple-file-list'][$key] = array( 
            'original-path' => $filepath 
        );
    }
}