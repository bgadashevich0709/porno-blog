<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{block name="title"}{$meta->title|default:"Блог"}{/block}</title>
    <meta name="description" content="{$meta->description|default:""}">
    <meta name="keywords" content="{$meta->keywords|default:""}">
    <link rel="stylesheet" href="/assets/build/style.css">
</head>
<body>

{include file="partials/header.tpl"}

<main>
    {block name="content"}{/block}
</main>

{include file="partials/footer.tpl"}

</body>
</html>
