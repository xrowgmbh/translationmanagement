{if $version|not}
    <div class="message-error">
        <h2>{'Incorrect Translation Manager Version. Notifiy Support.'|i18n('extension/tm')}</h2>
    </div>
{/if}
{if $clean}
    <div class="message-feedback">
        <h2>{'Everything has been cleaned up.'|i18n('extension/tm')}</h2>
    </div>
{/if}

<form name="collect" method="post" action={'tm/admin'|ezurl}>
	<div class="context-block">
		{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
		<h1 class="context-title">{'Translation management administration'|i18n('extension/tm')}</h1>
		{* DESIGN: Mainline *}<div class="header-mainline"></div>
		{* DESIGN: Header END *}</div></div></div></div></div></div>
		{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">
		<div class="context-attributes">
			<p>{'Only use this button if you exactly know what you are doing.'|i18n('extension/tm')}</p>
		</div>
		{* DESIGN: Content END *}</div></div></div>
		<div class="controlbar">
			{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
			<div class="block">
				<div class="button-left">
			    	<input class="button" type="submit" name="Clean" value="{'Clean all'|i18n('extension/tm')}" />
				</div>
				<div class="button-right"></div>
				<div class="break"></div>
			</div>
			{* DESIGN: Control bar END *}</div></div></div></div></div></div>
		</div>
	</div>
</form>