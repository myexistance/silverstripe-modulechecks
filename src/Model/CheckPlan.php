<?php

namespace Sunnysideup\ModuleChecks\Model;

use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\ORM\DataObject;

use SilverStripe\ORM\FieldType\DBField;

use SilverStripe\ORM\Filters\ExactMatchFilter;

use Sunnysideup\ModuleChecks\BaseObject;

use Sunnysideup\ModuleChecks\Admin\ModuleCheckModelAdmin;

class CheckPlan extends DataObject
{
    protected static $current_module_check = null;

    protected $availableChecksList = [];

    protected $availableRepos = [];

    #######################
    ### Names Section
    #######################

    private static $singular_name = 'Module Check Plan';

    private static $plural_name = 'Module Check Plan';

    private static $table_name = 'GitHubCheck';

    #######################
    ### Model Section
    #######################

    private static $db = [
        'Completed' => 'Boolean',
        'AllModules' => 'Boolean',
        'AllChecks' => 'Boolean',
    ];

    private static $has_many = [
        'ModuleChecks' => ModuleCheck::class,
    ];

    private static $many_many = [
        'IncludeModules' => Module::class,
        'IncludeChecks' => ModuleCheck::class,
    ];

    private static $many_many_extraFields = [];

    private static $belongs_many_many = [
        'ExcludeModules' => Module::class,
        'ExcludeChecks' => ModuleCheck::class,
    ];

    #######################
    ### Further DB Field Details
    #######################

    private static $cascade_deletes = [
        ModuleCheck::class,
    ];

    private static $indexes = [
        'Completed' => true,
        'AllModules' => true,
        'AllChecks' => true,
    ];

    private static $defaults = [
        'AllChecks' => true,
        'AllModules' => true,
    ];

    private static $default_sort = [
        'ID' => 'DESC',
    ];

    private static $searchable_fields = [
        'Completed' => ExactMatchFilter::class,
    ];

    #######################
    ### Field Names and Presentation Section
    #######################

    private static $field_labels = [
        'AllChecks' => 'Include All Checks',
        'AllModules' => 'Include All Modules',
    ];

    private static $summary_fields = [
        'Created.Nice' => 'Created',
        'Completed.Nice' => 'Completed',
        'AllChecks.Nice' => 'All Checks',
        'AllModules.Nice' => 'All Modules',
        'ModuleCount' => 'Modules Count',
        'CheckCount' => 'Check Count',
        'ModuleChecks.Count' => 'Module Check Count',
    ];

    #######################
    ### Casting Section
    #######################

    private static $casting = [
        'Title' => 'Varchar',
        'ModuleCount' => 'Int',
        'CheckCount' => 'Int',
    ];

    #######################
    ### can Section
    #######################

    private static $primary_model_admin_class = ModuleCheckModelAdmin::class;

    public static function get_current_check_plan(): CheckPlan
    {
        return DataObject::get_one(CheckPlan::class, ['Completed' => 0]);
    }

    public static function set_current_module_check(ModuleCheck $moduleCheck)
    {
        self::$current_module_check = $moduleCheck;
    }

    public static function get_current_module_check(): ?ModuleCheck
    {
        return self::$current_module_check;
    }

    public static function get_next_module_check(): ?ModuleCheck
    {
        $plan = self::get_current_check_plan();

        self::$current_module_check = $plan->ModuleChecks()->filter(['Running' => 0, 'Completed' => 0])->first();

        return self::$current_module_check;
    }

    public function getTitle()
    {
        return DBField::create_field('Varchar', 'FooBar To Be Completed');
    }

    public function getModuleCount()
    {
        return DBField::create_field('Int', 'FooBar To Be Completed');
    }

    public function getCheckCount()
    {
        return DBField::create_field('Int', 'FooBar To Be Completed');
    }

    public function canDelete($member = null, $context = [])
    {
        return false;
    }

    #######################
    ### write Section
    #######################

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        //...
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $this->createChecks();
        //...
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        //...
    }

    #######################
    ### Import / Export Section
    #######################


    #######################
    ### CMS Edit Section
    #######################

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        //...
        $obj = BaseObject::inst();

        $fields->addFieldsToTab(
            'Root.Main',
            [
                LiteralField::create(
                    'AddModules',
                    '<h2 style="text-align: left"><a href="/dev/tasks/load-modules">load modules</a></h2>'
                ),
                $exMods = CheckboxSetField::create(
                    'ExcludeModules',
                    'Excluded Modules',
                    $this->getAvailableModulesForDropdown()
                ),
                $incMods = CheckboxSetField::create(
                    'IncludedModules',
                    'Included Modules',
                    $this->getAvailableModulesForDropdown()
                ),

                $exChecks = CheckboxSetField::create(
                    'ExcludeChecks',
                    'Excluded Checks',
                    $this->getAvailableChecksForDropdown()
                ),
                $incChecks = CheckboxSetField::create(
                    'IncludedChecks',
                    'Included Checks',
                    $this->getAvailableChecksForDropdown()
                )
            ]
        );
        $exMods->displayIf("AllModules")->isChecked();
        $incMods->displayIf("AllModules")->isNotChecked();
        $exChecks->displayIf("AllChecks")->isChecked();
        $incChecks->displayIf("AllChecks")->isNotChecked();


        return $fields;
    }

    protected function createChecks()
    {
        $obj = BaseObject::inst();
        if ($this->AllChecks) {
            $checks = $obj->getAvailableChecks();
            foreach ($this->ExcludeChecks() as $excludedCheck) {
                unset($checks[$excludedCheck->ID]);
            }
        } else {
            $checks = [];
            foreach ($this->IncludeChecks() as $includedCheck) {
                $checks[$includedCheck->ID] = $includedCheck;
            }
        }
        if ($this->AllModules) {
            $modules = $obj->getAvailableModules();
            foreach ($this->ExcludeModules() as $excludedModules) {
                unset($modules[$excludedModules->ID]);
            }
        } else {
            $modules = [];
            foreach ($this->IncludeModules() as $includedModule) {
                $modules[$includedModule->ID] = $includedModule;
            }
        }
        foreach (array_keys($modules) as $moduleID) {
            foreach (array_keys($checks) as $checkID) {
                $filter = [
                    'ModuleCheckPlanID' => $this->ID,
                    'Module' => $moduleID,
                    'Check' => $checkID,
                ];
                $obj = DataObject::get_one(ModuleCheck::class, $filter);
                if (! $obj) {
                    $obj = ModuleCheck::create($obj);
                    $obj->write();
                }
            }
        }
    }


    protected function getAvailableChecks(): array
    {
        if (! count($this->availableChecksList)) {
            $list = Check::get();
            foreach ($list as $obj) {
                if ($obj->Enabled) {
                    $this->availableChecksList[$obj->MyClass] = $obj;
                }
            }
        }

        return $this->availableChecksList;
    }

    protected function getAvailableChecksForDropdown(): array
    {
        $list = $this->getAvailableChecks();
        $array = [];
        foreach ($list as $obj) {
            $array[$obj->ID] = $obj->Type . ': ' . $obj->Title;
        }
        return $array;
    }

    protected function getAvailableModules(): array
    {
        if (! count($this->availableRepos)) {
            $list = Module::get();
            foreach ($list as $obj) {
                if (! $obj->Disabled) {
                    $this->availableRepos[$obj->ModuleName] = $obj;
                }
            }
        }

        return $this->availableRepos;
    }

    protected function getAvailableModulesForDropdown(): array
    {
        $list = $this->getAvailableModules();
        $array = [];
        foreach ($list as $obj) {
            $array[$obj->ID] = $obj->ModuleName;
        }
        asort($array);

        return $array;
    }

}
