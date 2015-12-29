<?= $this->partial('partial.php'); ?>
<div style="border: 1px solid red">
    <h2>Inner layout</h2>
    <?= $this->body(); ?>
</div>
<?= $this->extend('layout.php'); ?>