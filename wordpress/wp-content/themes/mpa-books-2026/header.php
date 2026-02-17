<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="mpa-site-header">
  <div class="mpa-wrap mpa-site-header__inner">
    <a class="mpa-site-brand" href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>

    <?php if (has_nav_menu('primary')) : ?>
      <nav class="mpa-primary-nav" aria-label="<?php esc_attr_e('Primary menu', 'mpa-books-2026'); ?>">
        <?php
        wp_nav_menu([
            'theme_location' => 'primary',
            'container' => false,
            'menu_class' => 'mpa-primary-nav__list',
            'fallback_cb' => false,
        ]);
        ?>
      </nav>
    <?php endif; ?>
  </div>
</header>
