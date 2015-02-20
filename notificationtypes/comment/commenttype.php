<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/
class CommentEventType extends eZNotificationEventType
{
	const COLLABORATION_COMMENT = 'comment' ;

    function CommentEventType()
    {
        $this->eZNotificationEventType( CommentEventType::COLLABORATION_COMMENT );
    }

    function initializeEvent( &$event, $params )
    {
        eZDebugSetting::writeDebug( 'kernel-notification', $params, 'params for type collaboration' );
        $event->setAttribute( 'data_int1', $params['collaboration_id'] );
        $event->setAttribute( 'data_text1', $params['collaboration_identifier'] );
    }
    function attributes()
    {
        return array_merge( array( 'collaboration_identifier',
                                   'collaboration_id' ),
                            eZNotificationEventType::attributes() );
    }

    function hasAttribute( $attributeName )
    {
        return in_array( $attributeName, $this->attributes() );
    }

    function attribute( $attributeName )
    {
        if ( $attributeName == 'collaboration_identifier' )
            return eZNotificationEventType::attribute( 'data_text1' );
        else if ( $attributeName == 'collaboration_id' )
            return eZNotificationEventType::attribute( 'data_int1' );
        else
            return eZNotificationEventType::attribute( $attributeName );
    }

    function eventContent( &$event )
    {
        return eZCollaborationItem::fetch( $event->attribute( 'data_int1' ) );
    }
}

eZNotificationEventType::register( CommentEventType::COLLABORATION_COMMENT, 'commenteventtype' );

?>
