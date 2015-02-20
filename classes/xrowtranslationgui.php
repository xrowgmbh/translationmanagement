<?php

/**
 * @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
 * @license eZ Proprietary Extension License (PEL) v1.3
 * @version 2.2-14
 * @package translationmanagement
 */
class xrowTranslationGUI
{
    const DOM_ROOT_NAME = 'TS';
    const DOM_QUALIFIED_NAME = "-//xrow GmbH//DTD TS TRADOS V 1.0//EN";

    static function hasTSBinary()
    {
        if ( is_executable( eZINI::Instance( 'translationmanager.ini' )->variable( 'TranslationSettings', 'TSBinaryPath' ) ) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    static function ezlupdate( $extensions, $dropobsolete = false, &$output )
    {
        foreach ( $extensions as $extension )
        {
            $pipes = array();
            $cmd = eZINI::Instance( 'translationmanager.ini' )->variable( 'TranslationSettings', 'TSBinaryPath' );
            if ( $dropobsolete )
            {
                $cmd .= ' -no';
            }
            $cmd .= ' -u -e extension/' . $extension;
            eZDebug::writeDebug( $cmd, 'Executing ezlupdate' );
            $descriptorspec = array( 
                0 => array( 
                    "pipe" , 
                    "r" 
                ) ,  // stdin is a pipe that the child will read from
                1 => array( 
                    "pipe" , 
                    "w" 
                ) ,  // stdout is a pipe that the child will write to
                2 => array( 
                    "pipe" , 
                    "w" 
                )  // stderr is a file to write to
            );
            $process = proc_open( $cmd, $descriptorspec, $pipes, eZSys::rootDir() );
            if ( is_resource( $process ) )
            {
                $error = stream_get_contents( $pipes[2] );
                if ( strpos( $error, 'ERROR' ) !== false )
                {
                    eZDebug::writeError( $error, 'ezlupdate' );
                    $output = $output . $error;
                }
                else
                {
                    $output = $output . $error;
                }
                $return_value = proc_close( $process );
                if ( $return_value != 0 )
                {
                    return $return_value;
                }
            }
        }
        return $return_value;
    }

    static function getAllTranslationExtensions()
    {
        $ini = eZINI::instance( 'translationmanager.ini' );
        if ( $ini->hasVariable( 'TranslationSettings', 'ExcludedExtensions' ) )
        {
            $excludedExtensions = $ini->variable( 'TranslationSettings', 'ExcludedExtensions' );
        }
        else
        {
            $excludedExtensions = array();
        }
        
        $list = array();
        $dirs = eZDir::findSubitems( eZExtension::baseDirectory(), false );
        foreach ( $dirs as $dir )
        {
            $ini = eZINI::instance( 'site.ini', eZExtension::baseDirectory() . '/' . $dir . '/settings', null, null, null, true );
            
            if ( ! $ini->hasVariable( 'RegionalSettings', 'TranslationExtensions' ) )
            {
                continue;
            }
            
            if ( in_array( $dir, $excludedExtensions ) || ! is_dir( eZExtension::baseDirectory() . '/' . $dir ) )
            {
                continue;
            }
            
            $ex = array();
            $extensionInfo = array();
            $transdir = eZExtension::baseDirectory() . '/' . $dir . '/translations';
            
            if ( is_dir( eZExtension::baseDirectory() . '/' . $dir ) and is_dir( $transdir ) and is_file( $transdir . '/untranslated/translation.ts' ) and eZDir::isWriteable( $transdir ) )
            {
                $extensionInfo = eZExtension::extensionInfo( $dir );
            }
            if ( $extensionInfo )
            {
                $ex['id'] = $dir;
                $ex['info'] = $extensionInfo;
                $list[] = $ex;
            }
            else
            {
                $ex['id'] = $dir;
                $list[] = $ex;
            }
        }
        return $list;
    }

    static function getUntranslatableExtensions()
    {
        $list = array();
        $dirs = eZDir::findSubitems( eZExtension::baseDirectory(), false );
        $untranslatableEx = array();
        
        foreach ( $dirs as $dir )
        {
            
            $ini = eZINI::instance( 'site.ini', eZExtension::baseDirectory() . '/' . $dir . '/settings', null, null, null, true );
            
            if ( ! $ini->hasVariable( 'RegionalSettings', 'TranslationExtensions' ) )
            {
                $untranslatableEx[] = $dir;
            }
        }
        return $untranslatableEx;
    }

    static function getAllTranslationsByExtension( $extensionname )
    {
        $list = array();
        $transdir = eZExtension::baseDirectory() . '/' . $extensionname . '/translations';
        $dirs = eZDir::findSubitems( $transdir, false );
        foreach ( $dirs as $dir )
        {
            if ( $dir == 'untranslated' )
                continue;
            $langdir = $transdir . "/" . $dir;
            
            if ( is_dir( $langdir ) and is_file( $langdir . '/translation.ts' ) )
            {
                $locale1 = eZContentLanguage::fetchByLocale( $dir );
                $locale2 = eZLocale::instance( $dir );
                if ( is_object( $locale1 ) )
                    $list[] = $locale1;
                elseif ( is_object( $locale2 ) )
                    $list[] = $locale2;
            }
        }
        return $list;
    }

    // returns array with a list of all lanuages that have a translationfallback
    static function languageList()
    {
        $return = array();
        $list = eZContentLanguage::prioritizedLanguages();
        
        $translationini = eZINI::instance( 'translationmanager.ini' );
        $i18nini = eZINI::instance( 'i18n.ini' );
        $interfaceStringLocale = $translationini->variable( 'TranslationSettings', 'InterfaceStringLocale' );
        $fallbackLanguages = $i18nini->variable( 'TranslationSettings', 'FallbackLanguages' );
        
        foreach ( $list as $language )
        {
            if ( ( ! isset( $fallbackLanguages[$language->Locale] ) or ( isset( $fallbackLanguages[$language->Locale] ) ) ) and $language->Locale != $interfaceStringLocale )
            {
                $return[] = $language;
            }
        }
        return $return;
    }

    static function upload( $file )
    {
        $tmpdoc = new DOMDocument();
        $load = file_get_contents( $file );
        
        #no translation transformation
        $pattern = array('/<noxl start="(\%+)" name="([a-zA-Z0-9_ ]+)" end="(\%*)">([a-zA-Z0-9_ ]*)<\/noxl>/i', '/<location line="0"\/>/i');
        $replacement = array('${1}${2}${3}', '');
        $load = preg_replace( $pattern, $replacement, $load );
        
        if ( ! $tmpdoc->loadXML( $load ) )
        {
            eZDebug::writeError( 'Parser error, wrong doctype or encoding', __METHOD__ );
            throw new Exception( 'Parser error, wrong doctype or encoding' );
        }
        
        if ( $tmpdoc->documentElement->nodeName != xrowTranslationGUI::DOM_ROOT_NAME )
        {
            throw new Exception( 'Wrong doctype xrowTranslationGUI::upload()' );
        }
        $extension = $tmpdoc->documentElement->getAttribute( 'xmlns:ezextension' );
        $language = $tmpdoc->documentElement->getAttribute( 'xmlns:ezlanguage' );
        
        # init new doc
        $doc = new DOMDocument();
        $doc->encoding = 'UTF-8';
        $doc->preserveWhiteSpace = false;
        $newroot = $doc->createElement( "TS" );
        $doc->appendChild( $newroot );
        $elements = $tmpdoc->documentElement->getElementsByTagName( "context" );
        for ( $i = 0; $i < $elements->length; $i ++ )
        {
            
            $strings = $elements->item( $i )->getElementsByTagName( "message" );
            for ( $j = 0; $j < $strings->length; $j ++ )
            {
                $content = $strings->item( $j )->getElementsByTagName( "translated" );
                $translation = $strings->item( $j )->getElementsByTagName( "translation" );
                if ( $content->item( 0 )->firstChild )
                {
                    $translation->item( 0 )->appendChild( clone $content->item( 0 )->firstChild );
                    $translation->item( 0 )->removeAttribute( 'type' );
                    $strings->item( $j )->removeChild( $content->item( 0 ) );
                }
            }
            $importedElement = $doc->importNode( $elements->item( $i ), true );
            $newroot->appendChild( $importedElement );
        }
        $load = $doc->saveXML();
        
        $load = str_replace( "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n", "<!DOCTYPE TS>\n", $load );
        
        $dir = eZExtension::baseDirectory() . '/' . $extension . '/translations/' . $language;
        
        if ( ! is_dir( $dir ) )
        {
            eZDir::mkdir( $dir, 0777, true );
        }
        if ( eZFile::create( "translation.ts", $dir, $load ) )
        {
            @chmod( $dir . '/' . "translation.ts", 0777 );
            eZCache::clearAll();
            return true;
        }
        throw new Exception( "File $dir/translation.ts could not get created." );
    }

    static function checkDownloadDir( $extensions )
    {
        $extensionWoTrans = array();
        foreach ( $extensions as $extension )
        {
            $loaddir = eZExtension::baseDirectory() . '/' . $extension['id'] . '/translations/untranslated';
            
            if ( ! is_dir( $loaddir ) )
            {
                $extensionWoTrans[] = $extension['id'];
            }
        }
        return $extensionWoTrans;
    }

    static function download( $extensionname, $language )
    {
        
        $loadfile = eZExtension::baseDirectory() . '/' . $extensionname . '/translations/' . 'untranslated' . '/translation.ts';
        
        $load = file_get_contents( $loadfile );
        $pattern = '/<location line="0"\/>/i';
        $replacement = ' ';
        
        $load = preg_replace( $pattern, $replacement, $load );
        file_put_contents( $loadfile, $load );
        
        $tmpdoc = new DOMDocument();
        $tmpdoc->preserveWhiteSpace = false;
        $tmpdoc->loadXML( $load );
        
        if ( $tmpdoc->RelaxNGValidate( 'schemas/translation/ts.rng' ) )
        {
            // Creates an instance of the DOMImplementation class
            $imp = new DOMImplementation();
            
            // Creates a DOMDocumentType instance
            $dtd = $imp->createDocumentType( xrowTranslationGUI::DOM_ROOT_NAME, xrowTranslationGUI::DOM_QUALIFIED_NAME, xrowTranslationManager::findPublicURL() . '/extension/translationmanagement/schemas/ez-gui.dtd' );
            
            // Creates a DOMDocument instance
            $doc = $imp->createDocument( "", "", $dtd );
            
            // Set other properties
            $doc->encoding = 'UTF-8';
            $doc->standalone = false;
            $doc->formatOutput = true;
            
            $pi = $doc->createProcessingInstruction( "xml-stylesheet", 'version="2.0" type="text/xsl" href="' . xrowTranslationManager::findPublicURL() . '/extension/translationmanagement/schemas/ez-gui.xslt"' );
            $doc->appendChild( $pi );
            
            #$doc->documentElement = $tmpdoc->documentElement; 
            $root = $doc->createElement( xrowTranslationGUI::DOM_ROOT_NAME );
            $root->setAttribute( 'xmlns:ezextension', $extensionname );
            $root->setAttribute( 'xmlns:ezlanguage', $language );
            $doc->appendChild( $root );
            
            for ( $i = 0; $i < $tmpdoc->documentElement->childNodes->length; $i ++ )
            {
                $xmlContent = $doc->importNode( $tmpdoc->documentElement->childNodes->item( $i ), true );
                $doc->documentElement->appendChild( $xmlContent );
            
            }
            
            $strings = $doc->getElementsByTagName( "message" );
            for ( $i = 0; $i < $strings->length; $i ++ )
            {
                $source = $strings->item( $i )->getElementsByTagName( "source" );
                if ( $source->item( 0 ) )
                {
                    $translated = $doc->createElement( "translated" );
                    $translated->appendChild( clone $source->item( 0 )->firstChild );
                    $strings->item( $i )->appendChild( $translated );
                }
            }
            $load = $doc->saveXML();
            
            #no translation transformation
            $pattern = eZINI::Instance( 'translationmanager.ini' )->variable( 'TranslationSettings', 'TranslationFileVariableExpression' );
            $replacement = eZINI::Instance( 'translationmanager.ini' )->variable( 'TranslationSettings', 'TranslationFileVariableReplacement' );
            $load = preg_replace( $pattern, $replacement, $load );
            
            @ob_end_clean();
            header( "Content-Type: text/xml; charset=\"UTF-8\"" );
            header( "Content-Length: " . strlen( $load ) );
            header( 'Content-Disposition: attachment; filename="' . $extensionname . '-' . $language . '.xml"' );
            header( 'Content-Transfer-Encoding: binary' );
            header( 'Accept-Ranges: bytes' );
            
            echo $load;
            
            eZExecution::cleanExit();
        }
    }
}
?>