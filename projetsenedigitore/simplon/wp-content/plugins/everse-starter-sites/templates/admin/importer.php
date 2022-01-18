<?php

defined( 'ABSPATH' ) || exit;
$demos = $this->import_files;
?>
<div class="ess-wrapper wrap">
	
	<h1 class="wp-heading-inline ess-main-title dashicons-before  dashicons-upload">
		<?php 
esc_html_e( 'Everse Starter Sites', 'everse-starter-sites' );
?>
		<?php 
?>
	</h1>

	<div class="theme-browser rendered">
		<?php 

if ( empty($demos) ) {
    ?>
			<div class="notice  notice-info  is-dismissible">
				<p><?php 
    esc_html_e( 'There are no predefined import files available in this theme. Please upload the import files manually!', 'everse-starter-sites' );
    ?></p>
			</div>

		<?php 
} else {
    ?>
			<div class="themes wp-clearfix">

				<?php 
    // Prepare navigation data.
    $categories = ESS_Helpers::get_all_demo_categories( $demos );
    ?>
				<?php 
    
    if ( !empty($categories) ) {
        ?>
					<div class="ess-cat-header">
						<nav class="ess-navigation">
							<ul>
								<li class="active"><a href="#all" class="ess-nav-link"><?php 
        esc_html_e( 'All', 'everse-starter-sites' );
        ?></a></li>
								<?php 
        foreach ( $categories as $key => $name ) {
            ?>
									<li><a href="#<?php 
            echo  esc_attr( $key ) ;
            ?>" class="ess-nav-link"><?php 
            echo  esc_html( $name ) ;
            ?></a></li>
								<?php 
        }
        ?>
							</ul>
						</nav>
						<div clas="ess-search-wrapper">
							<input type="search" class="ess-search-input" name="ess-gl-search" value="" placeholder="<?php 
        esc_html_e( 'Search demos...', 'everse-starter-sites' );
        ?>">
						</div>
					</div>
				<?php 
    }
    
    ?>

				<div class="wp-clearfix ess-item-container">

					<?php 
    foreach ( $demos as $index => $key ) {
        ?>
						<div class="theme-wrap" data-categories="<?php 
        echo  esc_attr( ESS_Helpers::get_demo_item_categories( $key ) ) ;
        ?>" data-name="<?php 
        echo  esc_attr( strtolower( $key['file_name'] ) ) ;
        ?>">

							<div class="theme">

								<?php 
        
        if ( isset( $key['is_pro'] ) ) {
            ?>
									<span class="theme-pro-label"><?php 
            echo  esc_html__( 'pro', 'everse-starter-sites' ) ;
            ?></span>
								<?php 
        }
        
        ?>

								<div class="theme-screenshot">

									<img src="<?php 
        echo  $key['preview_image'] ;
        ?>" />

									<div class="demo-import-loader preview-all preview-all-<?php 
        echo  esc_attr( $key['file_name'] ) ;
        ?>"></div>

									<div class="demo-import-loader preview-icon preview-<?php 
        echo  esc_attr( $key['file_name'] ) ;
        ?>"><i class="custom-loader"></i></div>
								</div>

								<div class="theme-id-container">

									<h2 class="theme-name" id="<?php 
        echo  esc_attr( $key['file_name'] ) ;
        ?>"><?php 
        echo  ucwords( $key['file_name'] ) ;
        ?></h2>

									<div class="theme-actions">

										<button class="button button-primary ss-import-plugin-data" value="<?php 
        echo  esc_attr( $index ) ;
        ?>"><?php 
        esc_html_e( 'Import', 'everse-starter-sites' );
        ?></button>

										<a class="button" href="<?php 
        echo  $key['preview'] ;
        ?>" target="_blank"><?php 
        _e( 'Live Preview', 'woovina-sites' );
        ?></a>
									</div>

								</div>

							</div>

						</div>

					<?php 
    }
    ?>
				</div>

			</div>
		<?php 
}

?>
	</div>

	<p class="ess_ajax-loader js-ess-ajax-loader">
		<span class="spinner"></span> <?php 
esc_html_e( 'Importing, please wait!', 'everse-starter-sites' );
?>
	</p>

	<div class="ess-ajax-response"></div>
</div>