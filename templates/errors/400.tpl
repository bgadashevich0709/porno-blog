{extends file="layout.tpl"}

{block name="title"}400 — Ошибка валидации{/block}

{block name="content"}
    <div class="error-container">
        <p class="error-code" style="color: #fd7e14;">400</p>
        <h2 style="margin-top: 10px; color: #222;">Ошибка валидации данных</h2>

        <p style="color: #666; max-width: 500px; margin: 15px auto 20px auto;">
            {$message|default:"Пожалуйста, проверьте правильность заполнения всех полей формы."}
        </p>

        {* ВЫВОД ОШИБОК ВАЛИДАЦИИ ИЗ МЕТОДА getErrors() *}
        {if isset($errors) && !empty($errors)}
            <div style="text-align: left; max-width: 500px; margin: 0 auto 30px auto; background: #fff5f5; border: 1px solid #feb2b2; padding: 15px; border-radius: 6px;">
                <ul style="margin: 0; padding-left: 20px; color: #c53030; font-size: 0.95rem;">
                    {foreach from=$errors key=field item=fieldErrors}
                        {foreach from=$fieldErrors item=error}
                            <li><strong>{$field}:</strong> {$error}</li>
                        {/foreach}
                    {/foreach}
                </ul>
            </div>
        {/if}

        <div style="display: flex; gap: 10px; justify-content: center;">
            <button onclick="history.back()" class="view-all-link" style="cursor: pointer; border: none;">Вернуться назад</button>
        </div>
    </div>
{/block}
