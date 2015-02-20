{set-block scope=root variable=subject}{'[%sitename] New comment on collaboration "%title"'
                                        |i18n( "extension/tm",,
                                               hash( '%sitename', ezini( "SiteSettings", "SiteURL" ),
                                                     '%title', $collaboration_item.title|wash ) )}{/set-block}
{'This e-mail is to inform you that a collaboration awaits your attention at %sitename.
The translation process can viewed by using the URL below.'
 |i18n( 'extension/tm',,
        hash( '%sitename', ezini( "SiteSettings", "SiteURL" ),
              '%objectname', $objectversion.version_name|wash ) )}
http://{ezini( "SiteSettings", "SiteURL" )}{concat( "collaboration/item/full/", $collaboration_item.id )|ezurl( no )}

{"Following comment has been added by %user"|i18n( 'extension/tm',,hash( '%user', ezini( "SiteSettings", "SiteURL" ) ) )}:

{$message.data_text1}

{"If you do not wish to continue receiving these notifications,
change your settings at:"|i18n( 'design/standard/notification' )}
http://{ezini( "SiteSettings", "SiteURL" )}{concat( "notification/settings" )|ezurl( no )}

--
{"%sitename notification system"
 |i18n( 'extension/tm',,
        hash( '%sitename', ezini( "SiteSettings", "SiteURL" ) ) )}
