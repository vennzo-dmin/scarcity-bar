<?php

namespace App\Shopify;

use App\Models\User;
use App\Support\GidHelper;
use Illuminate\Support\Facades\Cache;

class ProductReader
{
    public function __construct(private readonly User $shop) {}

    public function fetchProductWithVariants(int|string $productId): ?array
    {
        $gid = GidHelper::product($productId);

        $query = <<<'GQL'
        query($id: ID!) {
          product(id: $id) {
            id
            handle
            title
            status
            featuredImage { url altText }
            variants(first: 100) {
              edges {
                node {
                  id
                  title
                  sku
                  price
                  compareAtPrice
                  availableForSale
                  inventoryQuantity
                  inventoryPolicy
                  inventoryItem {
                    id
                    tracked
                    inventoryLevels(first: 25) {
                      edges {
                        node {
                          location { id name }
                          quantities(names: ["available","on_hand","committed"]) {
                            name
                            quantity
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
        GQL;

        $data = (new ShopifyGraphQLClient($this->shop))->query($query, ['id' => $gid]);

        return $data['product'] ?? null;
    }

    public function fetchInventoryByVariant(int|string $variantId): ?array
    {
        $gid = GidHelper::variant($variantId);

        $query = <<<'GQL'
        query($id: ID!) {
          productVariant(id: $id) {
            id
            title
            price
            compareAtPrice
            availableForSale
            inventoryQuantity
            product { id title handle featuredImage { url } }
            inventoryItem {
              id
              tracked
              inventoryLevels(first: 25) {
                edges {
                  node {
                    location { id name }
                    quantities(names: ["available","on_hand"]) {
                      name
                      quantity
                    }
                  }
                }
              }
            }
          }
        }
        GQL;

        $data = (new ShopifyGraphQLClient($this->shop))->query($query, ['id' => $gid]);
        return $data['productVariant'] ?? null;
    }

    public function fetchShopCurrency(): string
    {
        return Cache::remember("bis:shop_currency:{$this->shop->id}", 3600, function () {
            $data = (new ShopifyGraphQLClient($this->shop))->query('{ shop { currencyCode } }');
            return $data['shop']['currencyCode'] ?? 'USD';
        });
    }

    /**
     * Lightweight inventory snapshot for storefront listing grids (collection,
     * search, index discovery). Keys are product handles (lowercase).
     *
     * @param  string[]  $handles
     * @return array<string, array{id:int,title:string,handle:string,featured_image:?string,collection_ids:int[],variants:array<int,array{id:int,title:string,available:bool,inventory_quantity:?int}>}>
     */
    public function fetchProductsInventoryByHandles(array $handles): array
    {
        $handles = array_values(array_unique(array_filter(array_map(static function ($h) {
            return strtolower(trim((string) $h));
        }, $handles))));

        if ($handles === []) {
            return [];
        }

        // Shopify Admin search — keep query length reasonable.
        $handles = array_slice($handles, 0, 18);
        $query = implode(' OR ', array_map(static fn ($h) => 'handle:' . $h, $handles));

        $gql = <<<'GQL'
        query($q: String!) {
          products(first: 20, query: $q) {
            edges {
              node {
                id
                handle
                title
                featuredImage { url }
                collections(first: 25) {
                  edges { node { id } }
                }
                variants(first: 50) {
                  edges {
                    node {
                      id
                      title
                      availableForSale
                      inventoryQuantity
                    }
                  }
                }
              }
            }
          }
        }
        GQL;

        $data = (new ShopifyGraphQLClient($this->shop))->query($gql, ['q' => $query]);
        $edges = $data['products']['edges'] ?? [];
        $out = [];

        foreach ($edges as $edge) {
            $node = $edge['node'] ?? null;
            if (!$node) {
                continue;
            }
            $pid = GidHelper::extractId($node['id'] ?? null);
            $handle = strtolower((string)($node['handle'] ?? ''));
            if (!$pid || $handle === '') {
                continue;
            }

            $collectionIds = [];
            foreach ($node['collections']['edges'] ?? [] as $ce) {
                $cid = GidHelper::extractId($ce['node']['id'] ?? null);
                if ($cid) {
                    $collectionIds[] = $cid;
                }
            }

            $variants = [];
            foreach ($node['variants']['edges'] ?? [] as $ve) {
                $vn = $ve['node'] ?? null;
                if (!$vn) {
                    continue;
                }
                $vid = GidHelper::extractId($vn['id'] ?? null);
                if (!$vid) {
                    continue;
                }
                $qty = $vn['inventoryQuantity'] ?? null;
                $variants[] = [
                    'id'                  => $vid,
                    'title'               => (string)($vn['title'] ?? ''),
                    'available'           => (bool)($vn['availableForSale'] ?? false),
                    'inventory_quantity'  => is_numeric($qty) ? (int) $qty : null,
                ];
            }

            $out[$handle] = [
                'id'               => $pid,
                'title'            => (string)($node['title'] ?? ''),
                'handle'           => $handle,
                'featured_image'   => $node['featuredImage']['url'] ?? null,
                'collection_ids'   => $collectionIds,
                'variants'         => $variants,
            ];
        }

        return $out;
    }
}
