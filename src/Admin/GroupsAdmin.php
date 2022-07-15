<?php

namespace Fromholdio\Bouncer\Admin;

use Fromholdio\Helpers\ModelAdmin\BaseModelAdmin;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Group;

class GroupsAdmin extends BaseModelAdmin
{
    private static $managed_models = [
        Group::class
    ];

    private static $url_segment = 'groups';
    private static $menu_title = 'Groups';
    private static $menu_icon_class = 'font-icon-rocket';

    public function getList(): DataList
    {
        $list = parent::getList();
        $list = $list->exclude('Code', 'administrators');
        return $list;
    }
}
