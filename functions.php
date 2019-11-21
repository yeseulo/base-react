<?php
/* Remove all default WP template redirects/lookups */
remove_action( 'template_redirect', 'redirect_canonical' );

/* Redirect all requests to index.php so the Vue app is loaded and 404s aren't thrown */
function oys_remove_redirects() {
	add_rewrite_rule( '^/(.+)/?', 'index.php', 'top' );
}
add_action( 'init', 'oys_remove_redirects' );

/* 스크립트 */
function oys_load_scripts() {
	wp_enqueue_script(
		'app',
		get_stylesheet_directory_uri() . '/dist/app.js',
		array(),
		filemtime( get_stylesheet_directory() . '/dist/app.js' ),
		true
	);

	wp_enqueue_style(
		'app',
		get_stylesheet_directory_uri() . '/dist/app.css',
		null,
		filemtime( get_stylesheet_directory() . '/dist/app.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'oys_load_scripts', 100 );

/* 테마 설정 */
function oys_setup_theme() {
  // 썸네일 이미지 사용
  add_theme_support( 'post-thumbnails' );
  add_editor_style( 'dist/app.css' );
  show_admin_bar( false );
}
add_action( 'after_setup_theme', 'oys_setup_theme' );

/* 관리자 글목록 썸네일 표시 */
if (function_exists( 'add_theme_support' )){
  add_image_size( 'admin-thumb', 200, 200 ); // 100 pixels wide (and unlimited height)
  add_filter('manage_posts_columns', 'posts_columns', 5);
  add_action('manage_posts_custom_column', 'posts_custom_columns', 5, 2);
  add_filter('manage_pages_columns', 'posts_columns', 5);
  add_action('manage_pages_custom_column', 'posts_custom_columns', 5, 2);
}
function posts_columns($defaults){
  $defaults['wps_post_thumbs'] = __('대표 이미지');
  return $defaults;
}
function posts_custom_columns($column_name, $id){
  if($column_name === 'wps_post_thumbs'){
    echo the_post_thumbnail( 'admin-thumb', array( 'class' => 'img-responsive' ) );
  }
}

/* 썸네일 이미지 api로 쉽게 접근할 수 있도록 설정 */
add_action('rest_api_init', 'register_rest_images' );
function register_rest_images(){
    register_rest_field( array('post', 'page'),
        'featured_image_url',
        array(
            'get_callback'    => 'get_rest_featured_image',
            'update_callback' => null,
            'schema'          => null,
        )
    );
}
function get_rest_featured_image( $object, $field_name, $request ) {
    if( $object['featured_media'] ){
        $img = wp_get_attachment_image_src( $object['featured_media'], 'thumbnail' );
        return $img[0];
    }
    return false;
}

/* full 사이즈 이미지 */
add_action('rest_api_init', 'register_rest_images_full' );
function register_rest_images_full(){
    register_rest_field( array('page', 'post'),
        'featured_image_url',
        array(
            'get_callback'    => 'get_rest_featured_image_full',
            'update_callback' => null,
            'schema'          => null,
        )
    );
}
function get_rest_featured_image_full( $object, $field_name, $request ) {
    if( $object['featured_media'] ){
        $img = wp_get_attachment_image_src( $object['featured_media'], 'full' );
        return $img[0];
    }
    return false;
}


/* <title> */
function oys_wp_title( $title, $sep ) {
  if ( is_feed() )
    return $title;

  $title            = oys_page_title();
  $site_name        = get_bloginfo( 'name', 'display' );
  $site_description = get_bloginfo( 'description', 'display' );

  if ( is_home() || is_front_page() ) :
    if ( ! $site_description ) : return;
    else : $title = "$title";
    endif;

  else :
    $title = "$title $sep $site_name";

  endif;
  return $title;
}
add_filter( 'wp_title', 'oys_wp_title', 10, 2 );

/* <head>: IE8 대응, 메타, 파비콘 */
function oys_add_opengraph_namespace( $input ) {
  return $input.' prefix="og: http://ogp.me/ns#"';
}
add_filter( 'language_attributes', 'oys_add_opengraph_namespace' );

function oys_head() {
    if ( is_404() ) : ?>
    <meta name="robots" content="noindex,follow">
    <meta property="og:title" content="<?php oys_meta( 'title' ); ?>">
    <meta property="og:type" content="object">

  <?php elseif ( is_search() ) : ?>
    <meta name="robots" content="noindex,follow">
    <link rel="canonical" href="<?php oys_meta( 'url' ); ?>">
    <meta property="og:title" content="<?php oys_meta( 'title' ); ?>">
    <meta property="og:url" content="<?php oys_meta( 'url' ); ?>">
    <meta property="og:type" content="object">

  <?php elseif ( is_archive() ) : ?>
    <meta name="robots" content="noindex,follow">
    <meta name="description" content="<?php oys_meta( 'description' ); ?>">
    <link rel="canonical" href="<?php oys_meta( 'url' ); ?>">
    <meta property="og:title" content="<?php oys_meta( 'title' ); ?>">
    <meta property="og:url" content="<?php oys_meta( 'url' ); ?>">
    <meta property="og:type" content="object">
    <meta property="og:description" content="<?php oys_meta( 'description' ); ?>">

  <?php elseif ( is_singular() ) : ?>
    <meta property="og:title" content="<?php oys_meta( 'title' ); ?>">
    <meta property="og:url" content="<?php oys_meta( 'url' ); ?>">
    <meta property="og:type" content="article">
    <meta name="description" content="<?php oys_meta( 'description' ); ?>">
    <meta property="og:description" content="<?php oys_meta( 'description' ); ?>">
    <?php if ( is_single() ) : ?>
      <meta property="article:section" content="<?php oys_meta( 'section' ); ?>">
      <?php foreach ( oys_meta( 'tags' ) as $tag ) : ?>
        <meta property="article:tag" content="<?php echo $tag->name; ?>">
      <?php endforeach; ?>
    <?php endif; ?>
    <meta property="article:published_time" content="<?php oys_meta( 'time' ); ?>">
    <meta property="article:author" content="<?php oys_meta( 'author' ); ?>">

  <?php else : ?>
    <meta property="og:title" content="<?php oys_meta( 'title' ); ?>">
    <meta property="og:url" content="<?php oys_meta( 'url' ); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <link rel="canonical" href="<?php oys_meta( 'url' ); ?>">
    <meta name="description" content="<?php bloginfo( 'description' ); ?>">
    <meta property="og:description" content="<?php bloginfo( 'description' ); ?>">
    <meta property="og:type" content="website">

  <?php endif; ?>
  <meta property="fb:app_id" content="">
  <meta property="og:site_name" content="<?php bloginfo( 'name' ); ?>">
  <meta property="og:image" content="<?php oys_meta( 'image' ); ?>">
  <?php if ( is_singular() ) : ?>
    <?php foreach ( oys_meta( 'attachment_images' ) as $attachment_image ) : ?>
      <meta property="og:image" content="<?php echo $attachment_image; ?>">
    <?php endforeach; ?>
  <?php endif; ?>
  <meta property="og:locale" content="<?php bloginfo( 'language' ); ?>"><?php
}
add_action('wp_head', 'oys_head');

/* <head>: 청소 */
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rest_output_link_wp_head'); // API json

/* 타이틀 */
function oys_title() {
  wp_title( '|', true, 'right');
}

/* 페이지 제목 */
function oys_page_title() {
  if ( is_404()      ) : echo 'Not Found';
  // elseif ( is_search()   ) : printf( '검색 결과: %s', get_search_query() );
  elseif ( is_search()   ) : echo '검색 결과';
  elseif ( is_archive()  ) : oys_the_archive_title();
  elseif ( is_singular() ) : the_title();
  else : bloginfo( 'name' );
  endif;
}

/* 메타: 루프 바깥에서 */
function oys_meta($meta = null) {
  if ( ! $meta ) return;

  if ( $meta == 'title' ) :
    oys_title();

  elseif ( $meta == 'url' ) :
    oys_url();

  elseif ( $meta == 'description' ) :
    if ( is_archive() ) :
      oys_the_archive_description();
    elseif ( is_single() ) :
      $excerpt = strip_tags(get_the_excerpt());
      if ( ! $excerpt ) :
        $queried_object = get_queried_object();
        $excerpt = oys_trim_excerpt( $queried_object->post_content );
      endif;
      echo $excerpt;
    else:
      return;
    endif;

  elseif ( $meta == 'section' ) :
    $category = get_the_category();
    echo $category[0]->cat_name;

  elseif ( $meta == 'tags' ) :
    $tags = get_the_tags();
    if ( ! $tags ) $tags = array();
    return $tags;

  elseif ( $meta == 'time' ) :
    echo esc_attr( get_the_date( 'c' ) );

  elseif ( $meta == 'author' ) :
    $queried_object = get_queried_object();
    $author_id = $queried_object->post_author;
    echo get_the_author_meta( 'display_name', $author_id );

  elseif ( $meta == 'image' ) :
    $fb_image = get_template_directory_uri().'/dist/images/fb-image.png';
    if ( is_singular() ) :
      $thumbnail_src = oys_get_post_thumbnail_src();
      $image         = ( $thumbnail_src ) ? $thumbnail_src : $fb_image;
    else :
      $image = $fb_image;
    endif;
    echo $image;

  elseif ( $meta == 'attachment_images' ) :
    $queried_object = get_queried_object();
    $args = array(
      'post_type'      => 'attachment',
      'posts_per_page' => -1,
      'post_parent'    => $queried_object->ID
    );
    $attachments = get_posts( $args );
    $attachment_images = array();
    if ( $attachments ) :
      foreach ( $attachments as $attachment ) :
        $attachment_images[] = oys_get_attachment_image_src( $attachment->ID, 'full' );
      endforeach;
    endif;
    return $attachment_images;

  else :
    return;

  endif;
}

/* 페이지 주소 */
function oys_url() {
      if ( is_search()   ) : $canonical = get_search_link();
  elseif ( is_archive()  ) : $canonical = oys_get_archive_url();
  elseif ( is_singular() ) : $canonical = get_permalink();
  else : $canonical = home_url( '/' );
  endif;

  echo esc_url( $canonical );
}

/* 보관함 주소 */
function oys_get_archive_url() {
      if ( is_category() ) : $canonical = get_term_link( get_query_var( 'cat' ), 'category' );
  elseif ( is_tag()      ) : $canonical = get_term_link( get_query_var( 'tag' ), 'post_tag' );
  elseif ( is_author()   ) : $canonical = get_author_posts_url( get_query_var( 'author' ), get_query_var( 'author_name' ) );
  elseif ( is_year()     ) : $canonical = get_year_link(  get_query_var( 'year' ) );
  elseif ( is_month()    ) : $canonical = get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) );
  elseif ( is_day()      ) : $canonical = get_day_link(   get_query_var( 'year' ), get_query_var( 'monthnum' ), get_query_var( 'day' ) );
  elseif ( is_tax( 'post_format' ) ) :
    $canonical = get_term_link( get_query_var( 'post_format' ), 'post_format' );
  elseif ( is_post_type_archive() ) :
    $canonical = get_post_type_archive_link( get_query_var( 'post_type' ) );
  elseif ( is_tax() ) :
    $canonical = get_term_link( get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
  else :
    $canonical = '';
  endif;

  return $canonical;
}

/* 보관함 제목 */
function oys_the_archive_title() {
  $title = oys_get_the_archive_title();
  if ( ! empty( $title ) ) :
    echo $title;
  endif;
}
function oys_get_the_archive_title() {
      if ( is_category() ) : $title = sprintf( single_cat_title( '', false ) );
  elseif ( is_tag()      ) : $title = sprintf( '태그: %s', single_tag_title( '', false ) );
  elseif ( is_author()   ) : $title = sprintf( get_the_author() . '의 모든 글' );
  elseif ( is_year()     ) : $title = sprintf( get_the_date( 'Y년' ) );
  elseif ( is_month()    ) : $title = sprintf( get_the_date( 'Y년 F' ) );
  elseif ( is_day()      ) : $title = sprintf( get_the_date( 'Y년 F j일') );
  elseif ( is_tax( 'post_format', 'post-format-aside'   ) ) : $title = '추가 정보';
  elseif ( is_tax( 'post_format', 'post-format-gallery' ) ) : $title = '갤러리';
  elseif ( is_tax( 'post_format', 'post-format-image'   ) ) : $title = '이미지';
  elseif ( is_tax( 'post_format', 'post-format-video'   ) ) : $title = '비디오';
  elseif ( is_tax( 'post_format', 'post-format-quote'   ) ) : $title = '인용';
  elseif ( is_tax( 'post_format', 'post-format-link'    ) ) : $title = '링크';
  elseif ( is_tax( 'post_format', 'post-format-status'  ) ) : $title = '상태';
  elseif ( is_tax( 'post_format', 'post-format-audio'   ) ) : $title = '오디오';
  elseif ( is_tax( 'post_format', 'post-format-chat'    ) ) : $title = '챗';
  elseif ( is_post_type_archive() ) :
    $title = post_type_archive_title( '', false );
  elseif ( is_tax() ) :
    $tax = get_taxonomy( get_queried_object()->taxonomy );
    // $title = sprintf( '%1$s: %2$s', $tax->labels->singular_name, single_term_title( '', false ) );
    $title = sprintf( '%1$s', single_term_title( '', false ) );
  else :
    $title = '보관함';
  endif;

  return $title;
}

/* 보관함 설명 */
function oys_the_archive_description( $before = '', $after = '' ) {
  if ( ! is_post_type_archive() ) :
    $description = term_description();
  else :
    $description = get_queried_object( 'post_type' )->description;
	endif;

  if ( ! empty( $description ) ) :
    echo $before . $description . $after;
  endif;
}
remove_filter( 'term_description', 'wpautop' );

/* 커스텀 요약 생성 */
function oys_trim_excerpt($text = '') {
  /** wp-includes/formatting.php에서 wp_trim_excerpt() 함수를 복제 */
  $text = strip_shortcodes( $text );
  $text = apply_filters( 'the_content', $text );
  $text = str_replace(']]>', ']]&gt;', $text);
  $excerpt_length = apply_filters( 'excerpt_length', 15 );
  $excerpt_more = apply_filters( 'excerpt_more', ' ' . '&hellip;' );
  $text = wp_trim_words( $text, $excerpt_length, $excerpt_more );
  return $text;
}

/* 썸네일: 소스 */
function oys_get_post_thumbnail_src($size = 'full') {
  $post_thumbnail_id = get_post_thumbnail_id();
  return oys_get_attachment_image_src( $post_thumbnail_id, $size );
}

/* 첨부 이미지: 소스 */
function oys_get_attachment_image_src($attachment_id, $size = 'full') {
  $image = wp_get_attachment_image_src( $attachment_id, $size );
  list( $src, $width, $height ) = $image;
  return $src;
}

/**
 * Change the block formats available in TinyMCE.
 * @link http://codex.wordpress.org/TinyMCE_Custom_Styles
 *
 */
function oys_change_mce_block_formats( $init ) {
	$block_formats = array(
		'단락=p',
		'단락 제목=h3',
		'Preformatted=pre',
	);
    $init['block_formats'] = implode( ';', $block_formats );

    // $init['indentation'] = '50px';

    $style_formats = array(
      array(
        'title' => '들여쓰기 단락',
        'block' => 'p',
        'classes' => 'p-indent',
      )
    );

    $init['style_formats'] = wp_json_encode( $style_formats );

	return $init;
}
add_filter( 'tiny_mce_before_init', 'oys_change_mce_block_formats' );
