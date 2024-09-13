<?php
/**
 * Lost password form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-lost-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_lost_password_form' );
?>

<div class="row justify-content-center">

	<div class="col-md-8 col-lg-6 col-xl-4">
		<form method="post" class="woocommerce-ResetPassword lost_reset_password">

			<div class="alert alert-info mb-5 text-left"><?php echo apply_filters( 'woocommerce_lost_password_message', esc_html__( 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'necromancers' ) ); ?></div><?php // @codingStandardsIgnoreLine ?>

			<div class="form-group">
				<input class="woocommerce-Input woocommerce-Input--text input-text form-control" type="text" name="user_login" id="user_login" autocomplete="username" placeholder="<?php esc_attr_e( 'Username or email', 'necromancers' ); ?>"/>
			</div>

			<?php do_action( 'woocommerce_lostpassword_form' ); ?>

			<div class="form-group">
				<input type="hidden" name="wc_reset_password" value="true" />
				<button type="submit" class="woocommerce-Button btn btn-lg btn-secondary btn-block<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" value="<?php esc_attr_e( 'Reset password', 'necromancers' ); ?>"><?php esc_html_e( 'Reset password', 'necromancers' ); ?></button>
			</div>

			<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>

		</form>
	</div>
	
</div>
<?php
do_action( 'woocommerce_after_lost_password_form' );
