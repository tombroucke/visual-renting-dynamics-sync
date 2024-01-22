<div class="mb-5">
    <h3><?php _e('Details', 'visual-renting-dynamics-sync'); ?></h3>
    <ul>
        <?php foreach ($fields as $field) : ?>
            <li>
                <strong><?php echo $field['name']; ?>: </strong>
                <span><?php echo $field['value']; ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
