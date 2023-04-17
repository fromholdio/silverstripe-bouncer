<?php

namespace Fromholdio\Bouncer\Admin;

use SilverStripe\Security\Member;

class MembersAdmin extends BouncerModelAdmin
{
    private static $managed_models = [
        Member::class
    ];

    private static $url_segment = 'users';
    private static $menu_title = 'Users';
    private static $menu_icon_class = 'font-icon-menu-security';
}
