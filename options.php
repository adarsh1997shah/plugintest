<?php 
if ( get_option( 'ibx_docs_type' ) ) {
	$options = maybe_unserialize( get_option( 'ibx_docs_type', 'Option not found' ) );
} else {
	$options = array();
}
?>


<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<h2><?php esc_html_e( 'Docs Created', 'plugintest' ); ?></h2>
	<table class="show-option-table">
		<tr class="table-row">
			<th class="table-header" scope="row"><?php esc_html_e( 'Docs Title', 'plugintest' ); ?></th>
			<th class="table-header" scope="row"><?php esc_html_e( 'Docs Slug', 'plugintest' ); ?></th>
			<th class="table-header" scope="row"><?php esc_html_e( 'Action', 'plugintest' ); ?></th>
		</tr>
	<?php
	if ( ! empty( $options ) ) {
		foreach ( $options as $option ) {
	?>
		<tr class="table-row">
			<td class="table-data" scope="row"><?php esc_html_e( $option['title'], 'plugintest' ); ?></td>
			<td class="table-data"><?php esc_html_e( $option['slug'], 'plugintest' ); ?></td>
			<td class="table-data">
				<input type="button" name="edit" id="edit" class="button button-primary" value="Edit">
				<input type="button" name="delete" id="delete" class="button button-primary" value="Delete">
			</td>
		</tr>
	<?php
		}
	}
	?>
	</table>

	<form action="" method="post">
		<?php wp_nonce_field( 'docs__form_save', 'docs_generate_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="docs_title"><?php esc_html_e( 'Docs Title', 'plugintest' ); ?></label>
				</th>
				<td>
					<input type="text" name="title" id="docs_title" class="regular-text" value="" placeholder = "Title" required>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="docs_slug"><?php esc_html_e( 'Docs Slug', 'plugintest' ); ?></label>
				</th>
				<td>
					<input type="text" name="slug" id="docs_slug" class="regular-text" value="" placeholder = "Slug">
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'ADD', 'plugintest' ) ); ?>
	</form>
</div>
