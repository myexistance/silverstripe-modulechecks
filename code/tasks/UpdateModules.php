<?php

/**
 * main class running all the updates
 *
 *
 */
class UpdateModules extends BuildTask
{

    protected $title = "Update Modules";

    protected $description = "Adds files necessary for publishing a module to GitHub. The list of modules is specified in standard config or else it retrieves a list of modules from GitHub.";

    /**
     * e.g.
     * - moduleA
     * - moduleB
     * - moduleC
     *
     *
     * @var array
     */
    private static $modules_to_update = array();

    /**
     * e.g.
     * - ClassNameForUpdatingFileA
     * - ClassNameForUpdatingFileB
     *
     * @var array
     */
    private static $files_to_update = array();
    /**
     * e.g.
     * - ClassNameForUpdatingFileA
     * - ClassNameForUpdatingFileB
     *
     * @var array
     */
    private static $commands_to_run = array();

    public function run($request) {
        increase_time_limit_to(3600);

        //Check temp module folder is empty
        $tempFolder = GitHubModule::Config()->get('absolute_temp_folder');
        $tempDirFiles = scandir($tempFolder);
        if (count($tempDirFiles) > 2) {
            die ( '<h2>' . $tempFolder . ' is not empty, please delete or move files </h2>');
        }

        //Get list of all modules from GitHub
        $gitUserName = $this->Config()->get('git_user_name');
        //$modules = GitRepoFinder::get_all_repos();
        $modules = array ('silverstripe-wishlist', 'silverstripe-youtubegallery');
        $limitedModules = $this->Config()->get('modules_to_update');


        if($limitedModules && count($limitedModules)) {
            $modules = array_intersect($modules, $limitedModules);
        }

        
        /*
         * Get files to add to modules
         * */
        $files = ClassInfo::subclassesFor('AddFileToModule');

        array_shift($files);
        $limitedFileClasses = $this->Config()->get('files_to_update');
        if($limitedFileClasses && count($limitedFileClasses)) {
            $files = array_intersect($files, $limitedFileClasses);
        }

        /*
         * Get commands to run on modules
         * */
         
        $commands = ClassInfo::subclassesFor('RunCommandLineMethodOnModule');
        array_shift($commands);
        $limitedCommands = $this->Config()->get('commands_to_run');
        if($limitedCommands && count($limitedCommands)) {
            $commands = array_intersect($commands, $limitedCommands);
        }

       
        foreach($modules as $count => $module) {

            if ( stripos($module, 'silverstripe-')  === false ) {
                $module = "silverstripe-" . $module;
            }
            echo "<h2>".($count+1) . ". ".$module."</h2>";

            
            $moduleObject = GitHubModule::get_or_create_github_module($module);


            // Check if all necessary files are perfect on GitHub repo already,
            // if so we can skip that module. But! ... if there are commands to run
            // over the files in the repo, then we need to clone the repo anyhow,
            // so skip the check
            if (count($commands) == 0 ) {
                $moduleFilesOK = true;
                
                foreach($files as $file) {
                    $fileObj = $file::create($moduleObject);
                    $checkFileName = $fileObj->getFileLocation();
                    $GitHubFileText = $moduleObject -> getRawFileFromGithub($checkFileName);
                    if ($GitHubFileText) {
                        $fileCheck = $fileObj->compareWithText($GitHubFileText);
                        if ( ! $fileCheck) {
                            $moduleFilesOK = false;
                        }
                    }
                    else {
                        $moduleFilesOK = false;
                    }
                }

                if ($moduleFilesOK) {
                    GeneralMethods::outputToScreen ("<li> All files in $module OK, skipping moving to next module ... </li>");
                    continue;
                }
            }
            
            $repository = $moduleObject->checkOrSetGitCommsWrapper($forceNew = true);
            foreach($files as $file) {
                //run file update
                $obj = $file::create($moduleObject);
                $obj->run();
            }

            foreach($commands as $command) {
                //run file update
                $obj = $command::create($moduleObject->Directory());
                $obj->run();
                //run command
            }         
            
            if( ! $moduleObject->add()) { die("ERROR in add"); }
            if( ! $moduleObject->commit()) { die("ERROR in commit"); }
            if( ! $moduleObject->push()) { die("ERROR in push"); }
            if( ! $moduleObject->removeClone()) { die("ERROR in removeClone"); }
        }
        //to do ..
    }


    private function checkFile($module, $filename) {
        return file_exists($this->Config()->get('absolute_temp_folder').'/'.$module.'/'.$filename);
    }

    private function checkReadMe($module) {
        return $this->checkFile($module, "README.MD");
    }

    private function checkDirExcludedWords($directory, $wordArray) {
        $filesAndFolders = scandir ($directory);
        
        $problem_files = array();
        foreach ($filesAndFolders as $fileOrFolder) {
            
            if ($fileOrFolder == '.' or $fileOrFolder == '..') {
                continue;
            }
            
            $fileOrFolderFullPath = $directory . '/' . $fileOrFolder;
            if (is_dir($fileOrFolderFullPath)) {
                $dir = $fileOrFolderFullPath;
                $this->checkDirExcludedWords ($dir, $wordArray);
            }
            if (is_file($fileOrFolderFullPath)) {
                $file = $fileOrFolderFullPath;
                $matchedWords = $this->checkFileExcludedWords($file, $wordArray);
                
                if ($matchedWords) {
                   $problem_files[$file] = $matchedWords;
                }
            }
        }
        return $problem_files;
    }

    private function checkFileExcludedWords($fileName, $wordArray) {
        $file = fopen($fileName, 'r');

        $matchedWords = array();
        
        if (! $file) return false;
        $fileContent = fread($file, filesize($fileName));

        
        foreach ($wordArray as $word)  {
            $matches = array();
            $matchCount = preg_match_all('/' . $word . '/i', $fileContent);
            if ($matchCount > 0) {
                $matchedWords[] = $word;
            }
        }

        fclose($file);
        return $matchedWords;
        
    }


}
