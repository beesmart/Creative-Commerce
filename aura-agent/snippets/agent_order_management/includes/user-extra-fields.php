

<?php  

/**
 * Allows agent asignment on the user profile page
 *
 * @since 1.0.0
 *
*/


global $wpdb;
	
	$args = array(
		'role'=>'store_agent'
	);
	$users = get_users($args);
$user_id = 0;
if(isset($user->ID)) {
	$user_id = $user->ID;
}
$agent_user = get_user_meta($user_id, '_agent_user', true);
  ?>

<table class="form-table">
    <tr>
        <th><label for="assign_perk"><?php esc_html_e( 'Assign Agent', 'crf' ); ?></label></th>
        <td>
            <select name="_assign_user" id="_assign_user">
				<option value=""> -- Select Agent -- </option>
                <?php 
    foreach($users as $user) {
				echo  '<option value="'.$user->ID.'" '.($agent_user == $user->ID?'selected="selected"':'').'>'.ucwords($user->display_name).'</option>';

    }
                ?>
            </select>
        </td>
    </tr>
</table>