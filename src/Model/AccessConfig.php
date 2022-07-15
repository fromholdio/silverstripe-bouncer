<?php

namespace Fromholdio\Bouncer\Model;

use Fromholdio\GenericConfig\Model\GenericConfig;
use SGN\HasOneEdit\HasOneEdit;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use UncleCheese\DisplayLogic\Forms\Wrapper;

class AccessConfig extends GenericConfig implements PermissionProvider
{
    private static $table_name = 'Config_Access';
    private static $singular_name = 'Access configuration';
    private static $plural_name = 'Access configurations';

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $hasOnePrefix = 'SiteConfig' . HasOneEdit::FIELD_SEPARATOR;

        $mapFn = function ($groups = []) {
            $map = [];
            foreach ($groups as $group) {
                // Listboxfield values are escaped, use ASCII char instead of &raquo;
                $map[$group->ID] = $group->getBreadcrumbs(' > ');
            }
            asort($map);
            return $map;
        };
        $groupsMap = $mapFn(Group::get()->exclude('Code', 'administrators'));
        $viewAllGroupsMap = $mapFn(
            Permission::get_groups_by_permission(
                ['SITETREE_VIEW_ALL', 'ADMIN']
            )->exclude('Code', 'administrators')
        );

        $viewersOptionsSource = [
            'Anyone' => _t('SilverStripe\\CMS\\Model\\SiteTree.ACCESSANYONE', "Anyone"),
            'LoggedInUsers' => _t(
                'SilverStripe\\CMS\\Model\\SiteTree.ACCESSLOGGEDIN',
                'Logged-in users'
            ),
            'OnlyTheseUsers' => _t(
                'SilverStripe\\CMS\\Model\\SiteTree.ACCESSONLYTHESE',
                'Only select groups'
            )
        ];

        $viewersOptionsField = new OptionsetField(
            $hasOnePrefix . 'CanViewType',
            _t(
                self::class . '.VIEWHEADER',
                'Who can view pages on this site?'
            ),
            $viewersOptionsSource
        );

        $viewerGroupsField = CheckboxSetField::create(
            $hasOnePrefix . 'ViewerGroups',
            _t('SilverStripe\\CMS\\Model\\SiteTree.VIEWERGROUPS', 'Viewer Groups')
        );
        $viewerGroupsField->setSource($groupsMap);
        $viewerGroupsField->setAttribute(
            'data-placeholder',
            _t('SilverStripe\\CMS\\Model\\SiteTree.GroupPlaceholder', 'Click to select group')
        );

        if ($viewAllGroupsMap) {
            $viewerGroupsField->setDefaultItems(array_keys($viewAllGroupsMap));
            $viewerGroupsField->setDisabledItems(array_keys($viewAllGroupsMap));
        }

        $viewerGroupsWrapper = Wrapper::create(
            $viewerGroupsField
        );
        $viewerGroupsWrapper->setName('ViewerGroupsWrapper');
        $viewerGroupsWrapper
            ->displayIf($hasOnePrefix . 'CanViewType')
            ->isEqualTo('OnlyTheseUsers');

        $fields->addFieldsToTab(
            'Root.MainTabSet.MainTab',
            [
                $viewersOptionsField,
                $viewerGroupsWrapper
            ]
        );

        return $fields;
    }
}
