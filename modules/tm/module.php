<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/

$Module = array( "name" => ezpI18n::tr('extension/tm', 'Translation manager'),
                 "variable_params" => true,
                 "function" => array(
                 "script" => "menu.php",
                 "params" => array( ) ) );

$ViewList = array();
$ViewList["menu"] = array(
    "script" => "menu.php",
    'default_navigation_part' => 'translationmanagement',
    'functions' => array( 'use' ),
    'params' => array(  ) );
$ViewList['collect'] = array(
    'functions' => array(  'use' ),
    'default_navigation_part' => 'translationmanagement',
    'script' => 'collect.php',
    'params' => array( ),
    'single_post_actions' => array( 'Download' => 'Download',
    'Browse' => 'Browse',
    'Remove' => 'Remove',
    'Update' => 'Update',
    'Translate' => 'Translate',
    
                                    'Cancel' => 'Cancel'
                                     ),
    'post_action_parameters' => array( 'Download' => array( 'Source' => 'initial_language' ),
                                       'Browse' => array( 'Source' => 'initial_language' ),
                                       'Translate' => array( 'Source' => 'initial_language' ),
                                       'Remove' => array( 'Source' => 'initial_language' ),
                                       'Update' => array( 'Source' => 'initial_language' ) ),
	'unordered_params' => array(  ) );
$ViewList['wizard'] = array(
    'functions' => array(  'translate' ),
    'script' => 'wizard.php',
    'params' => array( ),
	'unordered_params' => array(  ) );
$ViewList['download'] = array(
    'functions' => array(  'translate' ),
    'default_navigation_part' => 'translationmanagement',
    'script' => 'download.php',
    'params' => array( 'Type' => 'Type', 'Subtype' => 'Subtype' ),
	'unordered_params' => array(  ) );
$ViewList['admin'] = array(
    'functions' => array(  'administrate' ),
    'default_navigation_part' => 'translationmanagement',
    'script' => 'admin.php',
    'single_post_actions' => array( 'Clean' => 'Clean' ),
    'params' => array( ),
	'unordered_params' => array(  ) );
$ViewList['gui'] = array(
    'functions' => array(  'use' ),
    'default_navigation_part' => 'translationmanagement',
    'script' => 'gui.php',
    'single_post_actions' => array( 'Upload' => 'Upload', 'UpdateTranslations' => 'UpdateTranslations' ),
    'post_action_parameters' => array( 'Upload' => array( 'file' => 'file' ),
                                       'UpdateTranslations' => array( 'extension' => 'extension', 'dropobsolete' => 'dropobsolete' ),
                         ),
    'params' => array( ),
	'unordered_params' => array(  ) );
$ViewList['guidownload'] = array(
    'functions' => array(  'use' ),
    'default_navigation_part' => 'translationmanagement',
    'script' => 'guidownload.php',
    'single_post_actions' => array( 'Download' => 'Download' ),
    'post_action_parameters' => array( 'Download' => array( 'extension' => 'extension', 'language' => 'language' ) ),
    'params' => array( ),
	'unordered_params' => array( ) );

$ViewList['downloadxml'] = array(
    'functions' => array(  'translate' ),
	'script' => 'downloadxml.php',
	'single_post_actions' => array( 'Download' => 'Download' ),
	'post_action_parameters' => array( 'Download' => array( 'process' => 'process', 'target' => 'target', 'source' => 'source' ) ),
	'ui_context' => '',
	"default_navigation_part" => 'translationmanagement',
	"params" => array( ),
	"unordered_params" => array(  ) );

$Language = array(
    'name'=> 'Language',
    'values'=> array(),
    'path' => 'classes/',
    'file' => 'ezcontentlanguage.php',
    'class' => 'eZContentLanguage',
    'function' => 'fetchLimitationList',
    'parameter' => array( false )
    );

$FunctionList['administrate'] = array( );
$FunctionList['use'] = array( );
$FunctionList['translate'] = array( 'Language' => $Language );
$FunctionList['approve'] = array( 'Language' => $Language );
?>