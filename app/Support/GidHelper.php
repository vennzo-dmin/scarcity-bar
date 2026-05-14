<?php

namespace App\Support;

class GidHelper
{
    public static function product(int|string $id): string
    {
        return str_starts_with((string)$id, 'gid://')
            ? (string)$id
            : 'gid://shopify/Product/' . $id;
    }

    public static function variant(int|string $id): string
    {
        return str_starts_with((string)$id, 'gid://')
            ? (string)$id
            : 'gid://shopify/ProductVariant/' . $id;
    }

    public static function location(int|string $id): string
    {
        return str_starts_with((string)$id, 'gid://')
            ? (string)$id
            : 'gid://shopify/Location/' . $id;
    }

    public static function extractId(?string $gid): ?int
    {
        if (!$gid) return null;
        if (preg_match('#/(\d+)$#', $gid, $m)) {
            return (int)$m[1];
        }
        return is_numeric($gid) ? (int)$gid : null;
    }
}
