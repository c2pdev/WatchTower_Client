<?php
/**
 * Created by PhpStorm.
 * User: Myszczyszyn Dawid - Code2Prog
 * Date: 23.01.2016
 * Time: 16:31
 */

namespace Whatarmy_Watchtower;


class Watchtower {
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		//add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
	}

	public function dashboard_widget_function( $post, $callback_args ) {
		$rss = fetch_feed( 'https://whatarmy.com/feed/' );

		$maxitems = 0;

		if ( ! is_wp_error( $rss ) ) :
			$maxitems = $rss->get_item_quantity( 8 );
			$rss_items = $rss->get_items( 0, $maxitems );

		endif;
		?>
        <ul>
		<?php if ( $maxitems == 0 ) : ?>
            <li><?php _e( 'Nothing to show!', 'my-text-domain' ); ?></li>
		<?php else : ?>
			<?php foreach ( $rss_items as $item ) : ?>
                <li>
                    <a href="<?php echo esc_url( $item->get_permalink() ); ?>" target="_blank"
                       title="<?php printf( __( 'Posted %s', 'my-text-domain' ), $item->get_date( 'j F Y | g:i a' ) ); ?>">
						<?php echo esc_html( $item->get_title() ); ?>
                    </a>
                </li>
			<?php endforeach; ?>
		<?php endif; ?>
        </ul><?php
	}

	public function add_dashboard_widgets() {
		wp_add_dashboard_widget( 'dashboard_widget', 'Whatarmy Tips & Tricks', array(
			$this,
			'dashboard_widget_function'
		) );
	}


	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		add_options_page(
			'Settings Watchtower',
			'Watchtower Settings',
			'manage_options',
			'watchtower-setting-admin',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {

		$this->options = get_option( 'watchtower' );
		?>
        <div class="wrap">
            <style>
                .watchtower_token_field {
                    background: #ffea96 !important;
                }

                .watchtower_token_area, .watchtower_token_field {
                    font-size: 20px;
                    padding: 10px;
                }

                .watchtower_token_area {
                    margin: auto;
                    float: left;
                    padding-left: 0px;
                }
            </style>
            <h2>Watchtower Settings</h2>
            <form method="post" action="options.php">
				<?php
				settings_fields( 'watchtower' );
				do_settings_sections( 'watchtower-settings' );
				submit_button( 'Update settings' );
				?>
            </form>
        </div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init() {
		register_setting(
			'watchtower',
			'watchtower',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'access_token_section',
			'Access Token',
			array( $this, 'access_token_info' ),
			'watchtower-settings'
		);

		add_settings_field(
			'access_token',
			null,
			array( $this, 'access_token_callback' ),
			'watchtower-settings',
			'access_token_section',
			array()
		);
	}

	/**
	 * @param $input
	 *
	 * @return array
	 */
	public function sanitize( $input ) {
		$new_input = array();
		if ( isset( $input['access_token'] ) ) {
			$new_input['access_token'] = $input['access_token'];
		}

		return $new_input;
	}

	/**
	 *
	 */
	public function access_token_info() {
		print '<span class="watchtower_token_area">CurrentToken: <span class="watchtower_token_field">' . get_option( 'watchtower' )['access_token'] . '</span></span>';
	}

	/**
	 *
	 */
	public function access_token_callback() {
		printf(
			'<input type="hidden" id="access_token" name="watchtower[access_token]" value="' . Token::generateToken() . '" />',
			isset( $this->options['access_token'] ) ? esc_attr( $this->options['access_token'] ) : ''
		);
	}
}