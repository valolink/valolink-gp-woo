<?php
/**
 * GenerateBlocks Pro condition types — "WooCommerce Product" (single-product rules) and
 * "WooCommerce Product Archive" (product taxonomy archive rules). Operators: is | is_not.
 * Outside the matching context (or when WC is absent) every rule evaluates false, so a
 * condition never accidentally shows/hides blocks on unrelated pages.
 */

defined('ABSPATH') || exit;

add_action('generateblocks_register_conditions', function () {
    if (!class_exists('GenerateBlocks_Pro_Condition_Abstract')) {
        return;
    }
    if (!class_exists('GenerateBlocks_Pro_Conditions_Registry')) {
        return;
    }

    if (!class_exists('GP_WC_Condition_Product')) {
        class GP_WC_Condition_Product extends GenerateBlocks_Pro_Condition_Abstract {
            public function evaluate($rule, $operator, $value, $context = []) {
                if (!function_exists('wc_get_product')) {
                    return false;
                }
                $post_id = !empty($context['post_id']) ? $context['post_id'] : get_the_ID();
                $p = $post_id ? wc_get_product($post_id) : null;
                if (!$p instanceof WC_Product) {
                    return false;
                }
                switch ($rule) {
                    case 'is_on_sale':      $match = $p->is_on_sale(); break;
                    case 'is_featured':     $match = $p->is_featured(); break;
                    case 'is_in_stock':     $match = $p->is_in_stock(); break;
                    case 'is_on_backorder': $match = 'onbackorder' === $p->get_stock_status(); break;
                    case 'is_purchasable':  $match = $p->is_purchasable(); break;
                    case 'has_reviews':     $match = $p->get_review_count() > 0; break;
                    default:                $match = false;
                }
                return 'is_not' === $operator ? !$match : $match;
            }
            public function get_rules() {
                return [
                    'is_on_sale'      => 'On sale',
                    'is_featured'     => 'Featured',
                    'is_in_stock'     => 'In stock',
                    'is_on_backorder' => 'On backorder',
                    'is_purchasable'  => 'Purchasable',
                    'has_reviews'     => 'Has reviews',
                ];
            }
            public function get_rule_metadata($rule) {
                return ['needs_value' => false, 'value_type' => 'none'];
            }
        }
    }

    if (!class_exists('GP_WC_Condition_Product_Archive')) {
        class GP_WC_Condition_Product_Archive extends GenerateBlocks_Pro_Condition_Abstract {
            public function evaluate($rule, $operator, $value, $context = []) {
                if (!function_exists('is_product_taxonomy')) {
                    return false;
                }
                $term = is_product_taxonomy() ? get_queried_object() : null;
                if (!$term instanceof WP_Term) {
                    return false;
                }
                switch ($rule) {
                    case 'has_description': $match = '' !== trim((string) $term->description); break;
                    default:                $match = false;
                }
                return 'is_not' === $operator ? !$match : $match;
            }
            public function get_rules() {
                return [
                    'has_description' => 'Has description',
                ];
            }
            public function get_rule_metadata($rule) {
                return ['needs_value' => false, 'value_type' => 'none'];
            }
        }
    }

    GenerateBlocks_Pro_Conditions_Registry::register(
        'wc_product',
        [
            'label'     => 'WooCommerce Product',
            'operators' => ['is', 'is_not'],
            'priority'  => 80,
        ],
        'GP_WC_Condition_Product'
    );

    GenerateBlocks_Pro_Conditions_Registry::register(
        'wc_product_archive',
        [
            'label'     => 'WooCommerce Product Archive',
            'operators' => ['is', 'is_not'],
            'priority'  => 81,
        ],
        'GP_WC_Condition_Product_Archive'
    );
});
