<?php
namespace App\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class ShopifyHelper
{
    /**
     * Fetch the store name and main theme ID using GraphQL API
     * @return array ['storeName' => string|null, 'themeId' => string|null]
     */
    public static function getShopifyStoreDetails()
    {
        $query = '{
            shop {
                name
            }
            themes(first: 10) {
                edges {
                    node {
                        id
                        name
                        role
                    }
                }
            }
        }';
        $shop = Auth::user();
        $data = self::normalizeGraphResponse($shop->api()->graph($query));

        $storeName = data_get($data, 'data.shop.name');
        $edges     = data_get($data, 'data.themes.edges', []);
        if (!is_array($edges)) {
            $edges = [];
        }

        $themeId = null;
        foreach ($edges as $edge) {
            $role = strtolower((string) data_get($edge, 'node.role', ''));
            if ($role === 'main') {
                $themeId = str_replace('gid://shopify/OnlineStoreTheme/', '', (string) data_get($edge, 'node.id', ''));
                break;
            }
        }
        if (!$themeId && !empty($edges)) {
            $themeId = str_replace(
                'gid://shopify/OnlineStoreTheme/',
                '',
                (string) data_get($edges[0] ?? [], 'node.id', '')
            ) ?: null;
        }

        return [
            'storeName' => $storeName,
            'themeId'   => $themeId,
        ];
    }

    /**
     * Fetch the store Name and Email using GraphQL API
     * @return array ['storeName' => string|null, 'email' => string|null]
     */
    public static function getShopifyStoreNameEmail()
    {
        $query = '{
            shop {
                name,
                email
            }
        }';
        $shop = Auth::user();
        $data = self::normalizeGraphResponse($shop->api()->graph($query));

        return [
            'storeName' => data_get($data, 'data.shop.name'),
            'email'     => data_get($data, 'data.shop.email'),
        ];
    }

    /**
     * Osiset's graph() response has varied between versions: ResponseAccess
     * object, stdClass with ->body, or plain array. Flatten to a pure array.
     */
    private static function normalizeGraphResponse($response): array
    {
        if (is_object($response)) {
            if (isset($response->body)) {
                $body = $response->body;
            } elseif (method_exists($response, 'getDecodedBody')) {
                $body = $response->getDecodedBody();
            } elseif (method_exists($response, 'toArray')) {
                $body = $response->toArray();
            } else {
                $body = $response;
            }
        } elseif (is_array($response)) {
            $body = $response['body'] ?? $response;
        } else {
            return [];
        }

        // Unwrap ResponseAccess / stdClass / nested objects into a plain array.
        $decoded = json_decode(json_encode($body), true);
        return is_array($decoded) ? $decoded : [];
    }
}
