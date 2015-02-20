<?php

if ( !$options['arguments'][1] or !is_dir( eZExtension::baseDirectory() . '/' . $options['arguments'][1] ))
{
	echo "no such extension";
	exit(1);
}
$search = new xrowSearchAndReplace( array( 
                '@\.tpl$@' 
), "/\\|i18n\\((['\"][^'\"]*['\"])(.*\))/mixU", '|i18n("extension/'.$options['arguments'][1].'"\2' );
$search->process( 'extension/' . $options['arguments'][1] );
