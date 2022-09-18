<?php

namespace MOMO\MPay;

class Hook_Registry {

	public function __construct() {
		$this->add_hooks();
	}

	private function add_hooks() {
		$manage_accounts = new Manage_Accounts();

		//Download clients list.
		add_action('admin_post_mpay_clients_list', [$manage_accounts, 'download_clients_list']);

		//Upload clients list.
		add_action('admin_post_digibook_upload_clients_list', [$manage_accounts, 'upload_clients_list']);

		add_action('wp_ajax_mpay_fund_wallet_now', [$manage_accounts, 'mpay_fund_wallet_now']);
	}
}

new Hook_Registry();
