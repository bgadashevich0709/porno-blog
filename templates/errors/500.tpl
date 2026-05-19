{extends file="layout.tpl"}

{block name="title"}500 — Внутренняя ошибка{/block}

{block name="content"}
    <div class="error-container">
        <p class="error-code" style="color: #6c757d;">500</p>
        <h2 style="margin-top: 10px; color: #222;">Что-то пошло не так</h2>

        <p style="color: #666; max-width: 500px; margin: 15px auto 30px auto;">
            {$message|default:"На сервере произошел непредвиденный сбой. Мы уже знаем об этом и занимаемся исправлением ситуации."}
        </p>

        <a href="/" class="view-all-link" style="background: #222;">Вернуться на главную</a>

        {* Блок отладки для разработчиков *}
        {if isset($exception)}
            <div class="error-debug">
                <details>
                    <summary style="cursor: pointer; font-weight: bold; color: #ffc107; outline: none;">
                        Информация для разработчика (Debug Info)
                    </summary>
                    <div style="margin-top: 15px; border-top: 1px solid #444; padding-top: 15px; line-height: 1.5;">
                        <p style="color: #dc3545; margin: 0 0 5px 0;"><strong>Message:</strong> {$exception->getMessage()}</p>
                        <p style="color: #aaa; margin: 0 0 15px 0;"><strong>File:</strong> {$exception->getFile()}:{$exception->getLine()}</p>
                        <p style="color: #777; margin: 0 0 5px 0; font-size: 0.75rem; text-transform: uppercase;">Stack Trace:</p>
                        <pre style="margin: 0; white-space: pre-wrap; font-size: 0.8rem; color: #ddd;">{$exception->getTraceAsString()}</pre>
                    </div>
                </details>
            </div>
        {/if}
    </div>
{/block}
