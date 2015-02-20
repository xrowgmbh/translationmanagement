<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/

$FunctionList = array();

$FunctionList['status'] = array( 'name' => 'status',
                                'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                        'class' => 'eztmfunctioncollection',
                                                        'method' => 'status' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'contentobject_id',
                                                              'type' => 'string',
                                                              'required' => true ),
                                                              array( 'name' => 'version',
                                                              'type' => 'string',
                                                              'required' => true ),
                                                              array( 'name' => 'language',
                                                              'type' => 'string',
                                                              'required' => false ),
                                                              array( 'name' => 'process_id',
                                                              'type' => 'string',
                                                              'required' => false )
                                                               ) );
$FunctionList['work_object_status'] = array( 'name' => 'work_object_status',
                                'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                        'class' => 'eztmfunctioncollection',
                                                        'method' => 'workObjectStatus' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'process_id',
                                                              'type' => 'string',
                                                              'required' => true ),
                                                              array( 'name' => 'contentobject_id',
                                                              'type' => 'string',
                                                              'required' => true ),
                                                              array( 'name' => 'version',
                                                              'type' => 'string',
                                                              'required' => true ) ) );
$FunctionList['participant_map'] = array( 'name' => 'participant_map',
                                          'operation_types' => array( 'read' ),
                                          'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                                  'class' => 'eztmfunctioncollection',
                                                                  'method' => 'fetchParticipantMap' ),
                                           'parameter_type' => 'standard',
                                          'parameters' => array( array( 'name' => 'item_id',
                                                                        'required' => false,
                                                                        'default' => false ),
                                                                 array( 'name' => 'offset',
                                                                        'required' => false,
                                                                        'default' => false ),
                                                                 array( 'name' => 'limit',
                                                                        'required' => false,
                                                                        'default' => false ),
                                                                 array( 'name' => 'field',
                                                                        'required' => false,
                                                                        'default' => false ) ) );
$FunctionList['process_by_process_id'] = array( 'name' => 'process_by_process_id',
                                'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                        'class' => 'eztmfunctioncollection',
                                                        'method' => 'fetchProcessByProcessID' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'process_id',
                                                              'type' => 'integer',
                                                              'required' => true )
                                                               ) );
$FunctionList['userobjects_by_process_id'] = array( 'name' => 'userobjects_by_process_id',
                                'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                        'class' => 'eztmfunctioncollection',
                                                        'method' => 'fetchUserObjectsByProcessID' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'process_id',
                                                              'type' => 'integer',
                                                              'required' => true )
                                                               ) );
$FunctionList['objects_by_process_id'] = array( 'name' => 'objects_by_process_id',
                                'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                        'class' => 'eztmfunctioncollection',
                                                        'method' => 'fetchObjectsByProcessID' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'process_id',
                                                              'type' => 'integer',
                                                              'required' => true ),
                                                       array( 'name' => 'sort_by',
                                                              'type' => 'string',
                                                              'required' => false,
                                                              'default' => 'class_name' ),
                                                       array( 'name' => 'sort_method',
                                                              'type' => 'string',
                                                              'required' => false,
                                                              'default' => 'asc' )
                                                               ) );
$FunctionList['available_languages'] = array( 'name' => 'available_languages',
                                'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                        'class' => 'eztmfunctioncollection',
                                                        'method' => 'availableLanguages' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'version',
                                                              'type' => 'object',
                                                              'required' => true )
                                                               ) );
$FunctionList['available_languages_by_process'] = array( 'name' => 'available_languages',
                                'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                        'class' => 'eztmfunctioncollection',
                                                        'method' => 'availableLanguages' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'process',
                                                              'type' => 'integer',
                                                              'required' => true )
                                                               ) );
$FunctionList['can_translate'] = array( 'name' => 'can_translate',
                                'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                        'class' => 'eztmfunctioncollection',
                                                        'method' => 'canTranslate' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'language',
                                                              'type' => 'string',
                                                              'required' => true ),
                                                       array( 'name' => 'user_id',
                                                              'type' => 'string',
                                                              'required' => false )
                                                               ) );
$FunctionList['can_approve'] = array( 'name' => 'can_approve',
                                'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                        'class' => 'eztmfunctioncollection',
                                                        'method' => 'canApprove' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'language',
                                                              'type' => 'string',
                                                              'required' => true ),
                                                       array( 'name' => 'user_id',
                                                              'type' => 'string',
                                                              'required' => false )
                                                               ) );
$FunctionList['users'] = array( 'name' => 'users',
                                'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                        'class' => 'eztmfunctioncollection',
                                                        'method' => 'users' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( ) );
$FunctionList['translating_role_ids_by_language'] = array( 'name' => 'translating_role_ids_by_language',
                                'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                        'class' => 'eztmfunctioncollection',
                                                        'method' => 'getRolesByLanguage' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'language',
                                                              'type' => 'string',
                                                              'required' => true ),
                                                       array( 'name' => 'type',
                                                              'type' => 'string',
                                                              'required' => false )
                                                               ) );                                
$FunctionList['collaboration_item'] = array( 'name' => 'collaboration_item',
                                'call_method' => array( 'include_file' => 'extension/translationmanagement/modules/tm/eztmfunctioncollection.php',
                                                        'class' => 'eztmfunctioncollection',
                                                        'method' => 'collaborationItem' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'contentobject_id',
                                                              'type' => 'string',
                                                              'required' => true ),
                                                              array( 'name' => 'version',
                                                              'type' => 'string',
                                                              'required' => true )
                                                               ) );                                                             
?>