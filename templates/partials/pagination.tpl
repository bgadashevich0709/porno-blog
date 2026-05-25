{if isset($pages) && isset($postsData)}
    <div class="pagination-wrapper" style="margin-top: 40px; display: flex; flex-direction: column; align-items: center; gap: 20px;">

        {if !empty($pages)}
            <nav class="pagination-container" style="display: flex; gap: 5px;">
                {foreach $pages as $page}
                    {if $page->isSeparator}
                        <span style="padding: 8px 14px; color: #777;">{$page->label}</span>
                    {elseif $page->isCurrent}
                        <span style="padding: 8px 14px; background: #333; color: #fff; border: 1px solid #333; border-radius: 4px; font-weight: bold;">
                            {$page->label}
                        </span>
                    {else}
                        <a href="{$page->url}" style="padding: 8px 14px; background: #fff; color: #007bff; border: 1px solid #e1e1e1; border-radius: 4px; text-decoration: none; font-weight: bold;">
                            {$page->label}
                        </a>
                    {/if}
                {/foreach}
            </nav>
        {/if}

        {if isset($limitControl) && !empty($limitControl->options)}
            <div class="per-page-container" style="background: #fff; border: 1px solid #e1e1e1; border-radius: 6px; padding: 10px 15px; display: flex; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <form method="GET" action="" id="perPageForm" style="display: flex; gap: 10px; align-items: center; margin: 0; padding: 0;">

                    <!-- При изменении лимита сбрасываем пользователя на 1 страницу -->
                    <input type="hidden" name="page" value="1">

                    <!-- Сохраняем параметры сортировки из урла, чтобы они не сбросились -->
                    {foreach $smarty.get as $key => $value}
                        {if $key != 'page' && $key != 'perPage'}
                            <input type="hidden" name="{$key|escape}" value="{$value|escape}">
                        {/if}
                    {/foreach}

                    <label for="perPageSelect" style="font-size: 0.9rem; color: #555;">Показывать публикаций на странице:</label>
                    <select name="perPage" id="perPageSelect" onchange="document.getElementById('perPageForm').submit();" style="padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; background: #fff; font-size: 0.9rem; cursor: pointer; font-weight: bold; color: #333;">
                        {foreach $limitControl->options as $limit}
                            <option value="{$limit}" {if $limitControl->current == $limit}selected{/if}>{$limit}</option>
                        {/foreach}
                    </select>
                </form>
            </div>
        {/if}
    </div>
{/if}
