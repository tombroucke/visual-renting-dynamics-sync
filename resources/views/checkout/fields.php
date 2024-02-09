<div class="vrd-fields__field-wrapper">
    <?php foreach ($fields as $category) : ?>
        <h4><?php echo $category['label']; ?></h4>
        <?php foreach ($category['fields'] as $fieldName => $fieldSettings) {
            woocommerce_form_field(
                $fieldName,
                $fieldSettings
            );
        } ?>
    <?php endforeach; ?>
</div>
