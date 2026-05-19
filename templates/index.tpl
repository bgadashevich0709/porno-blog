{extends file="layout.tpl"}

{block name="title"}{$title}{/block}

{block name="content"}
    {if !empty($data->categories)}
        {foreach $data->categories as $categoryGroup}
            <section class="category-block">
                <div class="category-header">
                    <div>
                        <h2 class="category-title">{$categoryGroup->title}</h2>
                        {if !empty($categoryGroup->description)}
                            <p class="category-desc">{$categoryGroup->description}</p>
                        {/if}
                    </div>
                    <a href="{$categoryGroup->link}" class="view-all-link">Все материалы &rarr;</a>
                </div>

                <div class="posts-grid">
                    {foreach $categoryGroup->latestPosts as $post}
                        {* Рендерим карточку, прокидывая объект текущей итерации по имени post *}
                        {include file="partials/post_article.tpl" post=$post}
                    {/foreach}
                </div>

            </section>
        {/foreach}
    {else}
        <div class="empty-state">
            <p>На главной странице пока нет доступных публикаций.</p>
        </div>
    {/if}
{/block}
