{* Блок отладки для разработчиков *}
{if isset($exception)}
    <div class="error-debug" style="text-align: left; max-width: 800px; margin: 30px auto; background: #1e1e1e; padding: 20px; border-radius: 5px; font-family: monospace;">
        <details open>
            <summary style="cursor: pointer; font-weight: bold; color: #ffc107; outline: none; margin-bottom: 10px;">
                Информация для разработчика (Debug Info)
            </summary>

            <div style="border-top: 1px solid #444; padding-top: 15px; line-height: 1.5;">
                {* ИСПРАВЛЕНИЕ: Выводим готовую строку класса ошибки *}
                <p style="color: #dc3545; margin: 0 0 5px 0;"><strong>Exception:</strong> {$exception_class}</p>
                <p style="color: #dc3545; margin: 0 0 5px 0;"><strong>Message:</strong> {$exception->getMessage()}</p>
                <p style="color: #aaa; margin: 0 0 15px 0;"><strong>File:</strong> {$exception->getFile()}:{$exception->getLine()}</p>

                {* ИСПРАВЛЕНИЕ: Проверяем наличие переданного класса предыдущей ошибки *}
                {if isset($previous_class) && $previous_class}
                    <div style="background: #2d2d2d; padding: 10px; margin-bottom: 15px; border-left: 3px solid #dc3545;">
                        <p style="color: #ff8080; margin: 0;"><strong>Caused by:</strong> {$previous_class}</p>
                        <p style="color: #ff8080; margin: 5px 0 0 0;"><strong>Message:</strong> {$exception->getPrevious()->getMessage()}</p>
                    </div>
                {/if}

                <p style="color: #777; margin: 0 0 5px 0; font-size: 0.85rem; text-transform: uppercase; font-weight: bold;">Stack Trace:</p>
                <pre style="margin: 0; white-space: pre-wrap; font-size: 0.8rem; color: #ddd; background: #111; padding: 10px; overflow-x: auto;">{$exception->getTraceAsString()}</pre>
            </div>
        </details>
    </div>
{/if}
