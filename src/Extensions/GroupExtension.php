<?php

namespace Fromholdio\Bouncer\Extensions;

use Fromholdio\Helpers\GridFields\Forms\GridFieldAddNewManyManyThroughSearchButton;
use Fromholdio\Helpers\GridFields\Forms\GridFieldConfig_Core;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldGroupDeleteAction;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ManyManyThroughList;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use Fromholdio\Bouncer\Model\GroupMemberJoin;

class GroupExtension extends DataExtension
{
    private static $has_many = [
        'GroupMemberJoins' => GroupMemberJoin::class
    ];

    private static $many_many = [
        'MembersThrough' => [
            'through' => GroupMemberJoin::class,
            'from' => 'Group',
            'to' => 'Member'
        ]
    ];

    private static $cascade_deletes = [
        'GroupMemberJoins'
    ];

    private static $cascade_duplicates = [
        'GroupMemberJoins'
    ];

    private static $summary_fields = [
        'ContextualTitle' => 'Group name'
    ];

    public function onBeforeWrite()
    {
        $this->getOwner()->setField('HtmlEditorConfig', 'cms');
    }

    public function updateCMSFields(FieldList $fields)
    {
        $membersTab = $fields->fieldByName('Root.Members');
        if ($membersTab) {
            $membersTab->setName('Main');
            $membersTab->setTitle('Group');
        }

        $fields->removeByName([
            'HtmlEditorConfig',
            'Description',
            'MembersThrough',
            'Members',
            'GroupMemberJoins'
        ]);

        $curr = Controller::curr();
        $isSecurityAdmin = ($curr instanceof SecurityAdmin);
        if (!$isSecurityAdmin) {
            $fields->removeByName('Permissions');
        }

        if ($this->getOwner()->isConfiguredGroup())
        {
            $fields->removeByName('ParentID');
            $titleField = $fields->dataFieldByName('Title');
            if ($titleField) {
                $titleField->setReadonly(true);
            }
        }
        else {
            $parentField = $fields->dataFieldByName('ParentID');
            if ($parentField)
            {
                $parentSource = $parentField->getSource();
                if (!$isSecurityAdmin) {
                    $adminGroup = Group::get()->find('Code', 'administrators');
                    if ($adminGroup) {
                        unset($parentSource[(int) $adminGroup->getField('ID')]);
                    }
                    $managersGroup = Group::get()->find('Code', 'managers');
                    if ($managersGroup) {
                        unset($parentSource[(int) $managersGroup->getField('ID')]);
                    }
                }
                asort($parentSource);
                $parentField->setSource($parentSource);
                $parentField->setDescription(
                    "If you choose a parent group, this group will inherit the same permissions."
                );
            }
        }

        if ($this->getOwner()->isInDB())
        {
            /** @var ManyManyThroughList $membersList */
            $membersList = $this->getOwner()->MembersThrough();
            $memberButton = new GridFieldAddNewManyManyThroughSearchButton(
                $membersList,
                Member::get(),
                ['FirstName', 'Surname'],
                'Name',
                [],
                false,
                'buttons-before-left',
                'btn-outline-primary font-icon-plus-circled',
                'add-member'
            );
            $memberButton->setTitle('Attach user');

            $membersConfig = GridFieldConfig_Core::create(
                null, 20, true, false, null, false, true
            );

            $membersConfig->removeComponentsByType([
                GridFieldAddNewButton::class,
                GridFieldEditButton::class,
                GridFieldDetailForm::class,
                GridFieldDeleteAction::class
            ]);
            $membersConfig->addComponent($memberButton);
            $membersConfig->addComponent(
                new GridFieldGroupDeleteAction($this->getOwner()->getField('ID'))
            );

            $membersField = GridField::create(
                'MembersList',
                false,
                $membersList,
                $membersConfig
            );

            $fields->addFieldToTab('Root.Main', $membersField);
        }
    }

    public function ContextualTitle()
    {
        return $this->getOwner()->getBreadcrumbs(' > ');
    }

    public function isConfiguredGroup()
    {
        $data = $this->getOwner()->config()->get('configured_groups');
        if (!empty($data)) {
            $codes = array_keys($data);
            $localCode = $this->getOwner()->getField('Code');
            return in_array($localCode, $codes);
        }
        return false;
    }


    public function canDelete($member)
    {
        if ($this->isConfiguredGroup()) {
            return false;
        }
        return null;
    }


    /**
     * @return Group&GroupExtension
     */
    public function getOwner(): Group
    {
        /** @var Group $owner */
        $owner = parent::getOwner();
        return $owner;
    }
}
