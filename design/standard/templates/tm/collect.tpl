{def $count_selected = ""
	 $node = ""
	 $local_code = ""
	 $can_translate = ""}
	 
{if $errors.no-object}
    <div class="message-error">
        <h2>{'No objects found for translation.'|i18n('extension/tm')}</h2>
    </div>
{/if}
{if $errors.no-translators}
    <div class="message-error">
        <h2>{'No translators found for translation.'|i18n('extension/tm')}</h2>
    </div>
{/if}
{if $errors.no-approvers}
    <div class="message-error">
        <h2>{'No approvers found for translation.'|i18n('extension/tm')}</h2>
    </div>
{/if}
<form id="collect" name="collect" method="post" action={'tm/collect'|ezurl}>
	<div class="context-block">
		{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
		<h1 class="context-title">{'Translation collection'|i18n('extension/tm')}</h1>
		{* DESIGN: Mainline *}<div class="header-mainline"></div>
		{* DESIGN: Header END *}</div></div></div></div></div></div>
		{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">
		{if $Subtree|count|gt(0)}
			<table class="list"  cellspacing="0">
				<tr class="bgdark">
					<th class="tight">
                        <img src={'toggle-button-16x16.gif'|ezimage} alt="{'Invert selection.'|i18n( 'design/admin/node/view/full' )}" title="{'Invert selection.'|i18n( 'design/admin/node/view/full' )}" onclick="ezjs_toggleCheckboxes( document.collect, 'RemoveNodeArray[]' ); return false;"/>
                        {'Remove selected'|i18n('extension/tm')}
                    </th>
					<th class="tight">
						<img src={'toggle-button-16x16.gif'|ezimage} alt="{'Invert selection.'|i18n( 'design/admin/node/view/full' )}" title="{'Invert selection.'|i18n( 'design/admin/node/view/full' )}" onclick="ezjs_toggleJQuerySelection( $('#collect').find('input[name*=with_children][type=checkbox]') ); return false;"/>
						{'With children'|i18n('extension/tm')}
					</th>
					<th class="tight">
						<img src={'toggle-button-16x16.gif'|ezimage} alt="{'Invert selection.'|i18n( 'design/admin/node/view/full' )}" title="{'Invert selection.'|i18n( 'design/admin/node/view/full' )}" onclick="ezjs_toggleJQuerySelection( $('#collect').find('input[name*=without_hidden][type=checkbox]') ); return false;"/>
						{'Exclude hidden'|i18n('extension/tm')}
					</th>
					<th class="tight">
						<img src={'toggle-button-16x16.gif'|ezimage} alt="{'Invert selection.'|i18n( 'design/admin/node/view/full' )}" title="{'Invert selection.'|i18n( 'design/admin/node/view/full' )}" onclick="ezjs_toggleJQuerySelection( $('#collect').find('input[name*=without_root][type=checkbox]') ); return false;"/>
						{'Exclude root node'|i18n('extension/tm')}
					</th>
					<th>{'Node'|i18n('extension/tm')}</th>
					<th class="tight">{'Total available'|i18n('extension/tm')}</th>
					<th class="tight">{'Total selected'|i18n('extension/tm')}</th>
				</tr>
				{foreach $Subtree as $key => $node_data}
					{def $count=fetch( 'content', 'tree_count', hash( 'parent_node_id', $node_data.node))}
					{if $node_data.without_hidden}
						{set $count_selected=fetch( 'content', 'tree_count', hash( 'parent_node_id', $node_data.node, 'ignore_visibility', false() ))}
					{else}
						{set $count_selected=fetch( 'content', 'tree_count', hash( 'parent_node_id', $node_data.node, 'ignore_visibility', true() ))}
					{/if}
					{if $node_data.without_root|not}
						{set $count_selected=$count_selected|sum(1)}
					{/if}
					{set $node=fetch( 'content','node', hash( 'node_id', $node_data.node ) )}
					<tr class="bgdark">
						<td><input name="RemoveNodeArray[]" type="checkbox" value="{$key}" /></td>
						<td>{if $count|gt(0)}<input name="Subtree[{$key}][with_children]" type="checkbox" value="1" {if $node_data.with_children} checked="checked"{/if} />{/if}</td>
						<td>{if $count|gt(0)}<input name="Subtree[{$key}][without_hidden]" type="checkbox" value="1" {if $node_data.without_hidden} checked="checked"{/if} />{/if}</td>
						<td>{if $count|gt(0)}<input name="Subtree[{$key}][without_root]" type="checkbox" value="1" {if $node_data.without_root} checked="checked"{/if} />{/if}</td>
						<td>
						  {node_view_gui view=line content_node=$node}
						  <input name="Subtree[{$key}][node]" type="hidden" value="{$node_data.node}" />
						</td>
						<td>{if $count|gt(1)}{$count|sum(1)}{else}1{/if}</td>
						<td>
							{if and( $node_data.with_children, $count_selected|gt(0) )}
								{$count_selected}
							{else}
							1
							{/if}
						</td>
					</tr>
                    {set $node = ""}
					{undef $count $count_selected}
				{/foreach}
                {undef $node}
			</table>
		{else}
			<div class="context-attributes">
				<p>{'Please browse for nodes which you want to include in the translation collaboration.'|i18n('extension/tm')}</p>
			</div>
		{/if}
		{* DESIGN: Content END *}</div></div></div>
		<div class="controlbar">
			{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
				<div class="block">
					<div class="button-left">
						<input class="button{if $Subtree|count|eq(0)}-disabled{/if}" type="submit" name="Remove" value="{'Remove'|i18n('extension/tm')}" />
						<input class="button{if $Subtree|count|eq(0)}-disabled{/if}" name="Update" type="submit" value="{'Update'|i18n('extension/tm')}" />
					    <input class="button" type="submit" name="BrowseSubtree" value="{'Browse'|i18n('extension/tm')}" />
					</div>
					<div class="button-right"></div>
					<div class="break"></div>
				</div>
			{* DESIGN: Control bar END *}</div></div></div></div></div></div>
		</div>
	</div>
	
	<div class="context-block">
		<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
		<h2 class="context-title">{'Settings'|i18n('extension/tm')}</h2>
		<div class="header-subline"></div>
		</div></div></div></div></div></div>
		<div class="box-ml"><div class="box-mr"><div class="box-content">
		<div class="context-attributes">
			<p>{'Languages with no assignments to translators are considerd to be translated by you.'|i18n('extension/tm')}</p>
			<table class="list"  cellspacing="0">
				<tr class="bglight">
					<th width="1%">{'Source language'|i18n('extension/tm')}</th>
					<td>
						{def $languages=fetch( 'content', 'prioritized_languages' )}
						<select name="initial_language">
							{foreach $languages as $language}
								<option value="{$language.locale}"{if $language.locale|eq($Source)} selected="selected"{/if}>{$language.name}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr class="bgdark">
					<th>{'Include languages'|i18n('extension/tm')}</th>
					<td>
						<select id="languages" name="language_code_include[]" size="{$languages|count}" multiple="multiple">
							{foreach $languages as $language}
								{if $language.locale|ne($Source)}
									<option value="{$language.locale}"{if $language_code_include|contains($language.locale)} selected="selected"{/if}>{$language.name}</option>
								{/if}
							{/foreach}
						</select>
					</td>
				</tr>
				<tr class="bglight">
					<th>{'Deadline'|i18n('extension/tm')}</th>
					<td>
						<div class="block">
						    <div class="element">
						        <label>{'Day'|i18n('extension/tm')} <input type="text" value="{$Deadline.day}" name="deadline_day" size="2" maxlength="2" /></label>
						    </div>
						    <div class="element">
						        <label>{'Month'|i18n('extension/tm')} <input type="text" value="{$Deadline.month}" name="deadline_month"  size="2" maxlength="2" /></label>
						    </div>
						    <div class="element">
						        <label>{'Year'|i18n('extension/tm')} <input type="text" value="{$Deadline.year}" name="deadline_year"  size="4" maxlength="4" /></label>
						    </div>
						</div>
						<div class="break"></div>
					</td>
				</tr>
			</table>
		</div>
		{* DESIGN: Content END *}</div></div></div>
		
		<div class="controlbar">
			{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
			<div class="block">
		        <div class="button-left">
					<input class="button{if $Subtree|count|eq(0)}-disabled{/if}" name="Update" type="submit" value="{'Update'|i18n('extension/tm')}" />
		        </div>
			    <div class="button-right">
				  <input class="button{if $Subtree|count|eq(0)}-disabled{/if}" name="Download" type="submit" value="{'Download for as-is analysis'|i18n('extension/tm')}" />
			    </div>
			    <div class="break"></div>
		    </div>
			{* DESIGN: Control bar END *}</div></div></div></div></div></div>
		</div>
	</div>
	
	<div class="context-block">
		<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
		<h2 class="context-title">{'Translators and translation checkers'|i18n('extension/tm')}</h2>
		<div class="header-subline"></div>
		</div></div></div></div></div></div>
		<div class="box-ml"><div class="box-mr"><div class="box-content">
		{if $language_code_include|count|gt(0)}
			{def $translators_list=fetch( 'tm', 'users' )}
			<table class="list" cellspacing="0">
				<tr class="bgdark">
					<th>{'Translators'|i18n('extension/tm')}</th>
					<td>
						<div class="block">
							{foreach $languages as $key => $language }
								{set $local_code = $language.locale}
								{if and( $language.locale|ne($Source), $language_code_include|contains($language.locale) )}
									<div class="element">
										<label>{$language.name}</label>
										<select name="translators[{$language.locale}][]" size="4">
											{foreach $translators_list as $translator sequence array("bgdark", "bglight") as $sequence}
												{set $can_translate=fetch('tm','can_translate', hash( language, $language.locale, user_id, $translator.contentobject_id ))}
												{if $can_translate}
													<option value="{$translator.contentobject_id}" {if $translators.$local_code|contains($translator.contentobject_id)}selected="selected"{/if}>{$translator.name}</option>
												{/if}
											{/foreach}
										</select>
									</div>
								{/if}
							{/foreach}
						</div>
					</td>
				</tr>
				<tr class="bgdark">
					<th>{'Translation checker'|i18n('extension/tm')}</th>
					<td>
						<div class="block">
							{foreach $languages as $key => $language }
								{set $local_code = $language.locale}
								{if and( $language.locale|ne($Source), $language_code_include|contains($language.locale) )}
									<div class="element">
										<label>{$language.name}</label>
										<select name="approvers[{$language.locale}][]" size="4">
											{foreach $translators_list as $translator sequence array("bgdark", "bglight") as $sequence}
												{def $can_approve=fetch('tm','can_approve', hash( language, $language.locale, user_id, $translator.contentobject_id ))}
												{if $can_approve}
													<option value="{$translator.contentobject_id}" {if $approvers.$local_code|contains($translator.contentobject_id)}selected="selected"{/if}>{$translator.name}</option>
												{/if}
												{undef $can_approve}
											{/foreach}
										</select>
									</div>
								{/if}
							{/foreach}
						</div>
					</td>
				</tr>
                <tr class="bgdark">
                    <th>
                        {'Publish without editor approval'|i18n('extension/tm')}
                    </th>
                    
                    {if ezini( 'TranslationSettings', 'PreSelectionPublishWithoutEditorApproval', 'translationmanager.ini' )|eq("true")}
                    	{def $checked = "true"}
                    {else}
                    	{def $checked = "false"}
                    {/if}
                    {if $auto_accept|eq('on')}
                    	{set $checked = "true"}
                    {/if}
                    
                    <td>					
						{*SB: Here we need a little workaround to manipulate the checkbox in the right way. (hold the choosen Value after i.e. updating the languages)*}
						<input type="hidden" name="accept_field" value="{$auto_accept}" />
                        <input type="checkbox" {if $checked|eq("true")}checked="checked"{/if} name="auto_accept" />											
                    </td>
                </tr>
			</table>
		{else}
			<div class="context-attributes">
				<p>{'Please select a language to assign translators and translation checkers.'|i18n('extension/tm')}</p>
			</div>
		{/if}
		{* DESIGN: Content END *}</div></div></div>
		<div class="controlbar">
			{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
			<div class="block">
				<div class="button-left">
					<input class="button{if $Subtree|count|eq(0)}-disabled{/if}" name="Translate" type="submit" value="{'Translate'|i18n('extension/tm')}" />   
				</div>
				<div class="break"></div>
		    </div>
			{* DESIGN: Control bar END *}</div></div></div></div></div></div>
		</div>
	</div>
</form>
{literal}
	<script type="text/javascript">
	$('#languages').mouseleave(function() {
		$('#collect').submit();
	});
	
	function ezjs_toggleJQuerySelection( list )
	{
		for( var i = 0; i < list.length; i++ )
		{
			if( list[i].type === 'checkbox' && list[i].disabled == false )
			{
				if( list[i].checked == true )
				{
					list[i].checked = false;
				}
				else
				{
					list[i].checked = true;
				}
			}
		}
	}
	</script>
{/literal}