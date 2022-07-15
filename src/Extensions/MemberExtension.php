<?php

namespace Fromholdio\Bouncer\Extensions;

use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Fromholdio\Bouncer\Forms\SimplerConfirmedPasswordField;
use Fromholdio\Bouncer\Model\GroupMemberJoin;

class MemberExtension extends DataExtension
{
    private static $has_many = [
        'GroupMemberJoins' => GroupMemberJoin::class
    ];

    private static $many_many = [
        'GroupsThrough' => [
            'through' => GroupMemberJoin::class,
            'from' => 'Member',
            'to' => 'Group'
        ]
    ];

    private static $cascade_deletes = [
        'GroupMemberJoins'
    ];

    private static $cascade_duplicates = [
        'GroupMemberJoins'
    ];

    public function onBeforeWrite()
    {
        $this->getOwner()->setField('Locale', 'en_GB');
        $this->getOwner()->setField('DefaultMenuMode', 'open');
        $this->getOwner()->setField('DefaultPreviewMode', 'content');
    }

    public function onAfterWrite()
    {
        $this->getOwner()->enforceAdministratorsGroup();
    }

    public function onAfterSkippedWrite()
    {
        $this->getOwner()->enforceAdministratorsGroup();
    }

    public function enforceAdministratorsGroup()
    {
        if ($this->getOwner()->IsInAdministratorsGroup) {
            $this->getOwner()->addToGroupByCode('administrators');
        }
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName([
            'Locale',
            'Permissions',
            'DefaultMenuMode',
            'DefaultPreviewMode',
            'FacebookProfileURL',
            'TwitterHandle',
            'FailedLoginCount',
            'LoginSessions',
            'GroupsThrough',
            'GroupMemberJoins'
        ]);

        $curr = Controller::curr();
        if ($curr instanceof SecurityAdmin) {
            return;
        }

        if ($this->getOwner()->isInDB()) {
            $fields->removeByName('Password');
        }
        else {
            $passwordField = $fields->fieldByName('Root.Main.Password');
            if ($passwordField) {
                $fields->removeByName('Password');
                $fields->insertAfter('DirectGroups', $passwordField);
            }
        }

        if (!Permission::check('ADMIN'))
        {
            $adminGroup = Group::get()->find('Code', 'administrators');
            $groupsField = $fields->fieldByName('Root.Main.DirectGroups');
            if ($adminGroup && $groupsField)
            {
                $groupsSource = $groupsField->getSource();
                foreach ($groupsSource as $id => $title) {
                    if ((int) $id === (int) $adminGroup->getField('ID')) {
                        unset($groupsSource[$id]);
                    }
                }
                $groupsField->setSource($groupsSource);

                if ($this->getOwner()->inGroup($adminGroup, true))
                {
                    $adminGroupField = HiddenField::create('IsInAdministratorsGroup', false, 1);
                    $fields->insertAfter('DirectGroups', $adminGroupField);
                }
            }
        }
    }

    public function updateMemberPasswordField(&$field)
    {
        $curr = Controller::curr();
        if ($curr instanceof SecurityAdmin) {
            return;
        }

        $isExists = $this->getOwner()->isInDB();
        $field = SimplerConfirmedPasswordField::create(
            'Password',
            'Password',
            null,
            null,
            false,
            'Confirm Password',
            'New Password'
        );

        $currMember = Security::getCurrentUser();
        if ($isExists && $currMember) {
            if ($currMember->getField('ID') === $this->getOwner()->getField('ID')) {
                $field->setRequireExistingPassword(true);
            }
        }
        $field->setCanBeEmpty(true);
    }


    /**
     * @return Member&MemberExtension
     */
    public function getOwner(): Member
    {
        /** @var Member $owner */
        $owner = parent::getOwner();
        return $owner;
    }
}
