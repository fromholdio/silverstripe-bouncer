<?php

namespace Fromholdio\Bouncer\Admin;

use Fromholdio\Sortable\Extensions\Sortable;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use Symbiote\GridFieldExtensions\GridFieldConfigurablePaginator;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

abstract class BouncerModelAdmin extends ModelAdmin
{
    private static $is_auto_orderablerows_enabled = false;
    private static $is_configurablepaginator_enabled = false;
    private static $is_filter_header_enabled = false;

    public $showImportForm = false;
    public $showSearchForm = false;

    public function getEditForm($id = null, $fields = null)
    {
        $modelClass = $this->modelClass;
        $form = parent::getEditForm($id, $fields);
        $field = $form->Fields()->fieldByName($this->sanitiseClassName($modelClass));
        if ($field) {
            $config = $field->getConfig();
            $config->removeComponentsByType([
                GridFieldPrintButton::class,
                GridFieldExportButton::class
            ]);

            if (static::config()->get('is_configurablepaginator_enabled')) {
                $config->removeComponentsByType([GridFieldPaginator::class]);
                $paginator = GridFieldConfigurablePaginator::create(20);
                $config->addComponent($paginator);
            }

            if (static::config()->get('is_auto_orderablerows_enabled')) {
                if ($modelClass::has_extension(Sortable::class)) {
                    $sortField = $modelClass::singleton()->getSortableFieldName();
                    if (!empty($sortField)) {
                        $orderableRows = GridFieldOrderableRows::create($sortField);
                        $config->addComponent($orderableRows);
                    }
                }
            }

            if (static::config()->get('is_filter_header_enabled')) {
                $config->addComponent(GridFieldFilterHeader::create());
            }
        }
        return $form;
    }


    public function ImportForm()
    {
        return null;
    }

    public function getExportFields()
    {
        return null;
    }

    public function SearchForm()
    {
        return null;
    }

    public function SearchSummary()
    {
        return null;
    }

    public function getSearchContext()
    {
        return null;
    }
}
