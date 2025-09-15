<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Navigation;

class Helper
{
    public static function applyDefaults(Registry $registry): void
    {
        $r = $registry;

        // App sections
        $r->section('app.primary');
        $r->section('app.apps', p__('nav', 'Apps'));

        $item = new Item('/app', p__('nav', 'Home'), 'home');
        $r->item('app.primary', $item);

        $item = new Item('/app/library', p__('nav', 'Library'), 'library');
        $r->item('app.primary', $item);

        $item = new Item('/app/models', p__('nav', 'Models'), 'cpu');
        $r->item('app.primary', $item);

        // Admin sections
        $r->section('admin.primary');
        $r->section('admin.ai', p__('nav', 'AI'));
        $r->section('admin.billing', p__('nav', 'Billing'));
        $r->section('admin.affiliates', p__('nav', 'Affiliates'));
        $r->section('admin.accounts', p__('nav', 'Accounts'));
        $r->section('admin.platform', p__('nav', 'Platform'));


        $item = new Item('/admin', p__('nav', 'Dashboard'), 'home');
        $r->item('admin.primary', $item);

        $item = new Item('/admin/analytics', p__('nav', 'Analytics'), 'graph');
        $r->item('admin.primary', $item);

        $item = new Item('/admin/categories', p__('nav', 'Categories'), 'category');
        $r->item('admin.primary', $item);


        $item = new Item('/admin/templates', p__('nav', 'Templates'), 'stack');
        $r->item('admin.ai', $item);

        $item = new Item('/admin/assistants', p__('nav', 'Assistants'), 'message-bolt');
        $r->item('admin.ai', $item);

        $item = new Item('/admin/voices', p__('nav', 'Voices'), 'microphone');
        $r->item('admin.ai', $item);

        $item = new Item('/admin/plans', p__('nav', 'Plans'), 'box');
        $r->item('admin.billing', $item);

        $item = new Item('/admin/orders', p__('nav', 'Orders'), 'coins');
        $r->item('admin.billing', $item);

        $item = new Item('/admin/subscriptions', p__('nav', 'Subscriptions'), 'refresh');
        $r->item('admin.billing', $item);

        $item = new Item('/admin/coupons', p__('nav', 'Coupons'), 'rosette-discount');
        $r->item('admin.billing', $item);


        $item = new Item('/admin/affiliates', p__('nav', 'Accounts'), 'affiliate');
        $r->item('admin.affiliates', $item);

        $item = new Item('/admin/affiliates/payouts', p__('nav', 'Payouts'), 'credit-card-pay');
        $r->item('admin.affiliates', $item);


        $item = new Item('/admin/users', p__('nav', 'Users'), 'users');
        $r->item('admin.accounts', $item);

        $item = new Item('/admin/workspaces', p__('nav', 'Workspaces'), 'building');
        $r->item('admin.accounts', $item);


        $item = new Item('/admin/plugins', p__('nav', 'Plugins'), 'puzzle');
        $r->item('admin.platform', $item);

        $item = new Item('/admin/themes', p__('nav', 'Themes'), 'palette');
        $r->item('admin.platform', $item);

        $item = new Item('/admin/settings', p__('nav', 'Settings'), 'settings');
        $r->item('admin.platform', $item);

        $item = new Item('/admin/status', p__('nav', 'Status'), 'broadcast');
        $r->item('admin.platform', $item);

        $item = new Item('/admin/update', p__('nav', 'Update'), 'refresh');
        $r->item('admin.platform', $item);
    }
}
