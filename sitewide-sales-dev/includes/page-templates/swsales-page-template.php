<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">

	<?php if ( ! get_theme_support( 'title-tag' ) ) : ?>
		<title><?php wp_title(); ?></title>
	<?php endif; ?>

	<?php wp_head(); ?>
</head>

<body <?php body_class( 'swsales-template' ); ?>>

<?php while ( have_posts() ) : ?>

	<?php the_post(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<?php the_content(); ?>

	</article>

<?php endwhile; ?>

<?php wp_footer(); ?>

</body>
</html>
