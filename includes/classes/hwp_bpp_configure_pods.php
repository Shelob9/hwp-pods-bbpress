<?php

class hwp_bpp_configure_pods {

	static $topic_pod_id_key = 'hwp_bpp_topic_pod_id';

	/**
	 * Extends the 'topic' post type from bbPress and/or adds fields to it.
	 *
	 * @since 0.0.1
	 *
	 * @param bool $meta_storage Optional. Whether to use meta storage, the default or table storage.
	 * @param bool $create Optional. Whether to create the Pod, the default, or not.
	 * @param bool $add_fields Optional. If false, the default, no fields will be added. Fields should be specified using the 'hwp_bpp_topic_fields' filter.
	 *
	 * @return array Array containing Topic Pod ID. Also an array of IDs of field IDs, if fields were created.
	 */
	function __construct( $meta_storage = true, $create = true, $add_fields = false ) {
		if ( ! $meta_storage ) {
			$this->activate_table_storage_component();
		}

		$pod_id = $field_ids =false;

		if ( $create ) {
			$pod_id = $this->extend_topic( $meta_storage );

		}

		if ( is_wp_error( $pod_id ) ) {
			return $pod_id->get_error_message();
		}
		else {
			$this->cache_clear();
		}

		if ( $add_fields ) {
			if ( ! $pod_id  ) {
				$pod_id = $this->topic_pods_id();
			}

			if ( $pod_id ) {
				$field_ids = $this->add_fields( $pod_id );
			}

			if ( is_array( $field_ids ) ) {
				$this->cache_clear();
			}

		}

		if ( ! $pod_id ) {
			$pod_id = $this->topic_pods_id();
		}

		return array( 'pod_id' => $pod_id, 'field_ids' => $field_ids );

	}

	/**
	 * Extends the topic Pod
	 *
	 * @since 0.0.1
	 *
	 * @param bool $meta_storage Optional. Whether to use meta storage, the default or table storage.
	 *
	 * @return int|bool The Pod's ID or false if it wasn't extended.
	 */
	function extend_topic( $meta_storage ) {
		if ( ! post_type_exists( 'topic' ) ) {
			return new WP_Error( 'hwp_bbp_no_topic_cpt', __( 'The bbPress topic custom post type does not exist.', 'hwp_bbp' ) );
		}
		$storage = 'meta';
		if ( ! $meta_storage ) {
			$storage = 'table';
		}

		$params['name'] = 'topic';
		$params['type'] ='post_type';
		$params['object'] = 'topic';
		$params[ 'storage' ] = $storage;

		$pod_id = $this->api()->save_pod( $params  );

		update_option( self::$topic_pod_id_key, $pod_id );

		return $pod_id;

	}

	/**
	 * Get the topic Pods' ID
	 *
	 * @return mixed|void
	 */
	function topic_pods_id() {
		if ( false === ( $pod_id = get_option( self::$topic_pod_id_key ) ) ) {
			$pod = $this->api()->load_pod( 'topic' );
			$pod_id  = $pod[ 'id' ];

			update_option( self::$topic_pod_id_key, $pod_id );

		}

		return $pod_id;
	}

	/**
	 * Add fields to the topic Pod
	 *
	 * NOTE: By default this method does not add any fields. You must add an array of fields to the 'hwp_bpp_topic_fields' filter for it to work.
	 *
	 * @since 0.0.1
	 *
	 * @param $pod_id Id of the topic Pod
	 *
	 * @return array Array of field IDs
	 */
	function add_fields( $pod_id ) {
		$fields = apply_filters( 'hwp_bpp_topic_fields', false );

		$ids = false;
		if ( is_array( $fields ) ) {
			$pod_id = $this->topic_pods_id();
			if ( false === $pod_id ) {
				return;
			}

			foreach ( $fields as $field => $params ) {
				$params[ 'pod_id' ] = $pod_id;
				$ids[] = $this->api()->save_field( $params );
			}

		}

		if ( is_array( $ids ) ) {
			return $ids;
		}

	}

	/**
	 * Activates the table storage component
	 *
	 * @since 0.0.1
	 */
	private function activate_table_storage_component(){
		$component_settings = PodsInit::$components->settings;
		$component_settings['components']['table-storage'] = array();

		update_option( 'pods_component_settings', json_encode($component_settings));
	}

	/**
	 * Returns an instance of the Pods_API class
	 * @since 0.0.1
	 *
	 * @return PodsAPI
	 */
	private function api() {

		return pods_api();

	}

	/**
	 * Clears Pods cache
	 *
	 * @since 0.0.1
	 */
	private function cache_clear() {
		pods_cache_clear();
		pods_transient_clear();
	}
} 
