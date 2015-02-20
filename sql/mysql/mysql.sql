/* Uninstall and Cleanup 

TRUNCATE `ezapprove_items`;
TRUNCATE `ezcollab_group`;
TRUNCATE `ezcollab_item`;
TRUNCATE `ezcollab_item_group_link`;
TRUNCATE `ezcollab_item_message_link`;
TRUNCATE `ezcollab_item_participant_link`;
TRUNCATE `ezcollab_item_status`;
TRUNCATE `ezcollab_notification_rule`;
TRUNCATE `ezcollab_profile`;
TRUNCATE `ezcollab_simple_message`;
TRUNCATE `ezworkflow_process`;
TRUNCATE `ezxtranslationmanagement`;
*/
/* Setup and Install */
DROP TABLE IF EXISTS `ezxtranslationmanagement`;
CREATE TABLE `ezxtranslationmanagement` (
  `contentobject_id` int(11) NOT NULL default '0',
  `contentobject_version` int(11) NOT NULL default '0',
  `language` varchar(6) NOT NULL default '',
  `translated_from` varchar(6) NOT NULL default '',
  `status` int(2) NOT NULL default '0',
  `deadline` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `data_text` longtext,
  `process_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  PRIMARY KEY  (`contentobject_id`,`contentobject_version`,`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;