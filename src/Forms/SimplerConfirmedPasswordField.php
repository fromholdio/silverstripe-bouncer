<?php

namespace Fromholdio\Bouncer\Forms;

use SilverStripe\Forms\ConfirmedPasswordField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\View\HTML;

class SimplerConfirmedPasswordField extends ConfirmedPasswordField
{
    /**
     * @param string $name
     * @param string $title
     * @param mixed $value
     * @param Form $form
     * @param boolean $showOnClick
     * @param string $titleConfirmField
     * @param string $titleNewField
     */
    public function __construct(
        $name,
        $title = null,
        $value = "",
        $form = null,
        $showOnClick = false,
        $titleConfirmField = null,
        $titleNewField = null
    ) {

        // Set field title
        $titleNewField = isset($titleNewField) ? $titleNewField : _t('SilverStripe\\Security\\Member.NEWPASSWORD', 'New Password');

        // naming with underscores to prevent values from actually being saved somewhere
        $this->children = FieldList::create(
            $this->passwordField = PasswordField::create(
                "{$name}[_Password]",
                $titleNewField
            ),
            $this->confirmPasswordfield = PasswordField::create(
                "{$name}[_ConfirmPassword]",
                (isset($titleConfirmField)) ? $titleConfirmField : _t('SilverStripe\\Security\\Member.CONFIRMPASSWORD', 'Confirm Password')
            )
        );

        // has to be called in constructor because Field() isn't triggered upon saving the instance
        if ($showOnClick) {
            $this->getChildren()->push($this->hiddenField = HiddenField::create("{$name}[_PasswordFieldVisible]"));
        }

        // disable auto complete
        foreach ($this->getChildren() as $child) {
            /** @var FormField $child */
            $child->setAttribute('autocomplete', 'off');
        }

        $this->showOnClick = $showOnClick;

        parent::__construct($name, $title);
        $this->setValue($value);
    }

    public function Title()
    {
        return $this->title;
    }

    public function setTitle($title, $confirmTitle = null, $newTitle = null)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param array $properties
     *
     * @return string
     */
    public function Field($properties = [])
    {
        // Build inner content
        $fieldContent = '';
        foreach ($this->getChildren() as $field) {
            /** @var FormField $field */
            $field->setDisabled($this->isDisabled());
            $field->setReadonly($this->isReadonly());

            if (count($this->attributes ?? [])) {
                foreach ($this->attributes as $name => $value) {
                    $field->setAttribute($name, $value);
                }
            }

            $fieldContent .= $field->FieldHolder();
        }

        if (!$this->showOnClick) {
            return $fieldContent;
        }

        if ($this->getShowOnClickTitle()) {
            $title = $this->getShowOnClickTitle();
        } else {
            $title = _t(
                __CLASS__ . '.SHOWONCLICKTITLE',
                'Change Password',
                'Label of the link which triggers display of the "change password" formfields'
            );
        }

        // Check if the field should be visible up front
        $visible = $this->hiddenField->Value();
        $classes = $visible
            ? 'showOnClickContainer'
            : 'showOnClickContainer d-none';

        // Build display holder
        $container = HTML::createTag(
            'div',
            [
                'class' => $classes,
                'style' => 'padding-top: 16px;'
            ],
            $fieldContent
        );
        $actionLink = HTML::createTag('a', ['href' => '#'], $title);
        return HTML::createTag(
            'div',
            [
                'class' => 'showOnClick',
                'style' => 'padding-bottom: 0.5385rem; padding-top: 0.5385rem;'
            ],
            $actionLink . "\n" . $container
        );
    }
}
