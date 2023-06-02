<tr valign="top">
	<th scope="row" class="titledesc"><label
		for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text">
				<span><?php echo wp_kses_post( $data['title'] ); ?></span>
			</legend>
			<label for="<?php echo esc_attr( $field_key ); ?>">
				<div id="<?php echo $data['id']; ?>"></div>
			<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
		
		
		</fieldset>
	</td>
</tr>
