<?php
namespace AffWP\REST\Admin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Core class used to display a list table of API records.
 *
 * @since 1.9
 */
class List_Table extends \WP_List_Table {

	/**
	 * Number of records to list per page.
	 *
	 * @access public
	 * @since  1.9
	 * @var    int
	 */
	public $per_page = 30;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @since  1.9
	 *
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
			'singular'  => __( 'API Key', 'affiliate-wp' ),
			'plural'    => __( 'API Keys', 'affiliate-wp' ),
			'ajax'      => false,
		) );

		$this->query();
	}

	/**
	 * Message to be displayed when there are no consumers.
	 *
	 * @access public
	 * @since  1.9
	 */
	public function no_items() {
		_e( 'No API consumers found.', 'affiliate-wp' );
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 2.5
	 * @access protected
	 *
	 * @return string Name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'username';
	}

	/**
	 * Renders most of the columns in the list table.
	 *
	 * @access  public
	 * @since   1.9
	 *
	 * @param array $item Contains all the data of the keys
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Renders the 'Public Key' column.
	 *
	 * @access public
	 * @since  1.9
	 *
	 * @param \AffWP\REST\Consumer $item Current REST consumer.
	 * @return string Display information for the public key.
	 */
	public function column_public_key( $item ) {
		return '<input readonly="readonly" type="text" class="large-text" value="' . esc_attr( $item->public_key ) . '"/>';
	}

	/**
	 * Renders the 'Token' column.
	 *
	 * @access public
	 * @since  1.9
	 *
	 * @param \AffWP\REST\Consumer $item Current REST consumer.
	 * @return string Display information for the token.
	 */
	public function column_token( $item ) {
		return '<input readonly="readonly" type="text" class="large-text" value="' . esc_attr( $item->token ) . '"/>';
	}

	/**
	 * Renders the 'Secret Key' column.
	 *
	 * @access public
	 * @since  1.9
	 *
	 * @param \AffWP\REST\Consumer $item Current REST consumer.
	 * @return string Display information for the secret key.
	 */
	public function column_secret_key( $item ) {
		return '<input readonly="readonly" type="text" class="large-text" value="' . esc_attr( $item->secret_key ) . '"/>';
	}

	/**
	 * Renders the 'username' column.
	 *
	 * @access public
	 * @since  1.9
	 *
	 * @see row_actions()
	 *
	 * @param \AffWP\REST\Consumer $item Current REST consumer.
	 * @return string Display information for the user.
	 */
	public function column_username( $item ) {

		$actions = array();

		$actions['reissue'] = sprintf(
			'<a href="%1$s" class="affwp-regenerate-api-key">%2$s</a>',
			esc_url( wp_nonce_url( add_query_arg( array(
				'user_id'           => $item->user_id,
				'affwp_action'      => 'process_api_key',
				'affwp_api_process' => 'regenerate'
			) ), 'affwp-api-nonce' ) ),
			__( 'Reissue', 'affiliate-wp' )
		);

		$actions['revoke'] = sprintf(
			'<a href="%s" class="affwp-revoke-api-key affwp-delete">%s</a>',
			esc_url( wp_nonce_url( add_query_arg( array(
				'user_id'           => $item->user_id,
				'affwp_action'      => 'process_api_key',
				'affwp_api_process' => 'revoke'
			) ), 'affwp-api-nonce' ) ),
			__( 'Revoke', 'affiliate-wp' )
		);

		/**
		 * Filters the row actions for a given consumer.
		 *
		 * @since 1.9
		 *
		 * @param array                $actions Consumer row actions.
		 * @param \AffWP\REST\Consumer $item    Current REST consumer.
		 */
		$actions = apply_filters( 'affwp_api_row_actions', array_filter( $actions ), $item );

		$username = sprintf( '<a href="%1$s"><strong>%2$s</strong></a>',
			esc_url( add_query_arg( 'user_id', $item->user_id, admin_url( 'user-edit.php' ) ) ),
			affiliate_wp()->REST->consumers->get_consumer_username( $item->user_id )
		);

		return sprintf('%1$s %2$s', $username, $this->row_actions( $actions ) );
	}

	/**
	 * Retrieves the consumers table columns.
	 *
	 * @access public
	 * @since  1.9
	 *
	 * @return array $columns Array of all the consumer table columns.
	 */
	public function get_columns() {
		$columns = array(
			'username'   => __( 'Username', 'affiliate-wp' ),
			'public_key' => __( 'Public Key', 'affiliate-wp' ),
			'token'      => __( 'Token', 'affiliate-wp' ),
			'secret_key' => __( 'Secret Key', 'affiliate-wp' ),
		);

		return $columns;
	}

	/**
	 * Displays the key generation form.
	 *
	 * @access public
	 * @since  1.9
	 *
	 * @param string $which Optional. Which location the builk actions are being rendered for.
	 *                      Will be 'top' or 'bottom'. Default empty.
	 */
	public function bulk_actions( $which = '' ) {
		// These aren't really bulk actions but this outputs the markup in the right place.
		if ( 'top' === $which ) :
			$action_url = add_query_arg( array(
				'page' => 'affiliate-wp-tools',
				'tab'  => 'api_keys'
			), 'admin.php' );
			?>
			<form id="api-key-generate-form" method="post" action="<?php echo esc_attr( $action_url ); ?>">
				<input type="hidden" name="affwp_action" value="process_api_key" />
				<input type="hidden" name="affwp_api_process" value="generate" />
				<?php wp_nonce_field( 'affwp-api-nonce' ); ?>
				<span class="affwp-ajax-search-wrap">
					<input type="text" name="user_name" id="user_name" class="affwp-user-search" autocomplete="off" placeholder="<?php esc_attr_e( 'Enter username', 'affiliate-wp' ); ?>" />
					<input type="hidden" name="user_id" id="user_id" value="" />
				</span>
				<?php submit_button( __( 'Generate New API Keys', 'affiliate-wp' ), 'secondary', 'submit', false ); ?>
			</form>
		<?php endif;
	}

	/**
	 * Generates the table navigation above and below the table.
	 *
	 * @access protected
	 * @since  1.9
	 *
	 * @param string $which Which location the builk actions are being rendered for. Will be 'top'
	 *                      or 'bottom'.
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear" />
		</div>
		<?php
	}

	/**
	 * Retrieves the current page number.
	 *
	 * @access public
	 * @since  1.9
	 *
	 * @return int Current page number.
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}

	/**
	 * Performs the key query for consumers.
	 *
	 * @access public
	 * @since  1.9
	 *
	 * @return array Array of consumer objects.
	 */
	public function consumers_data() {

		$order    = isset( $_GET['order'] )   ? $_GET['order']           : 'DESC';
		$orderby  = isset( $_GET['orderby'] ) ? $_GET['orderby']         : 'consumer_id';
		$per_page = $this->get_items_per_page( 'affwp_edit_affiliates_per_page', $this->per_page );
		$offset   = $per_page * ( $this->get_paged() - 1 );

		$consumers = affiliate_wp()->REST->consumers->get_consumers( array(
			'number'  => $per_page,
			'offset'  => $offset,
			'orderby' => $orderby,
			'order'   => $order
		) );

		return $consumers;
	}



	/**
	 * Retrieves the total consumers count.
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @return int Total consumers count.
	 */
	public function total_items() {
		return affiliate_wp()->REST->consumers->count();
	}

	/**
	 * Sets up the final data for the table.
	 *
	 * @access public
	 * @since  1.9
	 *
	 * @see consumers_data()
	 */
	public function prepare_items() {
		$columns = $this->get_columns();

		$hidden = array(); // No hidden columns
		$sortable = array(); // Not sortable... for now

		$this->_column_headers = array( $columns, $hidden, $sortable, 'id' );

		$data = $this->consumers_data();

		$total_items = $this->total_items();

		$this->items = $data;

		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $total_items / $this->per_page ),
			)
		);
	}
}