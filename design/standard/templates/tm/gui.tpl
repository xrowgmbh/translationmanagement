{if $messages.upload-success}
    <div class="message-feedback">
        <h2>{'Your upload was successful.'|i18n( 'translationmanagement' )}</h2>
    </div>
{/if}
{if $error}
    <div class="message-error">
        <h2>{$error|wash}</h2>
    </div>
{/if}
{if $extensionWOTranslationDir}
    <div class="message-feedback">
        <h2>{'Please run first ezlupdate for the following Extensions'|i18n( 'translationmanagement' )}</h2>
        	<ul>
        		{foreach $extensionWOTranslationDir as $ExWO}
        			<li>{$ExWO}</li>
        		{/foreach}
        	</ul>
    </div>
{/if}
<div class="context-block">
	{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
	<h1 class="context-title">{'Interface string translation'|i18n('extension/tm')}</h1>
	{* DESIGN: Mainline *}<div class="header-mainline"></div>
	{* DESIGN: Header END *}</div></div></div></div></div></div>
	{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">
	<div class="context-attributes">
		<p>{'In this interface you can download and upload translated interface strings.'|i18n('extension/tm')}</p>
		<h2>{'Extensions'|i18n('extension/tm')}</h2>
	</div>
	<table class="list">
		<tr>
			<th scope="row">{'Extension name'|i18n('extension/tm')}</th>
			<th scope="row">{'Existing languages'|i18n('extension/tm')}</th>
			<th scope="row"></th>
		</tr>
		{foreach $extensions as $extension sequence array("bgdark", "bglight") as $sequence}
		    <tr class="{$sequence}">
			    <td>{$extension.id}</td>
			    <td>
					{foreach $extension_info[$extension.id] as $language}
						{if $language.name}
							{$language.name}
						{else}
							{if $language.language_name}
								{$language.language_name}
							{else}
								{'Unkown language'|i18n('extension/tm')}({$language.locale_code})
							{/if}
						{/if}{delimiter}, {/delimiter}
					{/foreach}
			    </td>
				<td>
			    	<div class="right">
						<div class="block">
						    <div class="element">
							    <form name="gui" method="post" action={'tm/guidownload'|ezurl}>
								    <select name="language">
									    {foreach $languages as $language}
							            	<option value="{$language.locale}">{$language.name}</option>
									    {/foreach}
								    </select>
								    <input name="extension" type="hidden" value="{$extension.id}" />
								    <input class="button" name="Download" type="submit" value="{'Download'|i18n('extension/tm')}" />
							    </form>
						    </div>
						</div>
				    </div>
			    </td>
			</tr>
		{/foreach}
	</table>
	{* DESIGN: Content END *}</div></div></div>
	<form name="gui" method="post" action={'tm/gui'|ezurl} enctype="multipart/form-data">
		<div class="controlbar">
			{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
			<div class="block">
				<div class="button-left"></div>
				<div class="button-right">
					<div class="block">
			       		<div class="element">
					    	<label>{'File'|i18n('extension/tm')}: <input type="file" name="file" accept="text/*" /></label>
				        </div>
				        <div class="element">
				        	<input class="button" type="submit" name="Upload" value="{'Upload'|i18n('extension/tm')}" />
					    </div>
				    </div>
			    </div>
				<div class="break"></div>
			</div>
			{* DESIGN: Control bar END *}</div></div></div></div></div></div>
		</div>
	</form>
</div>

<form name="guiupdate" method="post" action={'tm/gui'|ezurl}>
	<div class="context-block">
		<div class="box-header">
			<h2 class="context-title">{'Update translation files on disk'|i18n('extension/tm')}</h2>
			<div class="header-mainline"></div>
		</div>
		{if $tsbinary|not}
		    <div class="message-error">
		        <h2>{'ezlupdate executable not found.'|i18n( 'translationmanagement' )}</h2>
		    </div>
		{else}
			<div class="box-content">
				{if $output}
					<pre>{$output}</pre>
				{else}
					<p>{'Please select a extension to be updated with new interface string from its templates.'|i18n('extension/tm')}</p>
				{/if}
				{if $tsbinary}
					<label>{'Extension'|i18n('extension/tm')}
						<select name="extension">
							<option value="">{'All'|i18n('extension/tm')}</option>
							{foreach $extensions as $extension sequence array("bgdark", "bglight") as $sequence}
								<option value="{$extension.id}">{if $extension.info.Name}{$extension.info.Name}{else}{$extension.id}{/if}</option>
							{/foreach}
						</select>
					</label>
					<label for="dropobsolete">{'Drop obsolete strings'|i18n('extension/tm')}
						<input class="checkbox" id="dropobsolete" name="dropobsolete" type="checkbox" {if and( ezini('TranslationSettings','NeverDropObsolete','translationmanager.ini')|eq('true') )}disabled="disabled"{/if} />   
					</label>
					<input class="button" name="UpdateTranslations" type="submit" value="{'Update'|i18n('extension/tm')}" />   
				{/if}
			</div>
		{/if}
	</div>
</form>
{if $untranslateableExtensions}
    <div class="message-feedback">
        <h2>{'The following Extensions are not marked as TranslationExtensions in extension/[extensionname]/settings/site.ini.append.php:'|i18n( 'translationmanagement' )}</h2>
        	<ul>
        		{foreach $untranslateableExtensions as $untEx}
        			<li>{$untEx}</li>
        		{/foreach}
        	</ul>
    </div>
{/if}