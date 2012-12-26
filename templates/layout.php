<?php /** @var $page \CMSx\Page */ ?>
<?= $page->renderDoctype() ?>
<?= $page->renderHTMLTag() ?>
<head>
<?= $page->renderCharset() ?>
<?= $page->renderTitle() ?>
<?= $page->renderKeywords() ?>
<?= $page->renderDescription() ?>
<?= $page->renderCSS() ?>
<?= $page->renderCanonical() ?>
<?= $page->getMeta() ?>
</head>

<?= $page->renderBody() ?>
</html>