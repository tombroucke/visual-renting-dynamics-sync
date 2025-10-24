<h2><?php _e('Documents', 'visual-renting-dynamics-sync'); ?></h2>
<ul class="documents">
<?php foreach ($documentIds as $documentId) { ?>
    <li>
        <a href="<?php echo wp_get_attachment_url($documentId); ?>">
            <?php echo get_the_title($documentId); ?>
        </a>
    </li>
<?php } ?>
</ul>
