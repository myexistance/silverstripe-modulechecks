<?php

class AddTestToModule extends AddFileToModule
{
    protected $gitReplaceArray = array(
        'Module' => 'ShortUCFirstName',
    );

    protected $sourceLocation = 'source/ModuleTest.php';

    
    public function __construct($gitObject)
    {
        $this-> fileLocation = 'tests/' . $gitObject->ShortUCFirstName() . 'Test.php';
        parent::__construct($gitObject);
    }
}
