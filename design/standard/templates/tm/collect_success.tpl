<form name="collect" method="post" action={'collaboration/view/summary'|ezurl}>
	<div class="context-block">
		{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
		<h1 class="context-title">{'Translation collection'|i18n('extension/tm')}</h1>
		{* DESIGN: Mainline *}<div class="header-mainline"></div>
		{* DESIGN: Header END *}</div></div></div></div></div></div>
		{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">
		<div class="context-attributes">
			<p>A Translation collection has been created</p>
		</div>
		{* DESIGN: Content END *}</div></div></div>
		<div class="controlbar">
			{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
			<div class="block">
				<div class="button-left">
				    <input class="button" name="Continue" type="submit" value="{'Continue'|i18n('extension/tm')}" />
				</div>
				<div class="break"></div>
			</div>
			{* DESIGN: Control bar END *}</div></div></div></div></div></div>
		</div>
	</div>
</form>