<?php

namespace Sunnysideup\ModuleChecks\FilesToAdd;

use AddFileToModule;


class AddHtAccessToModule extends AddFileToModule
{
    protected $sourceLocation = 'app/template_files/.htaccess';

    protected $fileLocation = '.htaccess';
}

