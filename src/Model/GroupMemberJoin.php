<?php

namespace Fromholdio\Bouncer\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;

class GroupMemberJoin extends DataObject
{
    private static $table_name = 'Group_Members';

    private static $has_one = [
        'Member' => Member::class,
        'Group' => Group::class
    ];
}
