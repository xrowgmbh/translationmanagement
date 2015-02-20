<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/
class xrowLinguist
{

    function isVerbose()
    {
        global $verbose;
        return $verbose;
    }

    function checkIfPathExists( &$pathsToScan, $relativePath, $fileTypes, $recursive = true, $dieIfNotExists = true )
    {
        if ( is_dir( $relativePath ) )
        {
            $pathsToScan[] = array( 
                $relativePath , 
                $fileTypes , 
                $recursive 
            );
            return true;
        }
        else 
            if ( $dieIfNotExists )
            {
                print ( "The path $relativePath does not exist, and ezlupdate will not continue without it.\n" ) ;
                exit();
            }
            else
            {
                return false;
            }
    }

    function scanDirectory( $pathToScan, &$result )
    {
        if ( isVerbose() )
            print ( '[' . $pathToScan[0] . "]\n" ) ;
        
        if ( $pathToScan[2] ) // Recursive scan
        {
            $foundFiles = ezcBaseFile::findRecursive( $pathToScan[0], array( 
                '@.*\.(' . implode( '|', $pathToScan[1] ) . ')$@' 
            ) );
            foreach ( $foundFiles as $filePath )
            {
                if ( strpos( $filePath, '/settings/' ) !== false ) // Don't scan settings (ini) files
                    continue;
                $fileInfo = pathinfo( $filePath );
                parseFile( $filePath, $fileInfo['extension'], $result );
            }
        }
        else // One directory only
        {
            $d = dir( $pathToScan[0] );
            while ( false !== ( $entry = $d->read() ) )
            {
                $filePath = $pathToScan[0] . '/' . $entry;
                $fileInfo = pathinfo( $filePath );
                if ( is_file( $filePath ) and isset( $fileInfo['extension'] ) and in_array( $fileInfo['extension'], $pathToScan[1] ) )
                {
                    parseFile( $filePath, $fileInfo['extension'], $result );
                }
            }
            $d->close();
        }
    }

    function parseFile( $filePath, $fileExtension, &$result )
    {
        if ( isVerbose() )
            print ( $filePath . "\n" ) ;
        
        $fileExtension = strtoupper( $fileExtension );
        $parseFunction = 'parse' . $fileExtension . 'File';
        
        if ( function_exists( $parseFunction ) )
        {
            $parseFunction( $filePath, $result );
        }
        else
        {
            print ( "The parse function for $fileExtension files does not exist, the file type is not supported.\n" ) ;
            exit();
        }
    }

    function parsePHPFile( $filePath, &$result )
    {
        $content = file_get_contents( $filePath );
        $i18n_instances = getI18nStringsInPhp( $content );
        
        foreach ( $i18n_instances as $instance )
        {
            $context = $instance[0];
            $ts_string = $instance[1];
            
            if ( strlen( $context ) )
            {
                $man = eZTranslatorManager::instance();
                $trans = $man->translate( $context, $ts_string );
                if ( $trans === null )
                {
                    if ( ! isset( $result[$context] ) )
                        $result[$context] = array();
                    $result[$context][] = array( 
                        'source' => $ts_string , 
                        'location_file' => $filePath 
                    );
                    // TODO: Save line number (may require proper parsing)
                }
            }
        }
    }

    function parseTPLFile( $filePath, &$result )
    {
        $content = file_get_contents( $filePath );
        $i18n_instances = getI18nStringsInTpl( $content );
        
        foreach ( $i18n_instances as $instance )
        {
            $context = parseContextString( $instance[1] );
            $ts_string = parseTranslationString( $instance[0] );
            
            if ( strlen( $context ) )
            {
                $man = eZTranslatorManager::instance();
                $trans = $man->translate( $context, $ts_string );
                if ( $trans === null )
                {
                    if ( ! isset( $result[$context] ) )
                        $result[$context] = array();
                    $result[$context][] = array( 
                        'source' => $ts_string , 
                        'location_file' => $filePath 
                    );
                    // TODO: Save line number (may require proper parsing)
                }
            }
        }
    }

    function getI18nStringsInPhp( $content )
    {
        $return = array();
        
        $oldI18nRe = '/ezi18n\((.*?)\)/s'; // Old syntax: ezpI18n::tr( 'context', 'source' )
        $newI18nRe = '/ezpI18n::tr\((.*?)\)/s'; // New syntax: ezpI18n::tr( 'context', 'source' )
        

        foreach ( array( 
            $oldI18nRe , 
            $newI18nRe 
        ) as $re )
        {
            preg_match_all( $re, $content, $matches );
            
            $context = $source = $comment = $arguments = null; // Avoid eval PHP notice of undefined variables
            

            if ( $matches[0] )
            {
                foreach ( $matches[1] as $index => $instance )
                {
                    $php = '$values = array( ' . $instance . ' );';
                    $success = eval( $php );
                    
                    if ( $success === false )
                    {
                        print ( 'Error: Unparseable translation' . "\n" ) ;
                        print ( $php . "\n" ) ;
                    }
                    
                    $return[] = $values;
                }
            }
            
            unset( $matches );
        }
        
        return $return;
    }

    function getI18nStringsInTpl( $content )
    {
        $return = array();
        
        preg_match_all( '/{(.*?)}/is', $content, $commands );
        
        foreach ( $commands[1] as $tpl_command )
        {
            preg_match_all( '/(.*?)\|i18n\(.*?[\'"](.*?)[\'"].*?\)/is', $tpl_command, $matches );
            
            if ( count( $matches[0] ) )
            {
                foreach ( $matches[0] as $index => $translation )
                {
                    $text = strrev( $matches[1][$index] );
                    preg_match( '/[\'](.*?)[\']/is', $text, $text_out );
                    
                    if ( ! isset( $text_out[1] ) or ! count( $text_out[1] ) )
                    {
                        preg_match( '/"(.*?)"/is', $text, $text_out );
                    }
                    
                    $text = strrev( $text_out[1] );
                    
                    $return[] = array( 
                        $text , 
                        $matches[2][$index] 
                    );
                }
            }
            unset( $matches );
        }
        
        return $return;
    }

    function parseContextString( $string )
    {
        $parts = explode( ',', $string );
        $string = $parts[0];
        return $string;
    }

    function parseTranslationString( $string )
    {
        return $string;
    }

    function updateTsFile( $newResult, $extensionPath, $locale )
    {
        // Find and read the file, put entries into similar array as $newResult
        $existingResult = readExistingTsFile( $extensionPath, $locale, $usedFile );
        
        // Merge the arrays
        // print( "Existing: " ); print_r( $existingResult ); print( "\n" ); // REMOVE
        // print( "New: " ); print_r( $newResult ); print( "\n" ); // REMOVE
        $mergedResult = mergeTranslations( $existingResult, $newResult );
        // print( "Merged: " ); print_r( $mergedResult ); print( "\n" ); // REMOVE
        

        // Write the results back to the file
        saveTsFile( $mergedResult, $usedFile );
    }

    function readExistingTsFile( $extensionPath, $locale, &$usedFile )
    {
        global $tsLanguage, $tsSourceLanguage, $skipRelaxNG;
        
        // First try for current charset
        $charset = eZTextCodec::internalCharset();
        $ini = eZINI::instance();
        $filename = 'translation.ts';
        
        if ( $extensionPath )
            $root = $extensionPath . '/translations';
        else
            $root = $ini->variable( 'RegionalSettings', 'TranslationRepository' );
        
        if ( ! file_exists( $root ) )
        {
            return false;
        }
        
        // first process country translation files
        // then process country variation translation files
        $localeParts = explode( '@', $locale );
        
        $result = array();
        
        foreach ( $localeParts as $localePart )
        {
            $localeCodeToProcess = isset( $localeCodeToProcess ) ? $localeCodeToProcess . '@' . $localePart : $localePart;
            
            // array with alternative subdirs to check
            $alternatives = array( 
                array( 
                    $localeCodeToProcess , 
                    $charset , 
                    $filename 
                ) , 
                array( 
                    $localeCodeToProcess , 
                    $filename 
                ) 
            );
            
            unset( $path );
            
            foreach ( $alternatives as $alternative )
            {
                $pathParts = $alternative;
                array_unshift( $pathParts, $root );
                $pathToTry = eZDir::path( $pathParts );
                
                if ( file_exists( $pathToTry ) )
                {
                    $path = $pathToTry;
                    break;
                }
            }
            
            if ( ! isset( $path ) )
            {
                continue;
            }
            
            $doc = new DOMDocument( '1.0', 'utf-8' );
            $success = $doc->load( $path );
            
            if ( ! $success )
            {
                print ( "Error: Unable to load XML from file $path\n\n" ) ;
                exit();
            }
            
            // This check is optional, which lets you use ezlupdate to fix faulty TS files
            if ( ! $skipRelaxNG and ! $doc->RelaxNGValidate( 'schemas/translation/ts.rng' ) )
            {
                print ( "Error: XML text for file $path did not validate\n\n" ) ;
                exit();
            }
            
            $usedFile = $path;
            
            $treeRoot = $doc->documentElement;
            $tsLanguage = $treeRoot->getAttribute( 'language' );
            $tsSourceLanguage = $treeRoot->getAttribute( 'sourcelanguage' );
            
            $children = $treeRoot->childNodes;
            for ( $i = 0; $i < $children->length; $i ++ )
            {
                $child = $children->item( $i );
                
                if ( $child->nodeType == XML_ELEMENT_NODE )
                {
                    if ( $child->tagName == "context" )
                    {
                        handleContextNode( $child, $result );
                    }
                }
            }
            
            break; // We will only update one translation file
        }
        
        return $result;
    }

    function handleContextNode( $context, &$result )
    {
        $contextName = null;
        $messages = array();
        $context_children = $context->childNodes;
        
        for ( $i = 0; $i < $context_children->length; $i ++ )
        {
            $context_child = $context_children->item( $i );
            if ( $context_child->nodeType == XML_ELEMENT_NODE )
            {
                if ( $context_child->tagName == "name" )
                {
                    $name_el = $context_child->firstChild;
                    if ( $name_el )
                    {
                        $contextName = $name_el->nodeValue;
                    }
                }
                break;
            }
        }
        if ( ! $contextName )
        {
            print ( "Warning: No context name found, skipping context\n" ) ;
            return false;
        }
        foreach ( $context_children as $context_child )
        {
            if ( $context_child->nodeType == XML_ELEMENT_NODE )
            {
                $childName = $context_child->tagName;
                if ( $childName == "message" )
                {
                    handleMessageNode( $contextName, $context_child, $result );
                }
                else 
                    if ( $childName == "name" )
                    {
                        /* Skip name tag, we have already handled it */
                    }
                    else
                    {
                        print ( "Warning: Unknown context element name: $childName\n" ) ;
                    }
            }
        }
        
        return true;
    }

    function handleMessageNode( $contextName, $message, &$result )
    {
        $source = null;
        $translation = null;
        $translation_unfinished = null;
        $comment = null;
        $translator_comment = null;
        $location_file = null;
        $location_line = null;
        $message_children = $message->childNodes;
        for ( $i = 0; $i < $message_children->length; $i ++ )
        {
            $message_child = $message_children->item( $i );
            if ( $message_child->nodeType == XML_ELEMENT_NODE )
            {
                $childName = $message_child->tagName;
                if ( $childName == "source" )
                {
                    if ( $message_child->childNodes->length > 0 )
                    {
                        $source = '';
                        foreach ( $message_child->childNodes as $textEl )
                        {
                            if ( $textEl instanceof DOMText )
                            {
                                $source .= $textEl->nodeValue;
                            }
                            else 
                                if ( $textEl instanceof DOMElement && $textEl->tagName == 'byte' )
                                {
                                    $source .= chr( intval( '0' . $textEl->getAttribute( 'value' ) ) );
                                }
                        }
                    }
                }
                else 
                    if ( $childName == "translation" )
                    {
                        if ( $message_child->childNodes->length > 0 )
                        {
                            $translation = '';
                            foreach ( $message_child->childNodes as $textEl )
                            {
                                if ( $textEl instanceof DOMText )
                                {
                                    $translation .= $textEl->nodeValue;
                                }
                                else 
                                    if ( $textEl instanceof DOMElement && $textEl->tagName == 'byte' )
                                    {
                                        $translation .= chr( intval( '0' . $textEl->getAttribute( 'value' ) ) );
                                    }
                            }
                        }
                        
                        if ( $message_child->hasAttribute( 'type' ) and $message_child->getAttribute( 'type' ) == 'unfinished' )
                        {
                            $translation_unfinished = true;
                        }
                    }
                    else 
                        if ( $childName == "comment" )
                        {
                            $comment_el = $message_child->firstChild;
                            $comment = $comment_el->nodeValue;
                        }
                        else 
                            if ( $childName == "translatorcomment" )
                            {
                                $translator_comment_el = $message_child->firstChild;
                                $translator_comment = $translator_comment_el->nodeValue;
                            }
                            else 
                                if ( $childName == "location" )
                                {
                                    if ( $message_child->hasAttribute( 'filename' ) )
                                        $location_file = $message_child->getAttribute( 'filename' );
                                    else
                                        $location_file = '';
                                    
                                    if ( $message_child->hasAttribute( 'line' ) and is_numeric( $message_child->getAttribute( 'line' ) ) )
                                        $location_line = $message_child->getAttribute( 'line' );
                                    else
                                        $location_line = 0;
                                }
                                else
                                {
                                    print ( "Warning: Unknown message element name: $childName\n" ) ;
                                }
            }
        }
        
        if ( $source === null )
        {
            print ( "Warning: No source name found, skipping message $contextName\n" ) ;
            return false;
        }
        
        /* we need to convert ourselves if we're using libxml stuff here */
        if ( $message instanceof DOMElement )
        {
            $codec = eZTextCodec::instance( "utf8" );
            $source = $codec->convertString( $source );
            $translation = $codec->convertString( $translation );
            $comment = $codec->convertString( $comment );
        }
        
        if ( ! isset( $result[$contextName] ) )
            $result[$contextName] = array();
        $result[$contextName][] = array( 
            'source' => $source , 
            'translation' => $translation , 
            'translation_unfinished' => $translation_unfinished , 
            'comment' => $comment , 
            'translator_comment' => $translator_comment , 
            'location_file' => $location_file , 
            'location_line' => $location_line 
        );
        
        return true;
    }

    function mergeTranslations( $existingResult, $newResult )
    {
        global $removeObsolete;
        
        $mergedResult = array();
        
        foreach ( $existingResult as $context => $existingMessages )
        {
            foreach ( $existingMessages as $existingMessage )
            {
                $foundMessage = false;
                foreach ( $newResult[$context] as $key => $newMessage )
                {
                    if ( $existingMessage['source'] == $newMessage['source'] )
                    {
                        $foundMessage = true;
                        
                        // Update message properties
                        foreach ( array( 
                            'comment' , 
                            'location_file' , 
                            'location_line' 
                        ) as $messageProperty )
                        {
                            if ( isset( $newMessage[$messageProperty] ) )
                                $existingMessage[$messageProperty] = $newMessage[$messageProperty];
                        }
                        
                        // Unset used messages in $newResult, we don't need them anymore
                        unset( $newResult[$context][$key] );
                        
                        break;
                    }
                }
                
                // Any message in $existingResult that is not present in $newResult, is obsolete
                if ( ! $foundMessage )
                {
                    if ( $removeObsolete ) // Obsolete messages should be removed
                        continue; // Don't add it to $mergedResult
                    else // Obsolete messages are kept but marked as such
                        $existingMessage['obsolete'] = true;
                }
                
                if ( ! isset( $mergedResult[$context] ) )
                    $mergedResult[$context] = array();
                $mergedResult[$context][] = $existingMessage;
            }
        }
        
        // Any message in $newResult that is not present in $existingResult, is added
        foreach ( $newResult as $context => $newMessages )
        {
            foreach ( $newMessages as $newMessage )
            {
                $foundMessage = false;
                if ( isset( $existingResult[$context] ) )
                {
                    foreach ( $existingResult[$context] as $existingMessage )
                    {
                        if ( $newMessage['source'] == $existingMessage['source'] )
                        {
                            $foundMessage = true;
                            break;
                        }
                    }
                }
                
                if ( ! $foundMessage )
                {
                    if ( ! isset( $mergedResult[$context] ) )
                        $mergedResult[$context] = array();
                    $mergedResult[$context][] = $newMessage;
                }
            }
        }
        
        return $mergedResult;
    }

    function saveTsFile( $mergedResult, $filename )
    {
        // print( "Saving to file: $filename\n" ); // REMOVE
        global $tsLanguage, $tsSourceLanguage;
        
        $imp = new DOMImplementation();
        $dtd = $imp->createDocumentType( 'TS' );
        
        $doc = $imp->createDocument( '', '', $dtd );
        $doc->xmlVersion = '1.0';
        $doc->encoding = 'utf-8';
        $doc->formatOutput = true;
        
        $tsEl = $doc->createElement( 'TS' );
        $doc->appendChild( $tsEl );
        $versionAttr = $doc->createAttribute( 'version' );
        $tsEl->appendChild( $versionAttr );
        $versionAttr->appendChild( $doc->createTextNode( '2.0' ) );
        
        // Set language and sourcelanguage if they exist in original file
        if ( strlen( $tsLanguage ) > 0 )
        {
            $languageAttr = $doc->createAttribute( 'language' );
            $tsEl->appendChild( $languageAttr );
            $languageAttr->appendChild( $doc->createTextNode( $tsLanguage ) );
        }
        if ( strlen( $tsSourceLanguage ) > 0 )
        {
            $sourceLanguageAttr = $doc->createAttribute( 'sourcelanguage' );
            $tsEl->appendChild( $sourceLanguageAttr );
            $sourceLanguageAttr->appendChild( $doc->createTextNode( $tsSourceLanguage ) );
        }
        
        foreach ( $mergedResult as $context => $messages )
        {
            $contextEl = $doc->createElement( 'context' );
            $tsEl->appendChild( $contextEl );
            
            $nameEl = $doc->createElement( 'name' );
            $contextEl->appendChild( $nameEl );
            $nameEl->appendChild( $doc->createTextNode( $context ) );
            
            foreach ( $messages as $message )
            {
                $messageEl = $doc->createElement( 'message' );
                $contextEl->appendChild( $messageEl );
                
                if ( isset( $message['location_file'] ) )
                {
                    $locationEl = $doc->createElement( 'location' );
                    $messageEl->appendChild( $locationEl );
                    
                    $filenameAttr = $doc->createAttribute( 'filename' );
                    $locationEl->appendChild( $filenameAttr );
                    $filenameAttr->appendChild( $doc->createTextNode( $message['location_file'] ) );
                    
                    $lineAttr = $doc->createAttribute( 'line' );
                    $locationEl->appendChild( $lineAttr );
                    $lineAttr->appendChild( $doc->createTextNode( $message['location_line'] ) );
                }
                
                $sourceEl = $doc->createElement( 'source' );
                $messageEl->appendChild( $sourceEl );
                $sourceEl->appendChild( $doc->createTextNode( $message['source'] ) );
                
                $translationEl = $doc->createElement( 'translation' );
                $messageEl->appendChild( $translationEl );
                if ( isset( $message['translation'] ) ) // A translation may not exist
                    $translationEl->appendChild( $doc->createTextNode( $message['translation'] ) );
                else
                    $translationEl->appendChild( $doc->createTextNode( '' ) ); // Set empty string, to avoid collapsed tag. Good for plain text editors.
                if ( isset( $message['obsolete'] ) )
                {
                    $typeAttr = $doc->createAttribute( 'type' );
                    $translationEl->appendChild( $typeAttr );
                    $typeAttr->appendChild( $doc->createTextNode( 'obsolete' ) );
                }
                else 
                    if ( isset( $message['translation_unfinished'] ) or ! isset( $message['translation'] ) or strlen( $message['translation'] ) == 0 )
                    {
                        $typeAttr = $doc->createAttribute( 'type' );
                        $translationEl->appendChild( $typeAttr );
                        $typeAttr->appendChild( $doc->createTextNode( 'unfinished' ) );
                    }
                
                if ( isset( $message['comment'] ) )
                {
                    $commentEl = $doc->createElement( 'comment' );
                    $messageEl->appendChild( $commentEl );
                    $commentEl->appendChild( $doc->createTextNode( $message['comment'] ) );
                }
                
                if ( isset( $message['translator_comment'] ) )
                {
                    $translatorCommentEl = $doc->createElement( 'translatorcomment' );
                    $messageEl->appendChild( $translatorCommentEl );
                    $translatorCommentEl->appendChild( $doc->createTextNode( $message['translator_comment'] ) );
                }
            }
        }
        
        if ( $doc->save( $filename ) === false )
            print ( "Error: Could not save to file: $filename\n" ) ;
    
    }
}
?>