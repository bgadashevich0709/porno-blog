{* templates/parts/breadcrumbs.tpl *}
{if isset($breadcrumbs) && !empty($breadcrumbs)}
    <nav class="breadcrumbs-container" aria-label="breadcrumb">
        <ul class="breadcrumbs-list" style="display: flex; flex-wrap: wrap; list-style: none; padding: 0; margin: 15px 0; gap: 8px; align-items: center; font-size: 14px;">
            {foreach $breadcrumbs as $crumb}
                <li class="breadcrumbs-item" style="display: inline-flex; align-items: center;">
                    {if $crumb->url}
                        <a href="{$crumb->url}" class="breadcrumbs-link" style="color: #0066cc; text-decoration: none;">{$crumb->label|escape}</a>
                        <span class="breadcrumbs-separator" style="margin-left: 8px; color: #999;">/</span>
                    {else}
                        <span class="breadcrumbs-current" style="color: #333; font-weight: 500;">{$crumb->label|escape}</span>
                    {/if}
                </li>
            {/foreach}
        </ul>
    </nav>
{/if}
