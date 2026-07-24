<?php

namespace App\Enums;

enum AdminPermission: string
{
    case Vendors = 'vendors';
    case Products = 'products';
    case Orders = 'orders';
    case Dispatch = 'dispatch';
    case Reconciliation = 'reconciliation';
    case Agents = 'agents';
    case DeliveryZones = 'delivery-zones';
    case Blacklist = 'blacklist';
    case Settings = 'settings';
    case Payouts = 'payouts';

    public function label(): string
    {
        return match ($this) {
            self::Vendors => 'Vendors',
            self::Products => 'Products',
            self::Orders => 'Orders',
            self::Dispatch => 'Dispatch',
            self::Reconciliation => 'Reconciliation',
            self::Agents => 'Delivery Agents',
            self::DeliveryZones => 'Delivery Zones',
            self::Blacklist => 'Blacklist',
            self::Settings => 'Business Settings',
            self::Payouts => 'Vendor Payouts',
        };
    }
}
