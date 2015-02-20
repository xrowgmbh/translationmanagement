{def $object_languages=fetch('tm','available_languages', hash( version, $draft ) )}
<table class="tm-process">
	{foreach $object_languages as $key => $translation sequence array("bgdark", "bglight") as $sequence}
		{def $tm = fetch('tm','status', hash( contentobject_id, $object.id, version, $draft.version, language, $translation.locale, process_id,$process_id ) )}
		{if and( $tm, $tm.translated_from|ne($translation.locale) )}
		<tr>
			<td>{$translation.name}</td>
			<td>{$tm.status_name}</td>
		</tr>
		{/if}
		{undef $tm}
	{/foreach}
</table>