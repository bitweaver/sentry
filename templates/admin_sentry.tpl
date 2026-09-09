{strip}
{form}
	<input type="hidden" name="page" value="{$page}" />

	{legend legend="Sentry / GlitchTip reporting"}
		{foreach from=$formSentry key=feature item=output}
			<div class="form-group">
				{formlabel label=$output.label for=$feature}
				{forminput}
					<input type="text" class="form-control" name="{$feature}" id="{$feature}" value="{$gBitSystem->getConfig($feature)|escape}" />
					{formhelp note=$output.note}
				{/forminput}
			</div>
		{/foreach}

		<div class="form-group">
			{forminput}
				<label>
					<input type="checkbox" name="sentry_send_test" value="y" />
					{tr}Send a test notice on save{/tr}
				</label>
				{formhelp note="Uses the saved DSN after preferences are stored. Check your Sentry/GlitchTip project for a new event."}
			{/forminput}
		</div>

		{if $sentryTestSent}
			{formfeedback success="Test event submitted (if the DSN and network path are valid)."}
		{/if}

		<div class="form-group submit">
			<input type="submit" class="btn btn-default" name="sentry_prefs" value="{tr}Save{/tr}" />
		</div>
	{/legend}
{/form}
{/strip}
