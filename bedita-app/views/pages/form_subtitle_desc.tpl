<h2 class="showHideBlockButton">{t}Subtitle, description{/t}</h2>
<div class="blockForm" id="subtitle" style="display: none">
<fieldset>
	<table class="tableForm" border="0">
{* da modificare, e' in conflitto con la definizione della lingua del documento
	<tr>
		<td class="label">{t}Language{/t}:</td>
		<td class="field">
			<select name="data[lang]">
			{html_options options=$conf->langOptions selected=$object.lang|default:$conf->defaultLang}
			</select>
		</td>
		<td class="status">&nbsp;</td>
	</tr>
*}
	<tr id="SubTitle_TR_{$object.lang|default:$conf->defaultLang}">
		<td class="label">{t}Subtitle{/t}:</td>
		<td class="field"><textarea class="subtitle" name="data[subtitle]">{$object.subtitle|default:''|escape:'html'}</textarea></td>
		<td class="status">
		{* commentato temporaneamente: bug da fissare
		{if ($object)}<input class="cmdField" id="cmdTranslateSubTitle" type="button" value="lang ..."/>{/if}
		*}
		</td>
	</tr>
	{if (isset($object.LangText.subtitle))}
	{foreach name=i from=$object.LangText.subtitle key=lang item=text}
	<tr>
		<td class="label"></td>
		<td class="field">
			<input type='hidden' value='subtitle' name="data[LangText][{$smarty.foreach.i.iteration}][name]"/>
			<textarea class="subtitle" name="data[LangText][{$smarty.foreach.i.iteration}][txt]">{$text|escape:'html'}</textarea>
		</td>
		<td class="status">
			<select name="data[LangText][{$smarty.foreach.i.iteration}][lang]">
			{html_options options=$conf->langOptions selected=$lang}
			</select>
			&nbsp;&nbsp;
			<input type="button" name="delete" value=" x " onclick="$('../..', this).remove() ;"/>
		</td>
	</tr>
	{/foreach}
	{/if}
	<tr id="ShortDesc_TR_{$object.lang|default:$conf->defaultLang}">
		<td class="label">{t}Description{/t}:</td>
		<td class="field"><textarea class="shortdesc" name="data[short_desc]">{$object.shortDesc|default:''|escape:'html'}</textarea></td>
		<td class="status">
		{* commentato temporaneamente: bug da fissare
		{if ($object)}<input class="cmdField" id="cmdTranslateShortDesc" type="button" value="lang ..."/>{/if}
		*}
		</td>
	</tr>
	{if (isset($object.LangText.short_desc))}
	{foreach name=i from=$object.LangText.short_desc key=lang item=text}
	<tr>
		<td class="label"></td>
		<td class="field">
			<input type='hidden' value='shortdesc' name="data[LangText][{$smarty.foreach.i.iteration}][name]"/>
			<textarea class="shortdesc" name="data[LangText][{$smarty.foreach.i.iteration}][txt]">{$text|escape:'html'}</textarea>
		</td>
		<td class="status">
			<select name="data[LangText][{$smarty.foreach.i.iteration}][lang]">
			{html_options options=$conf->langOptions selected=$lang}
			</select>
			&nbsp;&nbsp;
			<input type="button" name="delete" value=" x " onclick="$('../..', this).remove() ;"/>
		</td>
	</tr>
	{/foreach}
	{/if}
	</table>
</fieldset>
</div>