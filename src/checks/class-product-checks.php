<?php
/** Bounded catalog checks. @package StoreVitals */
namespace StoreVitals\Checks;
use StoreVitals\Result;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Product_Checks {
	const MAX_PRODUCTS = 1000; const PAGE_SIZE = 100;
	public function run() {
		$counts = array( 'scanned'=>0, 'missing_price'=>0, 'missing_image'=>0, 'missing_sku'=>0, 'missing_short_desc'=>0, 'uncategorized'=>0, 'variable_no_children'=>0, 'out_of_stock'=>0 );
		$page = 1;
		do {
			$query = wc_get_products( array( 'status'=>array('publish','private','draft','pending'), 'limit'=>self::PAGE_SIZE, 'page'=>$page, 'paginate'=>true, 'return'=>'objects' ) );
			$products = isset( $query->products ) ? $query->products : array();
			foreach ( $products as $product ) {
				if ( $counts['scanned'] >= self::MAX_PRODUCTS ) { break 2; }
				++$counts['scanned'];
				if ( '' === (string) $product->get_price() ) { ++$counts['missing_price']; }
				if ( ! $product->get_image_id() ) { ++$counts['missing_image']; }
				if ( '' === trim( (string) $product->get_sku() ) ) { ++$counts['missing_sku']; }
				if ( '' === trim( wp_strip_all_tags( (string) $product->get_short_description() ) ) ) { ++$counts['missing_short_desc']; }
				if ( empty( $product->get_category_ids() ) ) { ++$counts['uncategorized']; }
				if ( $product->is_type( 'variable' ) && empty( $product->get_children() ) ) { ++$counts['variable_no_children']; }
				if ( ! $product->is_in_stock() ) { ++$counts['out_of_stock']; }
			}
			++$page; $max_pages = isset( $query->max_num_pages ) ? (int) $query->max_num_pages : 1;
		} while ( $page <= $max_pages && $counts['scanned'] < self::MAX_PRODUCTS );
		$url = admin_url( 'edit.php?post_type=product' );
		return array(
			new Result( 'products-scanned', 'products', Result::INFO, __( 'Product scan completed', 'storevitals' ), sprintf( __( 'Scanned %1$d products. Each scan is capped at %2$d products.', 'storevitals' ), $counts['scanned'], self::MAX_PRODUCTS ), $counts['scanned'] ),
			$this->issue( 'missing-price', __( 'Products without a price', 'storevitals' ), $counts['missing_price'], Result::CRITICAL, __( 'Products without a price may be unavailable for normal purchase flows.', 'storevitals' ), $url ),
			$this->issue( 'missing-image', __( 'Products without a featured image', 'storevitals' ), $counts['missing_image'], Result::WARNING, __( 'Product imagery is a major part of catalog usability.', 'storevitals' ), $url ),
			$this->issue( 'missing-sku', __( 'Products without an SKU', 'storevitals' ), $counts['missing_sku'], Result::INFO, __( 'SKUs are optional but useful for inventory and integrations.', 'storevitals' ), $url ),
			$this->issue( 'missing-short-description', __( 'Products without a short description', 'storevitals' ), $counts['missing_short_desc'], Result::INFO, __( 'A concise product summary can improve catalog consistency.', 'storevitals' ), $url ),
			$this->issue( 'uncategorized-products', __( 'Products without a category', 'storevitals' ), $counts['uncategorized'], Result::WARNING, __( 'Categories help customers browse and managers maintain the catalog.', 'storevitals' ), $url ),
			$this->issue( 'empty-variable-products', __( 'Variable products without variations', 'storevitals' ), $counts['variable_no_children'], Result::CRITICAL, __( 'A variable product with no variations cannot present normal variation choices.', 'storevitals' ), $url ),
			new Result( 'out-of-stock', 'inventory', Result::INFO, __( 'Out-of-stock products', 'storevitals' ), sprintf( _n( '%d scanned product is currently out of stock.', '%d scanned products are currently out of stock.', $counts['out_of_stock'], 'storevitals' ), $counts['out_of_stock'] ), $counts['out_of_stock'], $url, __( 'Open products', 'storevitals' ) )
		);
	}
	private function issue( $id, $title, $count, $severity, $description, $url ) {
		if ( $count > 0 ) { return new Result( $id, 'products', $severity, $title, sprintf( _n( '%1$d affected product. %2$s', '%1$d affected products. %2$s', $count, 'storevitals' ), $count, $description ), $count, $url, __( 'Open products', 'storevitals' ) ); }
		return new Result( $id, 'products', Result::PASSED, $title, __( 'No affected products were found in the bounded scan.', 'storevitals' ), 0, $url, __( 'Open products', 'storevitals' ) );
	}
}
