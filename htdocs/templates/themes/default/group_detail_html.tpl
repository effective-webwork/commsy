{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a class="edit" href=""><span class="edit_set"> &nbsp; </span></a>
			<a class="detail" href=""><span class="details_ia"> &nbsp; </span></a>
		</div>
	</div>

	<div class="item_body"> <!-- Start item body -->
		<!-- Start fade_in_ground -->
		<div class="fade_in_ground_actions hidden">
			{* TODO: add missing actions *}
			{if $detail.actions.edit}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_EDIT_ITEM___</a> |
			{/if}
			{if $detail.actions.delete}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_DELETE_ITEM___</a> |
			{/if}
			{if $detail.actions.mail}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_EMAIL_TO___</a> |
			{/if}
		</div>
		<!-- Ende fade_in_ground -->

		<h2>
			{$detail.content.title}
		</h2>
		<div class="clear"> </div>

		<div id="item_credits">
			<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___ <a href="">{$detail.content.modificator}</a> ___DATES_ON_DAY___  {$detail.content.modification_date}
			</p>
		</div>

		<div id="item_legend"> <!-- Start item_legend -->
			<div class="row_odd">
				{if $detail.content.show_picture}
					<div id="group_profil_picture">
						<img alt="Portrait" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$detail.content.picture}" />
					</div>
				{/if}
				{if !empty($detail.content.description)}
					<div class="detail_description">
					{$detail.content.description}
					</div>
				{/if}
				<div class="clear"> </div>


				<div class="detail_description">
					<h4>___GROUP_MEMBERS___</h4>
					{if !empty($detail.content.members)}
						<table>
							<tr>
								<td>
									<ul>
										{section name=members_col1 loop=$detail.content.members start=0 step=3}
											{$member=$detail.content.members[members_col1]}
											<li>
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.iid}" title="{$member.linktext}">{$member.linktext}</a>
											</li>
										{/section}
									</ul>
								</td>
								<td>
									<ul>
										{section name=members_col2 loop=$detail.content.members start=1 step=3}
											{$member=$detail.content.members[members_col2]}

											<li>
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.iid}" title="{$member.linktext}">{$member.linktext}</a>
											</li>
										{/section}
									</ul>
								</td>
								<td>
									<ul>
										{section name=members_col3 loop=$detail.content.members start=2 step=3}
											{$member=$detail.content.members[members_col3]}

											<li>
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.iid}" title="{$member.linktext}">{$member.linktext}</a>
											</li>
										{/section}
									</ul>
								</td>
							</tr>
						</table>
					{else}
						___COMMON_NONE___
					{/if}
				</div>
			</div>
		</div> <!-- Ende item_legend -->
		{include file="include/detail_moredetails_html.tpl" data=$detail.content.moredetails}

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	<div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}