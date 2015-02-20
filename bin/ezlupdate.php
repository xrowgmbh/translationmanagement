#!/usr/bin/env php
<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Publish
// SOFTWARE RELEASE: 4.3.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2010 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

require( 'autoload.php' );

define( 'EZLUPDATE_HELP_INTRO', 'Create and update eZ Publish translations.' );
define( 'EZLUPDATE_PHP_TYPE', 'php' );
define( 'EZLUPDATE_TPL_TYPE', 'tpl' );

// Setup console parameters
//{
$params = new ezcConsoleInput();

$helpOption = new ezcConsoleOption( 'h', 'help' );
$helpOption->mandatory = false;
$helpOption->shorthelp = 'Show help information';
$params->registerOption( $helpOption );

$extensionOption = new ezcConsoleOption( 'e', 'extension', ezcConsoleInput::TYPE_STRING );
$extensionOption->mandatory = false;
$extensionOption->shorthelp = 'Extension mode. Scans extension EXT instead of kernel, lib and design.';
$params->registerOption( $extensionOption );

$dirsOption = new ezcConsoleOption( 'd', 'dirs', ezcConsoleInput::TYPE_STRING );
$dirsOption->mandatory = false;
$dirsOption->multiple = true;
$dirsOption->shorthelp = 'Directories to scan in addition to kernel, lib and designs.';
$params->registerOption( $dirsOption );

$untranslatedOption = new ezcConsoleOption( 'u', 'untranslated', ezcConsoleInput::TYPE_NONE );
$untranslatedOption->mandatory = false;
$untranslatedOption->shorthelp = 'Create/update the untranslated file as well.';
$params->registerOption( $untranslatedOption );

$noobsoleteOption = new ezcConsoleOption( 'no', 'noobsolete', ezcConsoleInput::TYPE_NONE );
$noobsoleteOption->mandatory = false;
$noobsoleteOption->shorthelp = 'Drop all obsolete strings.';
$params->registerOption( $noobsoleteOption );

$novalidOption = new ezcConsoleOption( '', 'novalid', ezcConsoleInput::TYPE_NONE );
$novalidOption->mandatory = false;
$novalidOption->shorthelp = 'Do not run RelaxNG XML validation.';
$params->registerOption( $novalidOption );

$utf8Option = new ezcConsoleOption( '', 'utf8', ezcConsoleInput::TYPE_NONE );
$utf8Option->mandatory = false;
$utf8Option->shorthelp = 'Assume UTF8 when the encoding is uncertain.';
$params->registerOption( $utf8Option );

$verboseOption = new ezcConsoleOption( 'v', 'verbose', ezcConsoleInput::TYPE_NONE );
$verboseOption->mandatory = false;
$verboseOption->shorthelp = 'Whether or not to display more information.';
$params->registerOption( $verboseOption );

// Add an argument for which locale to update for
$params->argumentDefinition = new ezcConsoleArguments();
$params->argumentDefinition[0] = new ezcConsoleArgument( 'locale' );
$params->argumentDefinition[0]->mandatory = false; // Not mandatory when using --help
$params->argumentDefinition[0]->shorthelp = 'Locale to update translation files for. Format: xxx-XX';
//}

// Process console parameters
//{
try
{
    $params->process();
}
catch ( ezcConsoleTooManyArgumentsException $e )
{
    print( 'Too many arguments: ' . $e->getMessage(). "\n\n" );
    print( $params->getHelpText( EZLUPDATE_HELP_INTRO ) . "\n\n" );
    exit();
}
catch ( ezcConsoleOptionException $e )
{
    print( $e->getMessage(). "\n\n" );
    print( $params->getHelpText( EZLUPDATE_HELP_INTRO ) . "\n\n" );
    exit();
}

if ( $helpOption->value === true )
{
    print( $params->getHelpText( EZLUPDATE_HELP_INTRO ) . "\n\n" );
    exit();
}

$extensionPath = $extensionOption->value;
$dirsPath = $dirsOption->value;

if ( $extensionPath and $dirsPath )
{
    print( "Error: You can't use both --extension and --dirs at the same time.\n\n" );
    print( $params->getHelpText( EZLUPDATE_HELP_INTRO ) . "\n\n" );
    exit();
}

$updateUntranslated = $untranslatedOption->value;
$removeObsolete = $noobsoleteOption->value;
$skipRelaxNG = $novalidOption->value;
$assumeUTF8 = $utf8Option->value;
$verbose = $verboseOption->value;

$locale = $params->argumentDefinition['locale']->value;
if ( !$locale )
{
    print( "Error: No locale string supplied. Please supply a valid locale string on the format: xxx-XX or xxx-XX@variation\n\n" );
    print( $params->getHelpText( EZLUPDATE_HELP_INTRO ) . "\n\n" );
    exit();
}
if ( !preg_match( '/^[a-z]{3}-[A-Z]{2}(@[0-9a-z]+)?$/', $locale ) ) // Format: xxx-XX or xxx-XX@variation
{
    print( "Invalid locale string supplied. Please supply a valid locale string on the format: xxx-XX or xxx-XX@variation\n\n" );
    print( $params->getHelpText( EZLUPDATE_HELP_INTRO ) . "\n\n" );
    exit();
}
//}

// Perform the real work
//{
// Prepare scan
$pathsToScan = array();

if ( $extensionPath ) // Scan extension only
{
    checkIfPathExists( $pathsToScan, $extensionPath, array( EZLUPDATE_PHP_TYPE, EZLUPDATE_TPL_TYPE ) );
}
else // Scan standard paths, and --dirs if it is set
{
    checkIfPathExists( $pathsToScan, '.', array( EZLUPDATE_PHP_TYPE ), false ); // ezpublish root, non recursive
    checkIfPathExists( $pathsToScan, 'bin/php', array( EZLUPDATE_PHP_TYPE ) );
    checkIfPathExists( $pathsToScan, 'cronjobs', array( EZLUPDATE_PHP_TYPE ) );
    checkIfPathExists( $pathsToScan, 'kernel', array( EZLUPDATE_PHP_TYPE ) );
    checkIfPathExists( $pathsToScan, 'lib', array( EZLUPDATE_PHP_TYPE ) );
    checkIfPathExists( $pathsToScan, 'design', array( EZLUPDATE_TPL_TYPE ) );

    if ( $dirsPath )
    {
        foreach ( $dirsPath as $dirPath )
        {
            checkIfPathExists( $pathsToScan, $dirPath, array( EZLUPDATE_PHP_TYPE, EZLUPDATE_TPL_TYPE ) );
        }
    }
}

// Do scan
$result = array();
foreach ( $pathsToScan as $pathToScan )
{
    scanDirectory( $pathToScan, $result );
}

// Sort strings per context
foreach ( $result as &$context )
{
    sort( $context );
}

// Update TS-file
$tsLanguage = $tsSourceLanguage = false;
xrowLinguist::updateTsFile( $result, $extensionPath, $locale );
