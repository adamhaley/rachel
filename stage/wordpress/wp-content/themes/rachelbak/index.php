      <?php get_header(); ?>


<div id="container">
 
   <div id="content">
      
      <?php /* Top post navigation */ ?>
      <?php global $wp_query; $total_pages = $wp_query->max_num_pages; if ( $total_pages > 1 ) { ?>
          <div id="nav-above" class="navigation">
           <div class="nav-previous"><?php next_posts_link(__( '<span class="meta-nav">&laquo;</span> Older posts', 'your-theme' )) ?></div>
           <div class="nav-next"><?php previous_posts_link(__( 'Newer posts <span class="meta-nav">&raquo;</span>', 'your-theme' )) ?></div>
          </div><!– #nav-above –>
      <?php } ?>


	<?php while ( have_posts() ) : the_post() ?>
	 <h2 class="entry-title"><a href="<?php the_permalink(); ?>" title="<?php printf( __('Permalink to %s', 'your-theme'), the_title_attribute('echo=0') ); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
           <div class="entry-content">
 
     <?php the_content( __( 'Continue reading <span class="meta-nav">&raquo;</span>', 'your-theme' )  ); ?>
      <?php wp_link_pages('before=<div class="page-link">' . __( 'Pages:', 'your-theme' ) . '&after=</div>') ?>
           </div><!– .entry-content –>




	<?php endwhile; ?>

      <?php /* Bottom post navigation */ ?>
      <?php global $wp_query; $total_pages = $wp_query->max_num_pages; if ( $total_pages > 1 ) { ?>
          <div id="nav-below" class="navigation">
           <div class="nav-previous"><?php next_posts_link(__( '<span class="meta-nav">&laquo;</span> Older posts', 'your-theme' )) ?></div>
           <div class="nav-next"><?php previous_posts_link(__( 'Newer posts <span class="meta-nav">&raquo;</span>', 'your-theme' )) ?></div>
          </div><!– #nav-below –>
      <?php } ?>





	</div><!– #content –>
   
  </div><!– #container –>
 
  <div id="primary" class="widget-area">
  </div><!– #primary .widget-area –>
 
  <div id="secondary" class="widget-area">
  </div><!– #secondary –>
      <?php get_sidebar(); ?>

 <?php get_footer(); ?>


