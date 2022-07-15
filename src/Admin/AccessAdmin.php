<?php

namespace Fromholdio\Bouncer\Admin;

use Fromholdio\GenericConfig\Admin\GenericConfigAdmin;
use SGN\HasOneEdit\UpdateFormExtension;
use SilverStripe\Security\Permission;
use Fromholdio\Bouncer\Model\AccessConfig;

class AccessAdmin extends GenericConfigAdmin
{
    private static $menu_title = 'Access';
    private static $tree_class = AccessConfig::class;
    private static $url_segment = 'access';

    private static $menu_icon = null;
    private static $menu_icon_class = 'font-icon-lock';

    private static $extensions = [
        UpdateFormExtension::class
    ];

    public function canView($member = null)
    {
        return Permission::check('EDIT_ACCESSCONFIG');
    }
}
