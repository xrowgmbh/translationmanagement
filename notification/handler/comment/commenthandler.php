<?php
/**
* @copyright Copyright (C) 1999-2011 xrow GmbH. All rights reserved.
* @license [EXTENSION_LICENSE]
* @version [EXTENSION_VERSION]
* @package translationmanagement
*/
class CommentHandler extends eZCollaborationNotificationHandler
{
	const COMMENT_ID = 'comment' ;
    /*!
     Constructor
    */
    function CommentHandler()
    {
        $this->eZNotificationEventHandler( CommentHandler::COMMENT_ID, "Collaboration Handler Comment" );
    }

    function handle( $event )
    {
        eZDebugSetting::writeDebug( 'kernel-notification', $event, "trying to handle event" );
        if ( $event->attribute( 'event_type_string' ) == 'comment' )
        {
            $ini = eZINI::instance( "site.ini" );
            $mailConnection = array();
            $mailSender = array();
            $mailSender['name'] = $ini->variable( "SiteSettings", "SiteName" );
            $mailSender['email'] = $ini->variable( "MailSettings", "EmailSender" );
            $mailConnection['Transport'] = $ini->variable( "MailSettings", "Transport" );
            $mailConnection['Server'] = $ini->variable( "MailSettings", "TransportServer" );
            $mailConnection['Port'] = $ini->variable( "MailSettings", "TransportPort" );
            $mailConnection['User'] = $ini->variable( "MailSettings", "TransportUser" );
            $mailConnection['Password'] = $ini->variable( "MailSettings", "TransportPassword" );
            if ( $mailConnection['Transport'] == "sendmail" )
            {
                $transport = new ezcMailMtaTransport();
            }
            elseif ( $mailConnection['Transport'] == "smtp" )
            {
                $options = new ezcMailSmtpTransportOptions();
                $options->connectionType = ezcMailSmtpTransport::CONNECTION_PLAIN;
                $transport = new ezcMailSmtpTransport( $mailConnection['Server'], $mailConnection['User'], $mailConnection['Password'], $mailConnection['Port'], $options );
            }
            $mail = new ezcMail();
            $mail->from = new ezcMailAddress( $mailSender['email'], $mailSender['name'], 'utf-8' );

            $collections = eZNotificationCollection::fetchListForHandler( self::NOTIFICATION_HANDLER_ID,
                                                                          $event->attribute( 'id' ),
                                                                          'ezcmail' );
            foreach ( $collections as $collection )
            {
                $items = $collection->attribute( 'items_to_send' );
                $mail->subject = $collection->attribute( 'data_subject' );
                $mail->body = new ezcMailText( $collection->attribute( 'data_text' ), 'utf-8' );
                foreach ( $items as $item )
                {
                    $mail->addTo( new ezcMailAddress( $item->attribute( 'address' ), '', 'utf-8' ) );
                    $item->remove();
                }
                if ( $mailConnection['Transport'] != 'file' )
                {
                    try
                    {
                        $transport->send( $mail );
                    }
                    catch ( ezcMailException $e )
                    {
                        eZDebug::writeError( $e->getMessage(), __METHOD__ );
                        return false;
                    }
                }
                if ( $collection->attribute( 'item_count' ) == 0 )
                {
                    $collection->remove();
                }
            }
        }
        return true;
    }
}

?>
