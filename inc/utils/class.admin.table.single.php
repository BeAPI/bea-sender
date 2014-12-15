<?php
class Bea_Sender_Admin_Table_Single extends WP_List_Table {
	/**
	 * Constructor
	 *
	 * @author Nicolas Juen
	 */
	public function __construct( ) {
		parent::__construct( array(
			'singular' => 'sender', // singular name of the listed records
			'plural' => 'senders', // plural name of the listed records
			'ajax' => false	// does this table support ajax?
		) );
	}

	/**
	 * @return array
	 * @author Nicolas Juen
	 */
	function get_views( ) {
		$base_url = add_query_arg( array( 'page' => 'bea_sender', 'c_id' => isset( $_GET['c_id'] ) ? $_GET['c_id'] : 0 ), admin_url( 'tools.php' ) );

		$add = array(
			'all' => sprintf( __( '<a href="%s" class="%s" >All</a>', 'bea_sender' ), $base_url, self::current_view( 'all' ) ),
			'invalid' => sprintf( __( '<a href="%s" class="%s" >Bounced</a>', 'bea_sender' ), add_query_arg( array( 'current_status' => 'invalid' ), $base_url ), self::current_view( 'invalid' ) ),
			'valid' => sprintf( __( '<a href="%s" class="%s" >Valid</a>', 'bea_sender' ), add_query_arg( array( 'current_status' => 'valid' ), $base_url ), self::current_view( 'valid' ) ),
		);

		return $add;
	}

	/**
	 * @param $slug
	 *
	 * @return string
	 * @author Nicolas Juen
	 */
	private static function current_view( $slug ) {
		if( isset( $_GET['current_status'] ) && $_GET['current_status'] === $slug ) {
			return 'current';
		} elseif( $slug == 'all' && !isset( $_GET['current_status'] ) ) {
			return 'current';
		}
		return '';
	}

	/**
	 * @return array
	 * @author Nicolas Juen
	 */
	private static function getAuthStatuses() {
		return array( 'invalid', 'valid' );
	}
	
	/**
	 * Method when no url redirect founded
	 *
	 * @return string
	 * @author Nicolas Juen
	 */
	function no_items( ) {
		_e( 'No sender found.', 'bea_sender' );
	}

	/**
	 * This method display default column
	 *
	 * @return string $item[ $column_name ]
	 * @author Nicolas Juen
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'id' :
			case 'email' :
			case 'bounce_cat' :
			case 'bounce_type' :
			case 'bounce_no' :
				return $item->$column_name;
			break;
			case 'current_status' :
				return Bea_Sender_Client::getStatus( $item->current_status );
			break;
			case 'campaign_status' :
				return Bea_Sender_Client::getStatus( $item->campaign_current_status );
			break;
		}
	}

	/**
	 * Decide which columns to activate the sorting functionality on
	 * @return array $sortable, the array of columns that can be sorted by the user
	 * @author Nicolas Juen
	 */
	function get_sortable_columns( ) {
		return array();
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array $columns, the array of columns to use with the table
	 * @author Nicolas Juen
	 */
	function get_columns( ) {
		return array(
			'id' => __( 'ID', 'bea_sender' ),
			'email' => __( 'Email', 'bea_sender' ),
			'current_status' => __( 'Sender status', 'bea_sender' ),
			'campaign_status' => __( 'Sender campaign status', 'bea_sender' ),
			'bounce_cat' => __( 'Bounce cat', 'bea_sender' ),
			'bounce_type' => __( 'Bounce type', 'bea_sender' ),
			'bounce_no' => __( 'Bounce number', 'bea_sender' ),
		);
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array() $query
	 * @author Nicolas Juen
	 */
	function prepareQuery( ) {
		/* @var $wpdb wpdb */
		global $wpdb;
		
		// Setup the campaign
		$campaign = new Bea_Sender_Campaign( (int)$_GET['c_id'] );

		// Make the order
		$limit = $wpdb->prepare( ' LIMIT %d,%d', ( $this->get_pagenum( ) == 1 ? 0 : $this->get_pagenum( )-1 ) *$this->get_items_per_page( 'bea_s_per_page', BEA_SENDER_PPP ), $this->get_items_per_page( 'bea_s_per_page', BEA_SENDER_PPP ) );
		
		// fitlering by status
		$filter = self::get_status_filter( );
		
		// Get all the receivers
		$receivers = $campaign->get_receivers( $filter , '', $limit );
		
		// check there is data before
		if( !$campaign->isData() ) {
			return array();
		}

		return $receivers;
	}

	/**
	 * This method count total items in table
	 *
	 * @return integer Count(id)
	 * @author Nicolas Juen
	 */
	function totalItems( ) {
		/* @var $wpdb wpdb */
		global $wpdb;
		$filter = self::get_status_filter( );
		// Setup the campaign
		$campaign = new Bea_Sender_Campaign( (int)$_GET['c_id'] );
		
		// Get the receivers
		return $campaign->get_total_receivers( $filter );
	}

	/**
	 * @return false|null|string
	 * @author Nicolas Juen
	 */
	private static function get_status_filter( ) {
		/* @var $wpdb wpdb */
		global $wpdb;
		return !isset( $_GET['current_status'] ) || empty( $_GET['current_status'] ) || !in_array( $_GET['current_status'], self::getAuthStatuses( ) ) ? '' : $wpdb->prepare( ' AND r.current_status = %s', $_GET['current_status'] );
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table
	 * elements
	 *
	 * @author Nicolas Juen
	 */
	function prepare_items( ) {
		$this->_column_headers = array(
			$this->get_columns( ),
			array( ),
			$this->get_sortable_columns( )
		);

		// Get total items
		$total_items = $this->totalItems( );
		$elements_per_page = $this->get_items_per_page( 'bea_s_per_page', BEA_SENDER_PPP );

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page' => $elements_per_page, //WE have to determine how many items to show
			// on a page
			'total_pages' => ceil( $total_items / $elements_per_page )
		) );

		$this->items = $this->prepareQuery( );
	}
}
