{if isset($data->sortPanel) && !empty($data->sortPanel->sortOptions)}
    <div class="sorting-panel" style="background: #fff; border: 1px solid #e1e1e1; border-radius: 8px; padding: 15px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <form method="GET" action="" id="sortForm" style="display: flex; gap: 15px; width: 100%; align-items: center; margin: 0; padding: 0;">

            <input type="hidden" name="page" value="1">

            {* Динамически сохраняем остальные GET-параметры *}
            {foreach $smarty.get as $key => $value}
                {if $key != 'page' && $key != $data->sortPanel->sortKeyName && $key != 'sortWay'}
                    <input type="hidden" name="{$key|escape}" value="{$value|escape}">
                {/if}
            {/foreach}

            <!-- Список полей сортировки -->
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label for="universalSort" style="font-size: 0.85rem; color: #666; font-weight: bold;">Сортировать по:</label>
                <select name="{$data->sortPanel->sortKeyName}" id="universalSort" onchange="document.getElementById('sortForm').submit();" style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; background: #fff; font-size: 0.95rem; cursor: pointer; min-width: 180px;">
                    {foreach $data->sortPanel->sortOptions as $value => $label}
                        <option value="{$value}" {if $data->sortPanel->currentSort == $value}selected{/if}>{$label}</option>
                    {/foreach}
                </select>
            </div>

            <!-- Список направлений сортировки -->
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label for="universalWay" style="font-size: 0.85rem; color: #666; font-weight: bold;">Направление:</label>
                <select name="sortWay" id="universalWay" onchange="document.getElementById('sortForm').submit();" style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; background: #fff; font-size: 0.95rem; cursor: pointer;">
                    {foreach $data->sortPanel->wayOptions as $value => $label}
                        <option value="{$value}" {if $data->sortPanel->currentWay == $value}selected{/if}>{$label}</option>
                    {/foreach}
                </select>
            </div>
        </form>
    </div>
{/if}
