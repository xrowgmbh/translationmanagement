

{def $languages=fetch('tm','available_languages', hash( version, $draft ) )
     $all_languages=fetch('content','translation_list', hash( ) )
     $Source=$draft.initial_language.locale }

<div id="maincontent"><div id="fix">
<div id="maincontent-design">
{if $errors.no_language}
    <div class="message-error">
        <h2>{'There are currently no languages selected for translation.'|i18n( 'translationmanagement' )}</h2>
    </div>
{/if}
<!-- Maincontent START -->

<form action={$request_uri|ezurl} method="post" >
{*<form action={concat( 'tm/launch/', $process.id )|ezurl} method="post" >*}


<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{'Translation collaboration setup'|i18n( 'translationmanagement' )}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">

<div class="block">
<h1>{"Translation"|i18n("design/standard/workflow/eventtype/result")}</h1>
<p>
You may either chose to publish this object "as is" or to upload externally prepared translations based on the selected language before publishing. Please note that the language selection below is only relevant if you want to upload externally prepared translations. Use STRG + left mouse button to deselect from an option list. 
<table class="list"  cellspacing="0">
<tr class="bglight">
<th width="1%">Source language</th>
<td>

{$draft.initial_language.name}
</td>
</tr>
<tr  class="bgdark">
<th>Include existing languages</th>
<td>

<SELECT name="language_code_include[]" size="{$languages|count}" multiple>
{foreach $languages as $translation sequence array("bgdark", "bglight") as $sequence}
{if $translation.locale|ne($draft.initial_language.locale)}
<option value="{$translation.locale}"{if $language_code_include|contains($translation.locale)} selected{/if}>{$translation.name}</option>
{/if}
{/foreach}
</SELECT>
<div class="break"></div>
<input class="button" type="submit" name="TMUpdate" title="{'Select the languages you want to translate'|i18n('design/standard/content/edit_languages')}" value="{'Update'|i18n('design/standard/content/edit_languages')}" />
</td>
</tr>
<tr  class="bglight">
<th>Translators</th>
<td>

{def $translators_list=fetch( 'tm', 'users' )}

<div class="block">
{foreach $languages as $key => $language }
{if and( $language.locale|ne($Source), $language_code_include|contains($language.locale) )}

<div class="element">
<label>{$language.name}</label>
<SELECT name="translators[{$language.locale}][]" size="4">
{foreach $translators_list as $translator sequence array("bgdark", "bglight") as $sequence}
{def $can_translate=fetch('tm','can_translate', hash( language, $language.locale, user_id, $translator.contentobject_id ))}
{if $can_translate}
  <option value="{$translator.contentobject_id}">{$translator.name}</option>
{/if}
{/foreach}
</SELECT>
</div>
{/if}
{/foreach}
</div>
</td>
</tr>
<tr  class="bgdark">
<th>Translation checker</th>
<td>


<div class="block">
{foreach $languages as $key => $language }
{if and( $language.locale|ne($Source), $language_code_include|contains($language.locale) )}

<div class="element">
<label>{$language.name}</label>
<SELECT name="approvers[{$language.locale}][]" size="4">
{foreach $translators_list as $translator sequence array("bgdark", "bglight") as $sequence}
{def $can_approve=fetch('tm','can_approve', hash( language, $language.locale, user_id, $translator.contentobject_id ))}
{if $can_approve}
  <option value="{$translator.contentobject_id}">{$translator.name}</option>
{/if}
{/foreach}
</SELECT>
</div>
{/if}
{/foreach}
</div>
</td>
</tr>
<tr  class="bglight">
<th>Deadline</th>
<td>
<div class="block">
    <div class="element">
        <label>Day <input type="text" value="{$deadline.day}" name="deadline_day" size="2" maxlength="2"></label>
    </div>
    <div class="element">
        <label>Month <input type="text" value="{$deadline.month}" name="deadline_month"  size="2" maxlength="2"></label>
    </div>
    <div class="element">
        <label>Year <input type="text" value="{$deadline.year}" name="deadline_year"  size="4" maxlength="4"></label>
    </div>
</div>
<div class="break"></div>
</td>
</tr>
</table>
</div>

{* DESIGN: Content END *}</div></div></div></div></div></div>

<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">
<div class="button-left">
<input type="hidden" name="HasObjectInput" value="0" />
<input type="hidden" name="PublishButton" value="Continute" />
<input class="button" type="submit" name="TMPublish" value="Publish as is" />
<input class="button{if $language_code_include|count|eq(0)}-disabled{/if}" type="submit" name="TMTranslate" value="Translate" />
</div>

{* Translation a user is able to create *}
{set-block variable=$nonexisting_languages_output}
{foreach $all_languages as $language}
    {if is_unset($languages[$language.locale_code])}
        {if fetch('content', 'access', hash( 'access', 'edit',
                                             'contentobject', $object,
                                             'language', $language.locale_code ) )}
               <option value="{$language.locale_code}"{run-once} checked="checked"{/run-once}>{$language.intl_language_name|wash}</option>
        {/if}
    {/if}
{/foreach}
{/set-block}

{if $nonexisting_languages_output|trim}
<div class="button-right">
<label>
<select name="AddLanguage">
    {$nonexisting_languages_output}
    </select>
</label>
    <input class="button" type="submit" name="TMAddLanguage" title="{'Select the language you want to add to the object'|i18n('design/standard/content/edit_languages')}" value="{'Add new language'|i18n('design/standard/content/edit_languages')}" />
    <div class="labelbreak"></div>
    </div>
{/if}
    
    
    
    
<div class="break"></div>
</div>
{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</div>
</form>
<!-- Maincontent END -->
</div>
<div class="break"></div>
</div></div>
