<?php
declare(strict_types=1);

const TRANSACTION_STATUSES = ['menunggu_bayar', 'menunggu_verifikasi', 'diproses', 'berhasil', 'gagal', 'dibatalkan'];
const ACTIONABLE_TRANSACTION_STATUSES = ['menunggu_verifikasi', 'diproses'];
const OPEN_TRANSACTION_STATUSES = ['menunggu_bayar', 'menunggu_verifikasi', 'diproses'];
const VOUCHER_CATEGORY = 'Voucher Game';

function e(?string $v): string
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function money(float|string $n): string
{
    return 'Rp ' . number_format((float) $n, 0, ',', '.');
}

function product_image(?string $img): string
{
    return $img ? 'uploads/' . rawurlencode($img) : 'assets/product.svg';
}

function icon(string $name): string
{
    $paths = [
        'gamepad' => '<rect x="2.5" y="8" width="19" height="10" rx="5"/><path d="M8 11v4M6 13h4"/><path d="M15.5 12.5h.01M18 15h.01"/>',
        'phone' => '<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/>',
        'bolt' => '<path d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z"/>',
        'ticket' => '<path d="M3 9V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 6v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-6Z"/><path d="M13 5v2M13 11v2M13 17v2"/>',
        'bank' => '<path d="m3 9 9-5 9 5"/><path d="M4 9h16"/><path d="M5 9v8M9.5 9v8M14.5 9v8M19 9v8"/><path d="M3 20h18"/>',
        'wallet' => '<path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-9Z"/><path d="M15 12h5v4h-5a2 2 0 0 1 0-4Z"/>',
        'qr' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><path d="M14 14h3v3h-3z"/><path d="M20 14v3M17 20h3"/>',
        'receipt' => '<path d="M6 3h12v16l-2-1.5-2 1.5-2-1.5L10 19l-2-1.5L6 19V3Z"/><path d="M9 8h6M9 12h6"/>',
        'clock' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 2.5"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 15.5 9 5 9-5"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'box' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="M12 12 4 7.5M12 12l8-4.5M12 12v9"/>',
        'chart' => '<path d="M5 20v-6M10 20V8M15 20V10M20 20V5"/>',
        'swap' => '<path d="M7 4 4 7l3 3"/><path d="M4 7h13"/><path d="m17 20 3-3-3-3"/><path d="M20 17H7"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.4 3 7.6 7 9 4-1.4 7-4.6 7-9V6l-7-3Z"/>',
        'check' => '<path d="m5 13 4 4L19 7"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ];
    $p = $paths[$name] ?? $paths['ticket'];
    return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function category_icon(string $categoryName): string
{
    return match (true) {
        str_contains($categoryName, 'Token') || str_contains($categoryName, 'PLN') || str_contains($categoryName, 'Listrik') => icon('bolt'),
        str_contains($categoryName, 'Pulsa') || str_contains($categoryName, 'Data') => icon('phone'),
        str_contains($categoryName, 'Voucher') || str_contains($categoryName, 'Game') => icon('gamepad'),
        default => icon('ticket'),
    };
}

function status_label(string $status): string
{
    return match ($status) {
        'menunggu_bayar' => 'Menunggu bayar',
        'menunggu_verifikasi' => 'Menunggu verifikasi',
        'diproses' => 'Diproses',
        'berhasil' => 'Berhasil',
        'gagal' => 'Gagal',
        'dibatalkan' => 'Dibatalkan',
        default => ucfirst($status),
    };
}

function is_voucher_category(?string $categoryName): bool
{
    return $categoryName === VOUCHER_CATEGORY;
}

function is_voucher_label(string $categoryName): bool
{
    return str_contains($categoryName, 'Voucher');
}

function destination_label(string $categoryName): string
{
    return match (true) {
        str_contains($categoryName, 'Token') => 'ID pelanggan PLN',
        is_voucher_label($categoryName) => 'ID game',
        default => 'Nomor HP',
    };
}

function availability_label(string $categoryName, int $stock): string
{
    if (!is_voucher_label($categoryName)) {
        return 'Tersedia 24 jam';
    }
    return $stock > 0 ? 'Stok tersedia' : 'Stok sedang habis';
}

function product_is_orderable(string $categoryName, int $stock): bool
{
    return !is_voucher_label($categoryName) || $stock > 0;
}
