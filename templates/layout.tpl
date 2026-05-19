<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{block name="title"}{$title|default:"Блог"}{/block}</title>
    <!-- ПУТЬ ИСПРАВЛЕН: Теперь ссылаемся на папку build, которая скрыта в .gitignore -->
    <link rel="stylesheet" href="/assets/build/style.css">
</head>
<body>

{* Подключаем шапку *}
{include file="partials/header.tpl"}

<main>
    {* Сюда дочерние шаблоны будут встраивать свой основной контент *}
    {block name="content"}{/block}
</main>

{* Подключаем подвал *}
{include file="partials/footer.tpl"}

</body>
</html>
