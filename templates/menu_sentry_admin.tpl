{strip}
{if $packageMenuTitle}<a href="#"> {tr}{$packageMenuTitle|capitalize}{/tr}</a>{/if}
<ul class="{$packageMenuClass}">
	<li><a class="item" href="{$smarty.const.KERNEL_PKG_URL}admin/index.php?page=sentry">{tr}Sentry Settings{/tr}</a></li>
</ul>
{/strip}
