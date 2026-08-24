<?php
/**
 * Bounded catalog and inventory checks.
 *
 * @package StoreCheckup
 */

namespace StoreCheckup\Checks;

use StoreCheckup\Result;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Product_Checks {
	const MAX_PRODUCTS   = 1000;
	const MAX_VARIATIONS = 2000;
	const PAGE_SIZE      = 100;

	public function run() {
		$counts = array(
			'scanned'                 => 0,
			'variations_scanned'      => 0,
			'missing_price'           => 0,
			'missing_image'           => 0,
			'missing_sku'             => 0,
			'duplicate_sku'           => 0,
			'missing_short_desc'      => 0,
			'uncategorized'           => 0,
			'variable_no_children'    => 0,
			'variation_missing_price' => 0,
			'download_missing_files'  => 0,
			'external_missing_url'    => 0,
			'out_of_stock'            => 0,
			'low_stock'               => 0,
			'negative_stock'          => 0,
			'backorders'              => 0,
			'draft_pending'           => 0,
		);

		$seen_skus = array();
		$page      = 1;

		do {
			$query = wc_get_products(
				array(
					'status'   => array( 'publish', 'private', 'draft', 'pending' ),
					'limit'    => self::PAGE_SIZE,
					'page'     => $page,
					'paginate' => true,
					'return'   => 'objects',
				)
			);

			$products = isset( $query->products ) ? $query->products : array();
			foreach ( $products as $product ) {
				if ( $counts['scanned'] >= self::MAX_PRODUCTS ) {
					break 2;
				}

				++$counts['scanned'];
				$status = $product->get_status();
				if ( in_array( $status, array( 'draft', 'pending' ), true ) ) {
					++$counts['draft_pending'];
				}

				if ( '' === (string) $product->get_price() && ! $product->is_type( 'variable' ) ) {
					++$counts['missing_price'];
				}
				if ( ! $product->get_image_id() ) {
					++$counts['missing_image'];
				}

				$sku = trim( (string) $product->get_sku() );
				if ( '' === $sku ) {
					++$counts['missing_sku'];
				} else {
					$sku_key = strtolower( $sku );
					if ( isset( $seen_skus[ $sku_key ] ) ) {
						++$counts['duplicate_sku'];
					} else {
						$seen_skus[ $sku_key ] = true;
					}
				}

				if ( '' === trim( wp_strip_all_tags( (string) $product->get_short_description() ) ) ) {
					++$counts['missing_short_desc'];
				}
				if ( empty( $product->get_category_ids() ) ) {
					++$counts['uncategorized'];
				}
				if ( $product->is_downloadable() && empty( $product->get_downloads() ) ) {
					++$counts['download_missing_files'];
				}
				if ( $product->is_type( 'external' ) && '' === trim( (string) $product->get_product_url() ) ) {
					++$counts['external_missing_url'];
				}
				if ( ! $product->is_in_stock() ) {
					++$counts['out_of_stock'];
				}
				if ( $product->backorders_allowed() ) {
					++$counts['backorders'];
				}

				if ( $product->managing_stock() ) {
					$quantity = $product->get_stock_quantity();
					if ( null !== $quantity ) {
						$quantity = (float) $quantity;
						if ( $quantity < 0 ) {
							++$counts['negative_stock'];
						} elseif ( $quantity > 0 ) {
							$low_amount = function_exists( 'wc_get_low_stock_amount' ) ? wc_get_low_stock_amount( $product ) : 2;
							if ( $quantity <= (float) $low_amount ) {
								++$counts['low_stock'];
							}
						}
					}
				}

				if ( $product->is_type( 'variable' ) ) {
					$children = $product->get_children();
					if ( empty( $children ) ) {
						++$counts['variable_no_children'];
					} else {
						foreach ( $children as $variation_id ) {
							if ( $counts['variations_scanned'] >= self::MAX_VARIATIONS ) {
								break;
							}
							$variation = wc_get_product( $variation_id );
							if ( ! $variation ) {
								continue;
							}
							++$counts['variations_scanned'];
							if ( '' === (string) $variation->get_price() ) {
								++$counts['variation_missing_price'];
							}
						}
					}
				}
			}

			++$page;
			$max_pages = isset( $query->max_num_pages ) ? (int) $query->max_num_pages : 1;
		} while ( $page <= $max_pages && $counts['scanned'] < self::MAX_PRODUCTS );

		$products_url = admin_url( 'edit.php?post_type=product' );
		$stock_url    = admin_url( 'admin.php?page=wc-reports&tab=stock' );

		return array(
			new Result(
				'products-scanned',
				'products',
				Result::INFO,
				__( 'Catalog scan completed', 'rejoyan-store-health' ),
				sprintf(
					/* translators: 1: products scanned, 2: variations scanned, 3: product scan cap, 4: variation scan cap. */
					__( 'Scanned %1$d products and %2$d variations. Product scans are capped at %3$d products and %4$d variations per request.', 'rejoyan-store-health' ),
					$counts['scanned'],
					$counts['variations_scanned'],
					self::MAX_PRODUCTS,
					self::MAX_VARIATIONS
				),
				$counts['scanned']
			),
			$this->issue( 'missing-price', 'products', __( 'Products without a price', 'rejoyan-store-health' ), $counts['missing_price'], Result::CRITICAL, __( 'Non-variable products without a price may be unavailable for normal purchase flows.', 'rejoyan-store-health' ), $products_url ),
			$this->issue( 'variation-missing-price', 'products', __( 'Variations without a price', 'rejoyan-store-health' ), $counts['variation_missing_price'], Result::CRITICAL, __( 'A purchasable variation normally needs a price.', 'rejoyan-store-health' ), $products_url ),
			$this->issue( 'empty-variable-products', 'products', __( 'Variable products without variations', 'rejoyan-store-health' ), $counts['variable_no_children'], Result::CRITICAL, __( 'A variable product with no variations cannot present normal variation choices.', 'rejoyan-store-health' ), $products_url ),
			$this->issue( 'download-missing-files', 'products', __( 'Downloadable products without files', 'rejoyan-store-health' ), $counts['download_missing_files'], Result::CRITICAL, __( 'A downloadable product should normally have at least one downloadable file assigned.', 'rejoyan-store-health' ), $products_url ),
			$this->issue( 'external-missing-url', 'products', __( 'External products without a product URL', 'rejoyan-store-health' ), $counts['external_missing_url'], Result::CRITICAL, __( 'External/affiliate products need a destination URL to work as intended.', 'rejoyan-store-health' ), $products_url ),
			$this->issue( 'missing-image', 'products', __( 'Products without a featured image', 'rejoyan-store-health' ), $counts['missing_image'], Result::WARNING, __( 'Product imagery is a major part of catalog usability.', 'rejoyan-store-health' ), $products_url ),
			$this->issue( 'uncategorized-products', 'products', __( 'Products without a category', 'rejoyan-store-health' ), $counts['uncategorized'], Result::WARNING, __( 'Categories help customers browse and managers maintain the catalog.', 'rejoyan-store-health' ), $products_url ),
			$this->issue( 'duplicate-sku', 'products', __( 'Duplicate SKUs in the bounded scan', 'rejoyan-store-health' ), $counts['duplicate_sku'], Result::WARNING, __( 'Duplicate SKUs can confuse inventory workflows and external integrations.', 'rejoyan-store-health' ), $products_url ),
			$this->issue( 'missing-sku', 'products', __( 'Products without an SKU', 'rejoyan-store-health' ), $counts['missing_sku'], Result::INFO, __( 'SKUs are optional but useful for inventory and integrations.', 'rejoyan-store-health' ), $products_url ),
			$this->issue( 'missing-short-description', 'products', __( 'Products without a short description', 'rejoyan-store-health' ), $counts['missing_short_desc'], Result::INFO, __( 'A concise product summary can improve catalog consistency.', 'rejoyan-store-health' ), $products_url ),
			$this->issue( 'draft-pending-products', 'products', __( 'Draft or pending products', 'rejoyan-store-health' ), $counts['draft_pending'], Result::INFO, __( 'Review unfinished catalog items periodically so stale drafts do not accumulate.', 'rejoyan-store-health' ), $products_url ),
			$this->issue( 'negative-stock', 'inventory', __( 'Products with negative stock quantities', 'rejoyan-store-health' ), $counts['negative_stock'], Result::CRITICAL, __( 'Negative inventory can indicate overselling, imports, or stock synchronization problems.', 'rejoyan-store-health' ), $stock_url ),
			$this->issue( 'low-stock', 'inventory', __( 'Low-stock products', 'rejoyan-store-health' ), $counts['low_stock'], Result::WARNING, __( 'These products are at or below their configured low-stock threshold.', 'rejoyan-store-health' ), $stock_url ),
			new Result(
				'out-of-stock',
				'inventory',
				Result::INFO,
				__( 'Out-of-stock products', 'rejoyan-store-health' ),
				/* translators: %d: number of scanned products currently out of stock. */
				sprintf( _n( '%d scanned product is currently out of stock.', '%d scanned products are currently out of stock.', $counts['out_of_stock'], 'rejoyan-store-health' ), $counts['out_of_stock'] ),
				$counts['out_of_stock'],
				$stock_url,
				__( 'Review stock', 'rejoyan-store-health' )
			),
			new Result(
				'backorders-enabled',
				'inventory',
				Result::INFO,
				__( 'Products allowing backorders', 'rejoyan-store-health' ),
				/* translators: %d: number of scanned products that allow backorders. */
				sprintf( _n( '%d scanned product allows backorders.', '%d scanned products allow backorders.', $counts['backorders'], 'rejoyan-store-health' ), $counts['backorders'] ),
				$counts['backorders'],
				$stock_url,
				__( 'Review stock', 'rejoyan-store-health' )
			),
		);
	}

	private function issue( $id, $area, $title, $count, $severity, $description, $url ) {
		if ( $count > 0 ) {
			return new Result(
				$id,
				$area,
				$severity,
				$title,
				/* translators: 1: number of affected products or variations, 2: diagnostic explanation. */
				sprintf( _n( '%1$d affected item. %2$s', '%1$d affected items. %2$s', $count, 'rejoyan-store-health' ), $count, $description ),
				$count,
				$url,
				'inventory' === $area ? __( 'Review inventory', 'rejoyan-store-health' ) : __( 'Open products', 'rejoyan-store-health' )
			);
		}

		return new Result(
			$id,
			$area,
			Result::PASSED,
			$title,
			__( 'No affected items were found in the bounded scan.', 'rejoyan-store-health' ),
			0,
			$url,
			'inventory' === $area ? __( 'Review inventory', 'rejoyan-store-health' ) : __( 'Open products', 'rejoyan-store-health' )
		);
	}
}
